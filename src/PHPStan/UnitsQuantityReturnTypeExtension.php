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

use jbboehr\Yumemi\Units;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

/**
 * Infers Quantity<'meter'> from Units::quantity($value, 'meter') when the unit type is a finite
 * set of constant strings.
 *
 * The PHPStan-configured registry is authoritative: every statically known target must parse, while
 * a genuinely dynamic string falls back to the native `Quantity` return. A branded integer input
 * must already be expressed in every possible target unit because this method does not convert it.
 */
final class UnitsQuantityReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    public function __construct(
        private readonly UnitExpressionParser $parser,
    ) {
    }

    public function getClass(): string
    {
        return Units::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'quantity';
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): ?Type {
        return $this->inferType($methodCall, $scope);
    }

    /**
     * Shared inference used by the return-type extension and construction diagnostic rule.
     */
    public function inferType(MethodCall $methodCall, Scope $scope): ?Type
    {
        $args = $methodCall->getArgs();
        if (count($args) < 2) {
            return null;
        }

        $constantStrings = $scope->getType($args[1]->value)->getConstantStrings();
        if ($constantStrings === []) {
            return null;
        }

        $targetUnits = [];
        foreach ($constantStrings as $constantString) {
            $parsed = $this->parser->parse($constantString->getValue());
            if (!$parsed->isOk()) {
                return new ErrorType($parsed->errorMessage() ?? 'Invalid unit expression.');
            }

            $targetUnits[] = $parsed->expression();
        }

        $valueType = $scope->getType($args[0]->value);

        if ($valueType instanceof UnitIntegerType) {
            $valueUnit = $valueType->getUnitExpression();
            foreach ($targetUnits as $targetUnit) {
                if (!$valueUnit->equivalent($targetUnit)) {
                    return new ErrorType(sprintf(
                        'Units::quantity() value unit %s does not match target unit %s (normalized forms differ).',
                        $valueUnit->displayString,
                        $targetUnit->displayString,
                    ));
                }
            }
        }

        return TypeCombinator::union(...array_map(
            static fn (UnitExpression $targetUnit): QuantityType => new QuantityType($targetUnit),
            $targetUnits,
        ));
    }
}
