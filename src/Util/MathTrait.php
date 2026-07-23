<?php

namespace jbboehr\IudexMensurarumMysteriorum\Util;

use jbboehr\IudexMensurarumMysteriorum\Analyzer\ExprReducer;
use jbboehr\IudexMensurarumMysteriorum\Expr;

trait MathTrait
{
    public function div(Expr $expr): Expr
    {
        return ExprReducer::reduce(new Expr\Compound([
            $this,
            new Expr\Term($expr, -1),
        ]));
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
