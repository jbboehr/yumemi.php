<?php

namespace jbboehr\IudexMensurarumMysteriorum\Expr;

use jbboehr\IudexMensurarumMysteriorum\Analyzer\ExprReducer;
use jbboehr\IudexMensurarumMysteriorum\Dimension;
use jbboehr\IudexMensurarumMysteriorum\Expr;
use jbboehr\IudexMensurarumMysteriorum\Util\MathTrait;

final class Term implements Expr
{
    use MathTrait;

    public function __construct(
        public readonly Expr $value,
        public readonly int $power = 1,
    ) {
    }

    public function dimension(): Dimension
    {
        return $this->value->dimension()->pow($this->power);
    }

    public function toString(): string
    {
        if ($this->power === 1) {
            return $this->value->toString();
        }

        return $this->value->toString() . ' ^ ' . $this->power;
    }

    public function reduce(): Expr
    {
        return ExprReducer::reduce($this);
    }
}
