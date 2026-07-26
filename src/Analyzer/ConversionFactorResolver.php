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

namespace jbboehr\Yumemi\Analyzer;

use jbboehr\Yumemi\Dimension;
use jbboehr\Yumemi\Exception\IncompatibleUnitException;
use jbboehr\Yumemi\Expr;
use jbboehr\Yumemi\Number\Rational;

final class ConversionFactorResolver
{
    private readonly DimensionResolver $dimensionResolver;

    public function __construct(
        private readonly UnitNormalizer $unitNormalizer,
        ?DimensionResolver $dimensionResolver = null,
    ) {
        $this->dimensionResolver = $dimensionResolver ?? new DimensionResolver($this->unitNormalizer);
    }

    public function compatible(Expr $left, Expr $right): bool
    {
        return $this->dimension($left)->equals($this->dimension($right));
    }

    public function dimension(Expr $expr): Dimension
    {
        return $this->dimensionResolver->resolve($expr);
    }

    public function resolve(Expr $from, Expr $to): Rational
    {
        $fromNormal = $this->unitNormalizer->normalize($from);
        $toNormal = $this->unitNormalizer->normalize($to);

        // Use already-normalized trees; do not normalize again via DimensionResolver::resolve().
        $fromDimension = DimensionResolver::resolveNormalized($fromNormal);
        $toDimension = DimensionResolver::resolveNormalized($toNormal);

        if (!$fromDimension->equals($toDimension)) {
            throw IncompatibleUnitException::create($from, $to, $fromDimension, $toDimension);
        }

        return NormalizedExpr::constant($fromNormal)->div(NormalizedExpr::constant($toNormal));
    }
}
