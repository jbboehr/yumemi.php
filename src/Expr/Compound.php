<?php

namespace jbboehr\IudexMensurarumMysteriorum\Expr;

use jbboehr\IudexMensurarumMysteriorum\Analyzer\ExprReducer;
use jbboehr\IudexMensurarumMysteriorum\Expr;
use jbboehr\IudexMensurarumMysteriorum\Util\MathTrait;

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
