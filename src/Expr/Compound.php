<?php

namespace jbboehr\Yumemi\Expr;

use jbboehr\Yumemi\Analyzer\ExprReducer;
use jbboehr\Yumemi\Dimension;
use jbboehr\Yumemi\Expr;
use jbboehr\Yumemi\Util\MathTrait;

final class Compound implements Expr
{
    use MathTrait;

    /**
     * @param list<Expr> $exprs
     */
    public function __construct(
        public readonly array $exprs,
    ) {
    }

    public function dimension(): Dimension
    {
        $dimension = Dimension::dimensionless();

        foreach ($this->exprs as $expr) {
            $dimension = $dimension->mul($expr->dimension());
        }

        return $dimension;
    }

    public function toString(): string
    {
        return implode(' * ', array_map(
            static fn (Expr $expr): string => $expr->toString(),
            $this->exprs,
        ));
    }

    public function reduce(): Expr
    {
        return ExprReducer::reduce($this);
    }
}
