<?php

namespace jbboehr\IudexMensurarumMysteriorum\PHPStan;

use jbboehr\IudexMensurarumMysteriorum\Analyzer\ExprComparer;
use jbboehr\IudexMensurarumMysteriorum\Dimension;
use jbboehr\IudexMensurarumMysteriorum\Expr;

/**
 * A successfully parsed unit expression for PHPStan types.
 *
 * Holds the reduced IMM expression plus display helpers. Number kind (int/float)
 * is tracked separately by PHPStan types that wrap this value.
 *
 * {@see equals()} is structural (same reduced symbols). {@see equivalent()} compares
 * catalog-normalized forms so definitionally identical units match for assignment,
 * e.g. kilometer ≡ 1000 * meter ≡ 100000 * centimeter, newton ≡ kg·m/s².
 * Same dimension alone is not enough (meter ≉ foot).
 */
final class UnitExpression
{
    public function __construct(
        public readonly Expr $expr,
        public readonly string $displayString,
        public readonly Dimension $dimension,
        public readonly Expr $normalizedExpr,
    ) {
    }

    /**
     * Structural equality of the reduced symbolic expressions.
     */
    public function equals(self $other): bool
    {
        return ExprComparer::equal($this->expr, $other->expr);
    }

    /**
     * Definitional equality after expanding unit definitions (exact scale match).
     */
    public function equivalent(self $other): bool
    {
        return ExprComparer::equal($this->normalizedExpr, $other->normalizedExpr);
    }

    public function sameDimension(self $other): bool
    {
        return $this->dimension->equals($other->dimension);
    }
}
