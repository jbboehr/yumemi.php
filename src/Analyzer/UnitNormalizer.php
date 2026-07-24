<?php

namespace jbboehr\IudexMensurarumMysteriorum\Analyzer;

use jbboehr\IudexMensurarumMysteriorum\Expr;
use jbboehr\IudexMensurarumMysteriorum\Expr\Compound;
use jbboehr\IudexMensurarumMysteriorum\Expr\Term;
use jbboehr\IudexMensurarumMysteriorum\Expr\Unit;

final class UnitNormalizer
{
    public function normalize(Expr $expr): Expr
    {
        return ExprReducer::reduce($this->substitute(ExprReducer::reduce($expr)));
    }

    private function substitute(Expr $expr): Expr
    {
        if ($expr instanceof Compound) {
            return new Compound(array_map(
                fn (Expr $subexpr): Expr => $this->substitute($subexpr),
                $expr->exprs,
            ));
        }

        if ($expr instanceof Term) {
            return new Term($this->substitute($expr->value), $expr->power);
        }

        if ($expr instanceof Unit && !$expr->isBase()) {
            return $this->substitute($expr->definition ?? throw new \LogicException('Derived unit definition missing.'));
        }

        return $expr;
    }
}
