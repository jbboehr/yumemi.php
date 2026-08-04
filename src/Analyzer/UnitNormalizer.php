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

use jbboehr\Yumemi\Exception\LogicException;
use jbboehr\Yumemi\Expr;
use jbboehr\Yumemi\Expr\Product;
use jbboehr\Yumemi\Expr\Power;
use jbboehr\Yumemi\Expr\Unit;

/**
 * @internal
 */
final class UnitNormalizer
{
    public function normalize(Expr $expr): Expr
    {
        return ExprReducer::reduce($this->substitute(ExprReducer::reduce($expr)));
    }

    private function substitute(Expr $expr): Expr
    {
        if ($expr instanceof Product) {
            return new Product(array_map(
                fn (Expr $subexpr): Expr => $this->substitute($subexpr),
                $expr->factors,
            ));
        }

        if ($expr instanceof Power) {
            return new Power($this->substitute($expr->base), $expr->exponent);
        }

        if ($expr instanceof Unit && !$expr->isBase()) {
            return $this->substitute($expr->definition ?? throw new LogicException('Derived unit definition missing.'));
        }

        return $expr;
    }
}
