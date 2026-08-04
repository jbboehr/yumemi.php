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
use jbboehr\Yumemi\Number\Rational;

/**
 * @internal
 */
final class NormalizedExpr
{
    public static function constant(Expr $expr): Rational
    {
        $expr = ExprReducer::reduce($expr);

        if ($expr instanceof Constant) {
            return $expr->value;
        }

        if ($expr instanceof Product) {
            foreach ($expr->factors as $subexpr) {
                if ($subexpr instanceof Constant) {
                    return $subexpr->value;
                }
            }
        }

        return new Rational(1);
    }

    public static function withoutConstant(Expr $expr): Expr
    {
        $expr = ExprReducer::reduce($expr);

        if ($expr instanceof Constant) {
            return new Constant(1);
        }

        if (!$expr instanceof Product) {
            return $expr;
        }

        $exprs = array_values(array_filter(
            $expr->factors,
            static fn (Expr $subexpr): bool => !$subexpr instanceof Constant,
        ));

        if (count($exprs) === 0) {
            return new Constant(1);
        }

        if (count($exprs) === 1) {
            return $exprs[0];
        }

        return new Product($exprs);
    }
}
