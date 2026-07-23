<?php

namespace jbboehr\IudexMensurarumMysteriorum\Expr;

use GMP;
use jbboehr\IudexMensurarumMysteriorum\Analyzer\ExprReducer;
use jbboehr\IudexMensurarumMysteriorum\Expr;
use jbboehr\IudexMensurarumMysteriorum\Number\Rational;
use jbboehr\IudexMensurarumMysteriorum\Util\MathTrait;

final class Constant implements Expr
{
    use MathTrait;

    public readonly Rational $value;

    public function __construct(int|GMP|Rational $value = 1)
    {
        $this->value = $value instanceof Rational ? $value : Rational::fromInteger($value);
    }

    public function toString(): string
    {
        return $this->value->toString();
    }

    public function reduce(): Expr
    {
        return ExprReducer::reduce($this);
    }
}
