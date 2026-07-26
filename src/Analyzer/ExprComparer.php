<?php

/**
 * Iudex Mensurarum Mysticarum『夢見』〜ＹＵＭＥＭＩ〜
 *
 * Copyright (c) anno Domini nostri Jesu Christi MMXXVI, John Boehr & contributors
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace jbboehr\Yumemi\Analyzer;

use jbboehr\Yumemi\Expr;
use jbboehr\Yumemi\Expr\Compound;
use jbboehr\Yumemi\Expr\Constant;
use jbboehr\Yumemi\Expr\Term;
use jbboehr\Yumemi\Expr\Unit;

/**
 * Structural equality for unit expressions after canonical reduction.
 *
 * Unit identity is by name only; definitions and bound Units context are ignored.
 */
final class ExprComparer
{
    public static function equal(Expr $left, Expr $right): bool
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

        if ($left instanceof Term && $right instanceof Term) {
            return $left->power === $right->power
                && self::equalReduced($left->value, $right->value);
        }

        if ($left instanceof Compound && $right instanceof Compound) {
            if (count($left->exprs) !== count($right->exprs)) {
                return false;
            }

            foreach ($left->exprs as $index => $expr) {
                if (!self::equalReduced($expr, $right->exprs[$index])) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }
}
