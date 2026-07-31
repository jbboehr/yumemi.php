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

/**
 * Validates constant unit_to() calls and infers unit_float<'to'> for multiplicative targets.
 * Affine targets remain plain float because the branded unit model is multiplicative.
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

        $fromString = $this->constantString($scope->getType($args[1]->value));
        if ($fromString === null) {
            return null;
        }

        $toString = $this->constantString($scope->getType($args[2]->value));
        if ($toString === null) {
            return null;
        }

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

        $fromResult = $this->parser->parse($fromString);
        $toResult = $this->parser->parse($toString);
        $fromUnit = $fromResult->isOk() ? $fromResult->expression() : null;
        $toUnit = $toResult->isOk() ? $toResult->expression() : null;

        if (!$compatible) {
            return new ErrorType(sprintf(
                'Cannot convert with unit_to(): units %s and %s are not dimensionally compatible.',
                $fromResult->isOk() ? $fromResult->expression()->displayString : $fromString,
                $toResult->isOk() ? $toResult->expression()->displayString : $toString,
            ));
        }

        $valueType = $scope->getType($args[0]->value);
        if ($valueType instanceof UnitIntegerType || $valueType instanceof UnitFloatType) {
            if ($fromUnit === null) {
                return new ErrorType(sprintf(
                    'unit_to() cannot use a unit-branded value with affine from unit %s.',
                    $fromString,
                ));
            }

            $valueUnit = $valueType->getUnitExpression();
            if (!$valueUnit->equivalent($fromUnit)) {
                return new ErrorType(sprintf(
                    "unit_to() value unit %s does not match from unit %s (normalized forms differ).",
                    $valueUnit->displayString,
                    $fromUnit->displayString,
                ));
            }
        }

        if ($toUnit === null) {
            return new FloatType();
        }

        // Conversion factors are generally non-integral → always unit_float.
        return new UnitFloatType($toUnit);
    }

    private function constantString(Type $type): ?string
    {
        $constantStrings = $type->getConstantStrings();
        if (count($constantStrings) !== 1) {
            return null;
        }

        return $constantStrings[0]->getValue();
    }
}
