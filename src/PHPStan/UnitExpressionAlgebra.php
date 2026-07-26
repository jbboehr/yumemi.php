<?php

namespace jbboehr\IudexMensurarumMysteriorum\PHPStan;

use jbboehr\IudexMensurarumMysteriorum\Formatter\ExprFormatter;

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
