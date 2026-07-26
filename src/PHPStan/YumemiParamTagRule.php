<?php

/**
 * Iudex Mensurarum Mysticarum『夢見』〜ＹＵＭＥＭＩ〜
 *
 * Copyright (c) anno Domini nostri Jesu Christi MMXXVI, John Boehr & contributors
 *
 * SPDX-License-Identifier: AGPL-3.0-only WITH romic-exception
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License version 3,
 * as published by the Free Software Foundation, together with the Romic
 * Exception (an additional permission under section 7 of that license).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * and the Romic Exception along with this program.  If not, see
 * <http://www.gnu.org/licenses/> and the LICENSE_EXCEPTION file.
 */

namespace jbboehr\Yumemi\PHPStan;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;

/**
 * Checks arguments at call sites against the callee's extension-optional @yumemi-param tags.
 *
 * This is the caller side of graceful degradation: a function/method keeps a native parameter type
 * in its signature (so unbranded callers are unaffected) and declares the intended unit with, e.g.:
 *
 *     @param int $length
 *     @yumemi-param unit_int<'foot'> $length
 *
 * Only *branded* arguments are checked. A bare native value (a plain int/float) is the graceful
 * escape hatch and passes silently; a branded value carrying the wrong unit (e.g. a
 * unit_int<'meter'> where feet are expected) is a dimensional mistake and is reported. Registered on
 * {@see CallLike} so one rule covers function and method calls (static calls / `new` are deferred).
 *
 * @implements Rule<CallLike>
 */
final class YumemiParamTagRule implements Rule
{
    public function __construct(
        private readonly FileTypeMapper $fileTypeMapper,
        private readonly ReflectionProvider $reflectionProvider,
        private readonly YumemiDocTagReader $reader,
    ) {
    }

    public function getNodeType(): string
    {
        return CallLike::class;
    }

    /**
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        [$paramTypes, $parameters] = match (true) {
            $node instanceof FuncCall => $this->resolveFunction($node, $scope),
            $node instanceof MethodCall => $this->resolveMethod($node, $scope),
            default => [[], []],
        };

        if ($paramTypes === []) {
            return [];
        }

        return $this->checkArgs($node, $scope, $parameters, $paramTypes);
    }

    /**
     * @return array{array<string, Type>, list<ParameterReflection>}
     */
    private function resolveFunction(FuncCall $node, Scope $scope): array
    {
        if (!$node->name instanceof Name || !$this->reflectionProvider->hasFunction($node->name, $scope)) {
            return [[], []];
        }

        $function = $this->reflectionProvider->getFunction($node->name, $scope);

        $docComment = $function->getDocComment();
        if ($docComment === null) {
            return [[], []];
        }

        $phpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
            $function->getFileName(),
            null,
            null,
            $function->getName(),
            $docComment,
        );

        $paramTypes = $this->reader->paramTypes($phpDoc);
        if ($paramTypes === []) {
            return [[], []];
        }

        $acceptor = ParametersAcceptorSelector::selectFromArgs($scope, $node->getArgs(), $function->getVariants(), null);

        return [$paramTypes, $acceptor->getParameters()];
    }

    /**
     * @return array{array<string, Type>, list<ParameterReflection>}
     */
    private function resolveMethod(MethodCall $node, Scope $scope): array
    {
        if (!$node->name instanceof Identifier) {
            return [[], []];
        }

        $calledOnType = $scope->getType($node->var);
        $methodName = $node->name->toString();
        if (!$calledOnType->hasMethod($methodName)->yes()) {
            return [[], []];
        }

        $method = $calledOnType->getMethod($methodName, $scope);

        $phpDoc = $method->getResolvedPhpDoc();
        if ($phpDoc === null) {
            return [[], []];
        }

        $paramTypes = $this->reader->paramTypes($phpDoc);
        if ($paramTypes === []) {
            return [[], []];
        }

        $acceptor = ParametersAcceptorSelector::selectFromArgs($scope, $node->getArgs(), $method->getVariants(), null);

        return [$paramTypes, $acceptor->getParameters()];
    }

    /**
     * @param list<ParameterReflection> $parameters
     * @param array<string, Type> $paramTypes
     *
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    private function checkArgs(CallLike $node, Scope $scope, array $parameters, array $paramTypes): array
    {
        $errors = [];
        $position = 0;

        foreach ($node->getArgs() as $arg) {
            if ($arg->unpack) {
                // Spread argument: positions past here can't be mapped statically.
                break;
            }

            $paramName = $arg->name !== null
                ? $arg->name->toString()
                : $this->positionalParamName($parameters, $position++);

            if ($paramName === null || !isset($paramTypes[$paramName])) {
                continue;
            }

            $expected = $paramTypes[$paramName];
            $actual = $scope->getType($arg->value);

            // Only branded arguments are checked; unbranded native values pass (graceful degradation).
            if (!$this->reader->isUnitType($actual)) {
                continue;
            }

            $accepts = $expected->accepts($actual, true);
            if ($accepts->yes()) {
                continue;
            }

            $error = RuleErrorBuilder::message(sprintf(
                '@yumemi-param: parameter $%s expects %s, %s given.',
                $paramName,
                $expected->describe(VerbosityLevel::typeOnly()),
                $actual->describe(VerbosityLevel::typeOnly()),
            ))->identifier('yumemi.paramType')->line($arg->getStartLine());

            if ($accepts->reasons !== []) {
                $error = $error->tip(implode("\n", $accepts->reasons));
            }

            $errors[] = $error->build();
        }

        return $errors;
    }

    /**
     * @param list<ParameterReflection> $parameters
     */
    private function positionalParamName(array $parameters, int $position): ?string
    {
        if (isset($parameters[$position])) {
            return $parameters[$position]->getName();
        }

        if ($parameters === []) {
            return null;
        }

        // Extra positional args map to a trailing variadic parameter, if any.
        $last = $parameters[array_key_last($parameters)];

        return $last->isVariadic() ? $last->getName() : null;
    }
}
