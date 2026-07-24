<?php

namespace jbboehr\IudexMensurarumMysteriorum\Formatter;

use jbboehr\IudexMensurarumMysteriorum\Analyzer\ExprReducer;
use jbboehr\IudexMensurarumMysteriorum\Expr;
use jbboehr\IudexMensurarumMysteriorum\Expr\Compound;
use jbboehr\IudexMensurarumMysteriorum\Expr\Constant;
use jbboehr\IudexMensurarumMysteriorum\Expr\Term;
use jbboehr\IudexMensurarumMysteriorum\Expr\Unit;
use jbboehr\IudexMensurarumMysteriorum\Number\Rational;

final class ExprFormatter
{
    public static function format(Expr $expr): string
    {
        $constant = new Rational(1);
        $numerator = [];
        $denominator = [];

        self::collect(ExprReducer::reduce($expr), $constant, $numerator, $denominator);

        if ($numerator === [] && $denominator === []) {
            return $constant->toString();
        }

        $parts = [];
        if (!$constant->isOne()) {
            $parts[] = $constant->toString();
        }

        $parts = array_merge($parts, $numerator);

        if ($parts === []) {
            $parts[] = '1';
        }

        $formatted = implode(' * ', $parts);

        if (count($denominator) === 1) {
            return $formatted . ' / ' . $denominator[0];
        }

        if (count($denominator) > 1) {
            return $formatted . ' / (' . implode(' * ', $denominator) . ')';
        }

        return $formatted;
    }

    /**
     * @param list<string> $numerator
     * @param list<string> $denominator
     */
    private static function collect(Expr $expr, Rational &$constant, array &$numerator, array &$denominator): void
    {
        if ($expr instanceof Compound) {
            foreach ($expr->exprs as $subexpr) {
                self::collect($subexpr, $constant, $numerator, $denominator);
            }

            return;
        }

        if ($expr instanceof Constant) {
            $constant = $constant->mul($expr->value);
            return;
        }

        if ($expr instanceof Term) {
            self::collectTerm($expr, $constant, $numerator, $denominator);
            return;
        }

        if ($expr instanceof Unit) {
            $numerator[] = $expr->toString();
            return;
        }

        throw new \LogicException('Cannot format expression of type ' . $expr::class);
    }

    /**
     * @param list<string> $numerator
     * @param list<string> $denominator
     */
    private static function collectTerm(Term $term, Rational &$constant, array &$numerator, array &$denominator): void
    {
        if ($term->value instanceof Constant) {
            $constant = $constant->mul($term->value->value->pow($term->power));
            return;
        }

        $factor = self::formatPowered($term->value, abs($term->power));

        if ($term->power < 0) {
            $denominator[] = $factor;
            return;
        }

        $numerator[] = $factor;
    }

    private static function formatPowered(Expr $expr, int $power): string
    {
        $formatted = $expr->toString();

        if ($power === 1) {
            return $formatted;
        }

        return $formatted . ' ^ ' . $power;
    }
}
