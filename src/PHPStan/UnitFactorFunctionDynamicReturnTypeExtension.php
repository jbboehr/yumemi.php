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

use jbboehr\Yumemi\Exception\IncompatibleUnitException;
use jbboehr\Yumemi\Exception\NonMultiplicativeConversionException;
use jbboehr\Yumemi\Units;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

/**
 * Validates constant unit_factor() calls and infers unit_float<'to / from'>.
 */
final class UnitFactorFunctionDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{
    private const FUNCTION_NAME = 'jbboehr\\Yumemi\\unit_factor';

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
     * Returns null when the call is not statically analysable, an {@see ErrorType} for an
     * invalid factor, or a float brand carrying the structural target/source quotient.
     */
    public function inferType(FuncCall $functionCall, Scope $scope): ?Type
    {
        $args = $functionCall->getArgs();
        if (count($args) < 2) {
            return null;
        }

        $fromStrings = $this->constantStrings($scope->getType($args[0]->value));
        if ($fromStrings === null) {
            return null;
        }

        $toStrings = $this->constantStrings($scope->getType($args[1]->value));
        if ($toStrings === null) {
            return null;
        }

        $fromUnits = [];
        foreach ($fromStrings as $fromString) {
            $fromResult = $this->parser->parse($fromString);
            if (!$fromResult->isOk()) {
                return new ErrorType($fromResult->errorMessage() ?? 'Invalid source unit expression.');
            }

            $fromUnits[] = $fromResult->expression();
        }

        $toUnits = [];
        foreach ($toStrings as $toString) {
            $toResult = $this->parser->parse($toString);
            if (!$toResult->isOk()) {
                return new ErrorType($toResult->errorMessage() ?? 'Invalid target unit expression.');
            }

            $toUnits[] = $toResult->expression();
        }

        $resultTypes = [];
        foreach ($fromUnits as $fromUnit) {
            foreach ($toUnits as $toUnit) {
                try {
                    $this->units->conversionFactor($fromUnit->expr, $toUnit->expr);
                } catch (IncompatibleUnitException|NonMultiplicativeConversionException $exception) {
                    return new ErrorType('Cannot calculate unit_factor(): ' . $exception->getMessage());
                }

                $resultTypes[] = new UnitFloatType(UnitExpressionAlgebra::divide($toUnit, $fromUnit));
            }
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
}
