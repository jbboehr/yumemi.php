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
 */
final class UnitExpression
{
    public function __construct(
        public readonly Expr $expr,
        public readonly string $displayString,
        public readonly Dimension $dimension,
    ) {
    }

    public function equals(self $other): bool
    {
        return ExprComparer::equal($this->expr, $other->expr);
    }

    public function sameDimension(self $other): bool
    {
        return $this->dimension->equals($other->dimension);
    }
}
