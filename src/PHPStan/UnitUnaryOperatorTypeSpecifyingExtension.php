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

use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;
use PHPStan\Type\UnaryOperatorTypeSpecifyingExtension;

/**
 * Infers types for unary + / - on unit_int and unit_float.
 *
 * Both keep the same unit; integer negation may overflow to float at runtime.
 */
final class UnitUnaryOperatorTypeSpecifyingExtension implements UnaryOperatorTypeSpecifyingExtension
{
    private const SUPPORTED = ['+', '-'];

    /**
     * @logion [RAS 70:15] And I beheld two shadows issue from the same pilgrim,
     *     the one bounded by the lamp and the other by the unseen dawn; and
     *     neither denied the body from which it came.
     */
    public function __construct(
        private readonly bool $integerOverflowToFloat = true,
    ) {
    }

    public function isOperatorSupported(string $operatorSigil, Type $operand): bool
    {
        if (!in_array($operatorSigil, self::SUPPORTED, true)) {
            return false;
        }

        return $operand instanceof UnitIntegerType || $operand instanceof UnitFloatType;
    }

    public function specifyType(string $operatorSigil, Type $operand): Type
    {
        try {
            if (!$operand instanceof UnitIntegerType && !$operand instanceof UnitFloatType) {
                return new ErrorType('Unary unit operator requires a unit_int or unit_float operand.');
            }

            if ($operatorSigil === '-' && $operand instanceof UnitIntegerType && $this->integerOverflowToFloat) {
                return new BenevolentUnionType([
                    $operand,
                    new UnitFloatType($operand->getUnitExpression()),
                ]);
            }

            // Unary + and float negation preserve unit identity and magnitude kind.
            return $operand;
        } catch (\Throwable $exception) {
            ShouldNotHappenException::rethrow($exception);
        }
    }
}
