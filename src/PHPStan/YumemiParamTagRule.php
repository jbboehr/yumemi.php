<?php

/**
 * +--------------------------------------------------------------------------------------------------------------+
 * |        *                 .                         *                  .                         *            |
 * |   .              *                      .                    *                      .                        |
 * |             .                 .                  *                         .                 *               |
 * -      *                    .             *                    .                         .                     -
 *
 *                               Iudex Mensurarum Mysticarum『夢見』〜ＹＵＭＥＭＩ〜
 *
 * -                                          .----------------.                                                  -
 * |                                      .--'        __        '--.                                              |
 * |                                  .--'          .'  '.          '--.                                          |
 * |                             .---'            .'      '.            '---.                                     |
 * +--------------------------------------------------------------------------------------------------------------+
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
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ExtendedMethodReflection;
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
 * {@see CallLike} so one rule covers function calls, instance and static method calls, and `new`
 * (constructor) calls; dynamic or anonymous class/method targets are left unresolved.
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
            $node instanceof StaticCall => $this->resolveStaticCall($node, $scope),
            $node instanceof New_ => $this->resolveNew($node, $scope),
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

        // Fast path: functions have no phpdoc inheritance, so the raw comment is authoritative.
        if ($docComment === null || !str_contains($docComment, YumemiDocTagReader::PARAM_TAG)) {
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

        return $this->resolveFromMethod($calledOnType->getMethod($methodName, $scope), $node->getArgs(), $scope);
    }

    /**
     * @return array{array<string, Type>, list<ParameterReflection>}
     */
    private function resolveStaticCall(StaticCall $node, Scope $scope): array
    {
        // Dynamic method / class names (`$obj::$m()`, `$class::m()`) aren't resolved here.
        if (!$node->name instanceof Identifier || !$node->class instanceof Name) {
            return [[], []];
        }

        $classType = $scope->resolveTypeByName($node->class);
        $methodName = $node->name->toString();
        if (!$classType->hasMethod($methodName)->yes()) {
            return [[], []];
        }

        return $this->resolveFromMethod($classType->getMethod($methodName, $scope), $node->getArgs(), $scope);
    }

    /**
     * @return array{array<string, Type>, list<ParameterReflection>}
     */
    private function resolveNew(New_ $node, Scope $scope): array
    {
        // Dynamic (`new $class()`) and anonymous (`new class {}`) instantiations aren't resolved here.
        if (!$node->class instanceof Name) {
            return [[], []];
        }

        $className = $scope->resolveName($node->class);
        if (!$this->reflectionProvider->hasClass($className)) {
            return [[], []];
        }

        $classReflection = $this->reflectionProvider->getClass($className);
        if (!$classReflection->hasConstructor()) {
            return [[], []];
        }

        return $this->resolveFromMethod($classReflection->getConstructor(), $node->getArgs(), $scope);
    }

    /**
     * Shared tail for method-like calls (instance / static / constructor): apply the fast-path guard,
     * read @yumemi-param types, and align them against the resolved parameters.
     *
     * @param array<Arg> $args
     *
     * @return array{array<string, Type>, list<ParameterReflection>}
     */
    private function resolveFromMethod(ExtendedMethodReflection $method, array $args, Scope $scope): array
    {
        // Fast path: a method that inherits no phpdoc has only its own comment as a tag source, so a
        // missing @yumemi-param there means nothing to check. Overriding/implementing methods may
        // inherit the tag from an ancestor (verified: the rule fires on doc-less overrides/impls), so
        // they always take the full resolve path below.
        if (!$this->inheritsPhpDoc($method)) {
            $ownDocComment = $method->getDocComment();
            if ($ownDocComment === null || !str_contains($ownDocComment, YumemiDocTagReader::PARAM_TAG)) {
                return [[], []];
            }
        }

        $phpDoc = $method->getResolvedPhpDoc();
        if ($phpDoc === null) {
            return [[], []];
        }

        $paramTypes = $this->reader->paramTypes($phpDoc);
        if ($paramTypes === []) {
            return [[], []];
        }

        $acceptor = ParametersAcceptorSelector::selectFromArgs($scope, $args, $method->getVariants(), null);

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

    /**
     * Whether the method may inherit phpdoc (and thus a @yumemi-param) from an ancestor.
     *
     * True when its prototype is declared on a different class — i.e. it overrides a parent method or
     * implements an interface method. Such methods must resolve the full (inherited) phpdoc; only
     * methods whose prototype is themselves can trust their own raw doc comment for the fast path.
     */
    private function inheritsPhpDoc(ExtendedMethodReflection $method): bool
    {
        try {
            $prototype = $method->getPrototype();
        } catch (\Throwable) {
            // No resolvable prototype: be conservative and take the full resolve path.
            return true;
        }

        return $prototype->getDeclaringClass()->getName() !== $method->getDeclaringClass()->getName();
    }
}
