<?php

namespace jbboehr\Yumemi\Analyzer;

use jbboehr\Yumemi\Expr;
use jbboehr\Yumemi\Expr\Compound;
use jbboehr\Yumemi\Expr\Constant;
use jbboehr\Yumemi\Expr\Term;
use jbboehr\Yumemi\Number\Rational;

final class NormalizedExpr
{
    public static function constant(Expr $expr): Rational
    {
        $expr = ExprReducer::reduce($expr);

        if ($expr instanceof Constant) {
            return $expr->value;
        }

        if ($expr instanceof Compound) {
            foreach ($expr->exprs as $subexpr) {
                if ($subexpr instanceof Constant) {
                    return $subexpr->value;
                }
            }
        }

        return new Rational(1);
    }

    public static function withoutConstant(Expr $expr): Expr
    {
        $expr = ExprReducer::reduce($expr);

        if ($expr instanceof Constant) {
            return new Constant(1);
        }

        if (!$expr instanceof Compound) {
            return $expr;
        }

        $exprs = array_values(array_filter(
            $expr->exprs,
            static fn (Expr $subexpr): bool => !$subexpr instanceof Constant,
        ));

        if (count($exprs) === 0) {
            return new Constant(1);
        }

        if (count($exprs) === 1) {
            return $exprs[0];
        }

        return new Compound($exprs);
    }
}
