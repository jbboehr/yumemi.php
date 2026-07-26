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
use jbboehr\Yumemi\Number\Rational;

final class NormalizedExpr
{
    public static function constant(Expr $expr): Rational
    {
        $expr = ExprReducer::reduce($expr);

        if ($expr instanceof Constant) {
            return $expr->value;
        }

        if ($expr instanceof Compound) {
            foreach ($expr->exprs as $subexpr) {
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

        if (!$expr instanceof Compound) {
            return $expr;
        }

        $exprs = array_values(array_filter(
            $expr->exprs,
            static fn (Expr $subexpr): bool => !$subexpr instanceof Constant,
        ));

        if (count($exprs) === 0) {
            return new Constant(1);
        }

        if (count($exprs) === 1) {
            return $exprs[0];
        }

        return new Compound($exprs);
    }
}
