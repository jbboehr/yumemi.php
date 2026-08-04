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

namespace jbboehr\Yumemi\Analyzer;

use jbboehr\Yumemi\Expr;
use jbboehr\Yumemi\Expr\Product;
use jbboehr\Yumemi\Expr\Constant;
use jbboehr\Yumemi\Expr\Power;
use jbboehr\Yumemi\Expr\Unit;

/**
 * Structural equality for unit expressions after canonical reduction.
 *
 * Unit identity is by name only; definitions and bound Units context are ignored.
 * @internal
 */
final class ExprComparer
{
    public static function areEqual(Expr $left, Expr $right): bool
    {
        return self::equalReduced(
            ExprReducer::reduce($left),
            ExprReducer::reduce($right),
        );
    }

    private static function equalReduced(Expr $left, Expr $right): bool
    {
        if ($left::class !== $right::class) {
            return false;
        }

        if ($left instanceof Constant && $right instanceof Constant) {
            return $left->value->equals($right->value);
        }

        if ($left instanceof Unit && $right instanceof Unit) {
            return $left->name === $right->name;
        }

        if ($left instanceof Power && $right instanceof Power) {
            return $left->exponent === $right->exponent
                && self::equalReduced($left->base, $right->base);
        }

        if ($left instanceof Product && $right instanceof Product) {
            if (count($left->factors) !== count($right->factors)) {
                return false;
            }

            foreach ($left->factors as $index => $expr) {
                if (!self::equalReduced($expr, $right->factors[$index])) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }
}
