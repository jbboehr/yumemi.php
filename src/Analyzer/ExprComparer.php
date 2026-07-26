<?php

namespace jbboehr\Yumemi\Analyzer;

use jbboehr\Yumemi\Expr;
use jbboehr\Yumemi\Expr\Compound;
use jbboehr\Yumemi\Expr\Constant;
use jbboehr\Yumemi\Expr\Term;
use jbboehr\Yumemi\Expr\Unit;

/**
 * Structural equality for unit expressions after canonical reduction.
 *
 * Unit identity is by name only; definitions and bound Units context are ignored.
 */
final class ExprComparer
{
    public static function equal(Expr $left, Expr $right): bool
    {
        return self::equalReduced(
            ExprReducer::reduce($left),
            ExprReducer::reduce($right),
        );
    }

    private static function equalReduced(Expr $left, Expr $right): bool
    {
        if ($left::class !== $right::class) {
            return false;
        }

        if ($left instanceof Constant && $right instanceof Constant) {
            return $left->value->equals($right->value);
        }

        if ($left instanceof Unit && $right instanceof Unit) {
            return $left->name === $right->name;
        }

        if ($left instanceof Term && $right instanceof Term) {
            return $left->power === $right->power
                && self::equalReduced($left->value, $right->value);
        }

        if ($left instanceof Compound && $right instanceof Compound) {
            if (count($left->exprs) !== count($right->exprs)) {
                return false;
            }

            foreach ($left->exprs as $index => $expr) {
                if (!self::equalReduced($expr, $right->exprs[$index])) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }
}
