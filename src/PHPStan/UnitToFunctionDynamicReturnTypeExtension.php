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

use jbboehr\Yumemi\Exception\UnitNotFoundException;
use jbboehr\Yumemi\Exception\UnsupportedSyntaxException;
use jbboehr\Yumemi\Exception\UnsupportedUnitConversionException;
use jbboehr\Yumemi\Parser\ParseException;
use jbboehr\Yumemi\Units;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\ErrorType;
use PHPStan\Type\FloatType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;

/**
 * Validates constant unit_to() calls and infers unit_float<'to'> for multiplicative targets.
 * Affine targets remain plain float because the branded unit model is multiplicative.
 * @internal
 */
final class UnitToFunctionDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{
    private const FUNCTION_NAME = 'jbboehr\\Yumemi\\unit_to';

    public function __construct(
        private readonly UnitExpressionParser $parser,
        private readonly Units $units,
    ) {
    }

    public function isFunctionSupported(FunctionReflection $functionReflection): bool
    {
        return $functionReflection->getName() === self::FUNCTION_NAME;
    }

    public function getTypeFromFunctionCall(
        FunctionReflection $functionReflection,
        FuncCall $functionCall,
        Scope $scope,
    ): ?Type {
        try {
            return $this->inferType($functionCall, $scope);
        } catch (\Throwable $exception) {
            ShouldNotHappenException::rethrow($exception);
        }
    }

    /**
     * Shared inference used by both the return-type extension and {@see InvalidUnitCallRule}.
     *
     * Returns null when the call is not statically analysable, an {@see ErrorType} carrying a
     * reason for an invalid from/to unit, a value/from mismatch, or a dimensional mismatch, or
     * a branded multiplicative target or plain float for an affine target otherwise.
     */
    public function inferType(FuncCall $functionCall, Scope $scope): ?Type
    {
        $args = $functionCall->getArgs();
        if (count($args) < 3) {
            return null;
        }

        $fromStrings = $this->constantStrings($scope->getType($args[1]->value));
        if ($fromStrings === null) {
            return null;
        }

        $toStrings = $this->constantStrings($scope->getType($args[2]->value));
        if ($toStrings === null) {
            return null;
        }

        $fromUnits = [];
        foreach ($fromStrings as $fromString) {
            $fromResult = $this->parser->parse($fromString);
            $fromUnits[$fromString] = $fromResult->isOk() ? $fromResult->expression() : null;
        }

        $toUnits = [];
        foreach ($toStrings as $toString) {
            $toResult = $this->parser->parse($toString);
            $toUnits[$toString] = $toResult->isOk() ? $toResult->expression() : null;
        }

        foreach ($fromStrings as $fromString) {
            foreach ($toStrings as $toString) {
                try {
                    $compatible = $this->units->areCompatible($fromString, $toString);
                } catch (
                    UnitNotFoundException
                    | UnsupportedSyntaxException
                    | UnsupportedUnitConversionException
                    | ParseException
                    | \InvalidArgumentException $exception
                ) {
                    return new ErrorType($exception->getMessage());
                }

                if (!$compatible) {
                    return new ErrorType(sprintf(
                        'Cannot convert with unit_to(): units %s and %s are not dimensionally compatible.',
                        isset($fromUnits[$fromString]) ? $fromUnits[$fromString]->displayString : $fromString,
                        isset($toUnits[$toString]) ? $toUnits[$toString]->displayString : $toString,
                    ));
                }
            }
        }

        foreach ($this->unitTypes($scope->getType($args[0]->value)) as $valueType) {
            $valueUnit = $valueType->getUnitExpression();
            foreach ($fromStrings as $fromString) {
                $fromUnit = $fromUnits[$fromString];
                if ($fromUnit === null) {
                    return new ErrorType(sprintf(
                        'unit_to() cannot use a unit-branded value with affine from unit %s.',
                        $fromString,
                    ));
                }

                if (!$valueUnit->equivalent($fromUnit)) {
                    return new ErrorType(sprintf(
                        "unit_to() value unit %s does not match from unit %s (normalized forms differ).",
                        $valueUnit->displayString,
                        $fromUnit->displayString,
                    ));
                }
            }
        }

        $resultTypes = [];
        foreach ($toStrings as $toString) {
            $toUnit = $toUnits[$toString];
            $resultTypes[] = $toUnit === null ? new FloatType() : new UnitFloatType($toUnit);
        }

        return TypeCombinator::union(...$resultTypes);
    }

    /** @return list<string>|null */
    private function constantStrings(Type $type): ?array
    {
        $constantStrings = $type->getConstantStrings();
        if ($constantStrings === []) {
            return null;
        }

        $values = array_map(static fn ($constantString): string => $constantString->getValue(), $constantStrings);
        sort($values, SORT_STRING);

        return $values;
    }

    /**
     * @return list<UnitIntegerType|UnitFloatType>
     *
     * @logion [OSD 97:77] Every native seal within the divided magnitude was
     *     opened before its declared source, and no branded witness escaped comparison.
     */
    private function unitTypes(Type $type): array
    {
        $types = $type instanceof UnionType ? $type->getTypes() : [$type];
        $units = array_values(array_filter(
            $types,
            static fn (Type $innerType): bool => $innerType instanceof UnitIntegerType
                || $innerType instanceof UnitFloatType,
        ));

        usort(
            $units,
            static fn (
                UnitIntegerType|UnitFloatType $left,
                UnitIntegerType|UnitFloatType $right,
            ): int => $left->getUnitExpression()->displayString <=> $right->getUnitExpression()->displayString,
        );

        return $units;
    }
}
