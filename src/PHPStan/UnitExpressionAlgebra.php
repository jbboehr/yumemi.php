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

namespace jbboehr\Yumemi\PHPStan;

use jbboehr\Yumemi\Formatter\ExprFormatter;

/**
 * Unit algebra on {@see UnitExpression} for the static layer.
 *
 * Each operation rebuilds all four fields in lockstep — the symbolic `expr`, the display string,
 * the `dimension`, and the catalog-`normalizedExpr` — so results stay consistent with the runtime
 * engine. Shared by {@see UnitOperatorTypeSpecifyingExtension} (native `unit_int` / `unit_float`
 * arithmetic) and the `Quantity` method return-type extensions, so both layers combine units the
 * same way.
 */
final class UnitExpressionAlgebra
{
    public static function multiply(UnitExpression $left, UnitExpression $right): UnitExpression
    {
        $expr = $left->expr->mul($right->expr);
        $normalized = $left->normalizedExpr->mul($right->normalizedExpr);

        return new UnitExpression(
            $expr,
            ExprFormatter::format($expr),
            $left->dimension->mul($right->dimension),
            $normalized,
        );
    }

    public static function divide(UnitExpression $left, UnitExpression $right): UnitExpression
    {
        $expr = $left->expr->div($right->expr);
        $normalized = $left->normalizedExpr->div($right->normalizedExpr);

        return new UnitExpression(
            $expr,
            ExprFormatter::format($expr),
            $left->dimension->div($right->dimension),
            $normalized,
        );
    }

    public static function invert(UnitExpression $unit): UnitExpression
    {
        return self::power($unit, -1);
    }

    public static function power(UnitExpression $unit, int $exponent): UnitExpression
    {
        $expr = $unit->expr->pow($exponent);
        $normalized = $unit->normalizedExpr->pow($exponent);

        return new UnitExpression(
            $expr,
            ExprFormatter::format($expr),
            $unit->dimension->pow($exponent),
            $normalized,
        );
    }
}
