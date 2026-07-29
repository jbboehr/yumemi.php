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

namespace jbboehr\Yumemi;

use jbboehr\Yumemi\Analyzer\AstConverter;
use jbboehr\Yumemi\Analyzer\ExprReducer;
use jbboehr\Yumemi\Analyzer\NormalizedExpr;
use jbboehr\Yumemi\Analyzer\UnitConversionResolver;
use jbboehr\Yumemi\Analyzer\UnitNormalizer;
use jbboehr\Yumemi\Analyzer\UnitResolver;
use jbboehr\Yumemi\Catalog\PrefixDescriptor;
use jbboehr\Yumemi\Catalog\UnitDescriptor;
use jbboehr\Yumemi\Expr\Compound;
use jbboehr\Yumemi\Expr\Term;
use jbboehr\Yumemi\Expr\Unit;
use jbboehr\Yumemi\Formatter\ExprFormatter;
use jbboehr\Yumemi\Formatter\FormatOptions;
use jbboehr\Yumemi\Number\Rational;
use jbboehr\Yumemi\Parser\Parser;
use jbboehr\Yumemi\Registry\UnitRegistry;
use jbboehr\Yumemi\Registry\Udunits2UnitRegistry;

final class Units
{
    private static ?self $default = null;

    private readonly AstConverter $astConverter;
    private readonly UnitConversionResolver $unitConversionResolver;
    private readonly ExprFormatter $defaultFormatter;
    private readonly UnitNormalizer $unitNormalizer;
    private readonly UnitResolver $unitResolver;

    public function __construct(
        private readonly UnitRegistry $unitRegistry,
    ) {
        $this->unitResolver = new UnitResolver($this->unitRegistry);
        $this->astConverter = new AstConverter($this->unitResolver);
        $this->unitNormalizer = new UnitNormalizer();
        $this->unitConversionResolver = new UnitConversionResolver($this->unitRegistry);
        $this->defaultFormatter = new ExprFormatter($this->unitRegistry);
    }

    /**
     * Shared default context backed by the UDUNITS2 catalog.
     *
     * Repeated calls return the same instance, so quantities from separate
     * Units::default() calls can be combined. For an isolated catalog or tests,
     * construct {@see self} with an explicit registry instead.
     */
    public static function default(): self
    {
        return self::$default ??= new self(new Udunits2UnitRegistry());
    }

    public function compatible(Expr|string $left, Expr|string $right): bool
    {
        return $this->unitConversionResolver->compatible($left, $right);
    }

    public function conversionFactor(Expr|string $from, Expr|string $to): Rational
    {
        return $this->unitConversionResolver->conversionFactor($from, $to);
    }

    public function convert(int|Rational $value, Expr|string $from, Expr|string $to): Rational
    {
        $value = $value instanceof Rational ? $value : new Rational($value);

        return $this->unitConversionResolver->conversion($from, $to)->apply($value);
    }

    public function convertFloat(float $value, Expr|string $from, Expr|string $to): float
    {
        $conversion = $this->unitConversionResolver->conversion($from, $to);

        return $value * $conversion->scale->toFloat() + $conversion->offset->toFloat();
    }

    public function dimension(Expr|string $expr): Dimension
    {
        return $this->unitConversionResolver->dimension($expr);
    }

    public function format(Expr|string $expr, ?FormatOptions $options = null): string
    {
        $symbolicExpr = is_string($expr)
            ? ExprReducer::reduce(AstConverter::symbolic()->convert(Parser::parseString($expr)))
            : $expr;

        return $this->formatter($options)->format($symbolicExpr);
    }

    public function formatter(?FormatOptions $options = null): ExprFormatter
    {
        return $options === null
            ? $this->defaultFormatter
            : new ExprFormatter($this->unitRegistry, $options);
    }

    /**
     * Describe an exact unit spelling or a dynamically prefixed name without parsing compound expressions.
     */
    public function describe(string $name): ?UnitDescriptor
    {
        return $this->unitRegistry->describe($name);
    }

    /**
     * Describe an exact prefix name or symbol.
     */
    public function describePrefix(string $name): ?PrefixDescriptor
    {
        return $this->unitRegistry->describePrefix($name);
    }

    public function normalize(Expr|string $expr): Expr
    {
        return $this->unitNormalizer->normalize($this->expr($expr));
    }

    public function parse(string $input): Expr
    {
        return $this->bindContext(
            ExprReducer::reduce($this->astConverter->convert(Parser::parseString($input))),
        );
    }

    /**
     * Explicit alias for parsing a unit expression.
     */
    public function parseUnit(string $input): Expr
    {
        return $this->parse($input);
    }

    /**
     * Parse a quantity, folding explicit constants into its exact magnitude.
     */
    public function parseQuantity(string $input): Quantity
    {
        $symbolicExpr = ExprReducer::reduce(
            AstConverter::symbolic()->convert(Parser::parseString($input)),
        );
        $unit = NormalizedExpr::withoutConstant($symbolicExpr);

        return new Quantity(
            NormalizedExpr::constant($symbolicExpr),
            $unit,
            $this,
            $this->bindContext(ExprReducer::reduce($this->resolveSymbolicExpr($unit))),
        );
    }

    public function quantity(int|Rational $value, Expr|string $unit): Quantity
    {
        return new Quantity($value, $unit, $this);
    }

    /**
     * Resolve a unit name through the catalog.
     *
     * This is the supported way for application code to obtain {@see Unit} values.
     * Constructing {@see Unit} directly is internal and may not be dimensionable.
     */
    public function unit(string $name): Expr
    {
        return $this->bindContext(
            ExprReducer::reduce($this->unitResolver->resolveOrFail($name)),
        );
    }

    private function expr(Expr|string $expr): Expr
    {
        return is_string($expr) ? $this->parse($expr) : $expr;
    }

    private function resolveSymbolicExpr(Expr $expr): Expr
    {
        if ($expr instanceof Unit) {
            return $this->unitResolver->resolveOrFail($expr->name);
        }

        if ($expr instanceof Term) {
            return new Term($this->resolveSymbolicExpr($expr->value), $expr->power);
        }

        if ($expr instanceof Compound) {
            return new Compound(array_map(
                fn (Expr $subexpr): Expr => $this->resolveSymbolicExpr($subexpr),
                $expr->exprs,
            ));
        }

        return $expr;
    }

    /**
     * Stamp a weak Units context onto unit leaves so Unit::dimension() can fall back to the catalog.
     */
    private function bindContext(Expr $expr): Expr
    {
        if ($expr instanceof Unit) {
            return $expr->withUnits($this);
        }

        if ($expr instanceof Term) {
            return new Term($this->bindContext($expr->value), $expr->power);
        }

        if ($expr instanceof Compound) {
            return new Compound(array_map(
                fn (Expr $subexpr): Expr => $this->bindContext($subexpr),
                $expr->exprs,
            ));
        }

        return $expr;
    }
}
