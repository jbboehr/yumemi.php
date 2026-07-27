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

use jbboehr\Yumemi\Quantity;
use PHPStan\PhpDoc\ResolvedPhpDocBlock;
use PHPStan\Reflection\ExtendedParameterReflection;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;

/**
 * Shared declaration-time validation for extension-optional Yumemi PHPDoc tags.
 */
final class YumemiDocTagValidator
{
    public function __construct(
        private readonly YumemiDocTagReader $reader,
    ) {
    }

    /**
     * @param list<ExtendedParameterReflection> $parameters
     *
     * @return list<IdentifierRuleError>
     */
    public function validate(
        ResolvedPhpDocBlock $phpDoc,
        array $parameters,
        Type $nativeReturnType,
        bool $supportsReturnTag,
        int $line,
    ): array {
        $errors = $this->validateParamTags($phpDoc, $parameters, $line);

        return [
            ...$errors,
            ...$this->validateReturnTags($phpDoc, $nativeReturnType, $supportsReturnTag, $line),
        ];
    }

    /**
     * @param list<ExtendedParameterReflection> $parameters
     *
     * @return list<IdentifierRuleError>
     */
    private function validateParamTags(ResolvedPhpDocBlock $phpDoc, array $parameters, int $line): array
    {
        $parameterMap = [];
        foreach ($parameters as $parameter) {
            $parameterMap[$parameter->getName()] = $parameter;
        }

        $errors = [];
        $seen = [];

        foreach ($this->reader->paramTags($phpDoc) as $tag) {
            $name = $tag->parameterName;
            if ($name === null) {
                $errors[] = $this->invalidTagError($tag, $line);

                continue;
            }

            if (isset($seen[$name])) {
                $errors[] = $this->error(
                    sprintf('PHPDoc tag %s for $%s is duplicated.', $tag->tagName, $name),
                    'yumemi.docTagDuplicate',
                    $line,
                );

                continue;
            }
            $seen[$name] = true;

            if ($tag->errorKind !== null || $tag->type === null) {
                $errors[] = $this->invalidTagError($tag, $line);

                continue;
            }

            if (!isset($parameterMap[$name])) {
                $errors[] = $this->error(
                    sprintf('PHPDoc tag %s references unknown parameter $%s.', $tag->tagName, $name),
                    'yumemi.docTagParameter',
                    $line,
                );

                continue;
            }

            $nativeType = $parameterMap[$name]->getNativeType();
            [$expectedNativeType, $expectedDescription] = $this->expectedNativeType($tag->type);
            if ($expectedNativeType->equals($nativeType)) {
                continue;
            }

            $errors[] = $this->error(
                sprintf(
                    'PHPDoc tag %s for $%s declares %s but the native parameter type is %s; expected %s.',
                    $tag->tagName,
                    $name,
                    $tag->type->describe(VerbosityLevel::typeOnly()),
                    $nativeType->describe(VerbosityLevel::typeOnly()),
                    $expectedDescription,
                ),
                'yumemi.docTagType',
                $line,
            );
        }

        return $errors;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function validateReturnTags(
        ResolvedPhpDocBlock $phpDoc,
        Type $nativeReturnType,
        bool $supportsReturnTag,
        int $line,
    ): array {
        $tags = $this->reader->returnTags($phpDoc);
        if ($tags === []) {
            return [];
        }

        $errors = [];

        foreach ($tags as $index => $tag) {
            if ($index > 0) {
                $errors[] = $this->error(
                    sprintf('PHPDoc tag %s is duplicated.', $tag->tagName),
                    'yumemi.docTagDuplicate',
                    $line,
                );

                continue;
            }

            if (!$supportsReturnTag) {
                $errors[] = $this->error(
                    sprintf('PHPDoc tag %s is not supported on methods.', $tag->tagName),
                    'yumemi.docTagUnsupported',
                    $line,
                );

                continue;
            }

            if ($tag->errorKind !== null || $tag->type === null) {
                $errors[] = $this->invalidTagError($tag, $line);

                continue;
            }

            [$expectedNativeType, $expectedDescription] = $this->expectedNativeType($tag->type);
            if ($expectedNativeType->equals($nativeReturnType)) {
                continue;
            }

            $errors[] = $this->error(
                sprintf(
                    'PHPDoc tag %s declares %s but the native return type is %s; expected %s.',
                    $tag->tagName,
                    $tag->type->describe(VerbosityLevel::typeOnly()),
                    $nativeReturnType->describe(VerbosityLevel::typeOnly()),
                    $expectedDescription,
                ),
                'yumemi.docTagType',
                $line,
            );
        }

        return $errors;
    }

    private function invalidTagError(YumemiDocTag $tag, int $line): IdentifierRuleError
    {
        $subject = $tag->parameterName === null
            ? $tag->tagName
            : sprintf('%s for $%s', $tag->tagName, $tag->parameterName);

        return $this->error(
            sprintf(
                'PHPDoc tag %s has invalid %s: %s',
                $subject,
                $tag->errorKind ?? YumemiDocTag::ERROR_SYNTAX,
                $tag->errorReason ?? 'the tag could not be parsed.',
            ),
            $tag->errorKind === YumemiDocTag::ERROR_TYPE
                ? 'yumemi.docTagType'
                : 'yumemi.docTagSyntax',
            $line,
        );
    }

    /**
     * @return array{Type, string}
     */
    private function expectedNativeType(Type $type): array
    {
        return match (true) {
            $type instanceof UnitIntegerType => [new IntegerType(), 'int'],
            $type instanceof UnitFloatType => [new FloatType(), 'float'],
            $type instanceof QuantityType => [new ObjectType(Quantity::class), Quantity::class],
            default => throw new \LogicException('Expected a Yumemi unit type.'),
        };
    }

    private function error(string $message, string $identifier, int $line): IdentifierRuleError
    {
        return RuleErrorBuilder::message($message)
            ->identifier($identifier)
            ->line($line)
            ->build();
    }
}
