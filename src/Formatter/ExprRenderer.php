<?php

/**
 * +--------------------------------------------------------------------------------------------------------------+
 * |        *                 .                         *                  .                         *            |
 * |   .              *                      .                    *                      .                        |
 * |             .                 .                  *                         .                 *               |
 * -      *                    .             *                    .                         .                     -
 *
 *                               Iudex Mensurarum Mysticarum『夢見』〜ＹＵＭＥＭＩ〜
 *
 * -                                          .----------------.                                                  -
 * |                                      .--'        __        '--.                                              |
 * |                                  .--'          .'  '.          '--.                                          |
 * |                             .---'            .'      '.            '---.                                     |
 * +--------------------------------------------------------------------------------------------------------------+
 *
 * Copyright (c) anno Domini nostri Jesu Christi MMXXVI, John Boehr & contributors
 *
 * SPDX-License-Identifier: AGPL-3.0-only WITH romic-exception
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License version 3,
 * as published by the Free Software Foundation, together with the Romic
 * Exception (an additional permission under section 7 of that license).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * and the Romic Exception along with this program.  If not, see
 * <http://www.gnu.org/licenses/> and the LICENSE_EXCEPTION file.
 */

namespace jbboehr\Yumemi\Formatter;

use jbboehr\Yumemi\Analyzer\ExprReducer;
use jbboehr\Yumemi\Expr;
use jbboehr\Yumemi\Expr\Compound;
use jbboehr\Yumemi\Expr\Constant;
use jbboehr\Yumemi\Expr\Term;
use jbboehr\Yumemi\Expr\Unit;
use jbboehr\Yumemi\Number\Rational;

/** @internal */
final class ExprRenderer
{
    private const SUPERSCRIPT_DIGITS = [
        '0' => '⁰',
        '1' => '¹',
        '2' => '²',
        '3' => '³',
        '4' => '⁴',
        '5' => '⁵',
        '6' => '⁶',
        '7' => '⁷',
        '8' => '⁸',
        '9' => '⁹',
        '-' => '⁻',
    ];

    /**
     * @param callable(string): string $unitNameFormatter
     */
    public static function format(
        Expr $expr,
        FormatOptions $options = new FormatOptions(),
        ?callable $unitNameFormatter = null,
    ): string {
        $constant = new Rational(1);
        $numerator = [];
        $denominator = [];

        self::collect(
            ExprReducer::reduce($expr),
            $options,
            $unitNameFormatter ?? static fn (string $name): string => $name,
            $constant,
            $numerator,
            $denominator,
        );

        if ($numerator === [] && $denominator === []) {
            return self::formatDimensionless($constant, $options);
        }

        $parts = [];
        if (!$constant->isOne()) {
            $parts[] = $constant->toString();
        }

        $parts = array_merge($parts, $numerator);
        if ($parts === []) {
            $parts[] = '1';
        }

        $formatted = implode(self::multiplicationSign($options), $parts);

        if (count($denominator) === 1) {
            return $formatted . ' / ' . $denominator[0];
        }

        if (count($denominator) > 1) {
            return $formatted . ' / (' . implode(self::multiplicationSign($options), $denominator) . ')';
        }

        return $formatted;
    }

    /**
     * @param callable(string): string $unitNameFormatter
     * @param list<string>             $numerator
     * @param list<string>             $denominator
     */
    private static function collect(
        Expr $expr,
        FormatOptions $options,
        callable $unitNameFormatter,
        Rational &$constant,
        array &$numerator,
        array &$denominator,
    ): void {
        if ($expr instanceof Compound) {
            foreach ($expr->exprs as $subexpr) {
                self::collect($subexpr, $options, $unitNameFormatter, $constant, $numerator, $denominator);
            }

            return;
        }

        if ($expr instanceof Constant) {
            $constant = $constant->mul($expr->value);
            return;
        }

        if ($expr instanceof Term) {
            self::collectTerm($expr, $options, $unitNameFormatter, $constant, $numerator, $denominator);
            return;
        }

        if ($expr instanceof Unit) {
            $numerator[] = $unitNameFormatter($expr->name);
            return;
        }

        throw new \LogicException('Cannot format expression of type ' . $expr::class);
    }

    /**
     * @param callable(string): string $unitNameFormatter
     * @param list<string>             $numerator
     * @param list<string>             $denominator
     */
    private static function collectTerm(
        Term $term,
        FormatOptions $options,
        callable $unitNameFormatter,
        Rational &$constant,
        array &$numerator,
        array &$denominator,
    ): void {
        if ($term->value instanceof Constant) {
            $constant = $constant->mul($term->value->value->pow($term->power));
            return;
        }

        $factor = $term->value instanceof Unit
            ? $unitNameFormatter($term->value->name)
            : $term->value->toString();
        $factor = self::formatPowered($factor, abs($term->power), $options);

        if ($term->power < 0) {
            $denominator[] = $factor;
            return;
        }

        $numerator[] = $factor;
    }

    private static function formatPowered(string $expr, int $power, FormatOptions $options): string
    {
        if ($power === 1) {
            return $expr;
        }

        if ($options->typography === Typography::Unicode) {
            return $expr . strtr((string) $power, self::SUPERSCRIPT_DIGITS);
        }

        return $expr . ' ^ ' . $power;
    }

    private static function formatDimensionless(Rational $constant, FormatOptions $options): string
    {
        return match ($options->dimensionless) {
            DimensionlessStyle::One => $constant->toString(),
            DimensionlessStyle::Word => $constant->isOne()
                ? 'dimensionless'
                : $constant->toString() . self::multiplicationSign($options) . 'dimensionless',
            DimensionlessStyle::Empty => $constant->isOne() ? '' : $constant->toString(),
        };
    }

    private static function multiplicationSign(FormatOptions $options): string
    {
        return $options->typography === Typography::Unicode ? ' · ' : ' * ';
    }
}
