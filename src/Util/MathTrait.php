<?php

namespace jbboehr\Yumemi\Util;

use jbboehr\Yumemi\Analyzer\ExprComparer;
use jbboehr\Yumemi\Analyzer\ExprReducer;
use jbboehr\Yumemi\Expr;

trait MathTrait
{
    public function div(Expr $expr): Expr
    {
        return ExprReducer::reduce(new Expr\Compound([
            $this,
            new Expr\Term($expr, -1),
        ]));
    }

    public function equals(Expr $expr): bool
    {
        return ExprComparer::equal($this, $expr);
    }

    public function mul(Expr $expr): Expr
    {
        return ExprReducer::reduce(new Expr\Compound([
            $this,
            $expr,
        ]));
    }

    public function pow(int $power): Expr
    {
        return ExprReducer::reduce(new Expr\Term($this, $power));
    }
}
