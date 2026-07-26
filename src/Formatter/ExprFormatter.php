<?php

/**
 * Iudex Mensurarum Mysticarum『夢見』〜ＹＵＭＥＭＩ〜
 *
 * Copyright (c) anno Domini nostri Jesu Christi MMXXVI, John Boehr & contributors
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace jbboehr\Yumemi\Formatter;

use jbboehr\Yumemi\Analyzer\ExprReducer;
use jbboehr\Yumemi\Expr;
use jbboehr\Yumemi\Expr\Compound;
use jbboehr\Yumemi\Expr\Constant;
use jbboehr\Yumemi\Expr\Term;
use jbboehr\Yumemi\Expr\Unit;
use jbboehr\Yumemi\Number\Rational;

/**
 * User-facing rendering of unit expressions.
 *
 * Prefers fraction form for negative powers (e.g. "meter / second") rather than the
 * structural tree dump from {@see Expr::toString()} (e.g. "meter * second ^ -1").
 * Quantity display and exception messages should use this formatter.
 */
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
