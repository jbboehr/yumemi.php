<?php

namespace jbboehr\Yumemi\Expr;

use GMP;
use jbboehr\Yumemi\Analyzer\ExprReducer;
use jbboehr\Yumemi\Dimension;
use jbboehr\Yumemi\Expr;
use jbboehr\Yumemi\Number\Rational;
use jbboehr\Yumemi\Util\MathTrait;

final class Constant implements Expr
{
    use MathTrait;

    public readonly Rational $value;

    public function __construct(int|GMP|Rational $value = 1)
    {
        $this->value = $value instanceof Rational ? $value : Rational::fromInteger($value);
    }

    public function dimension(): Dimension
    {
        return Dimension::dimensionless();
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
