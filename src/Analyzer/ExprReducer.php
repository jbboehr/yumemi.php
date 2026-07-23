<?php

namespace jbboehr\IudexMensurarumMysteriorum\Analyzer;

use jbboehr\IudexMensurarumMysteriorum\Expr;
use jbboehr\IudexMensurarumMysteriorum\Expr\Compound;
use jbboehr\IudexMensurarumMysteriorum\Expr\Constant;
use jbboehr\IudexMensurarumMysteriorum\Expr\Term;
use jbboehr\IudexMensurarumMysteriorum\Expr\Unit;

final class ExprReducer
{
    public static function reduce(Expr $expr): Expr
    {
        $state = new ReductionState();
        self::collect($expr, 1, $state);

        return self::build($state);
    }

    private static function build(ReductionState $state): Expr
    {
        ksort($state->units);

        $exprs = [];

        if (!$state->constant->isOne() || count($state->units) === 0) {
            $exprs[] = new Constant($state->constant);
        }

        foreach ($state->units as $name => $power) {
            if ($power === 0) {
                continue;
            }

            $unit = new Unit($name);
            $exprs[] = $power === 1 ? $unit : new Term($unit, $power);
        }

        if (count($exprs) === 1) {
            return $exprs[0];
        }

        return new Compound($exprs);
    }

    private static function collect(Expr $expr, int $power, ReductionState $state): void
    {
        if ($power === 0) {
            return;
        }

        if ($expr instanceof Compound) {
            foreach ($expr->exprs as $subexpr) {
                self::collect($subexpr, $power, $state);
            }

            return;
        }

        if ($expr instanceof Term) {
            self::collect($expr->value, $power * $expr->power, $state);
            return;
        }

        if ($expr instanceof Constant) {
            $state->constant = $state->constant->mul($expr->value->pow($power));
            return;
        }

        if ($expr instanceof Unit) {
            $state->units[$expr->name] = ($state->units[$expr->name] ?? 0) + $power;

            if ($state->units[$expr->name] === 0) {
                unset($state->units[$expr->name]);
            }

            return;
        }

        throw new \LogicException('Cannot reduce expression of type ' . $expr::class);
    }
}
