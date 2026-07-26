<?php

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
