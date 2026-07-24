<?php

namespace jbboehr\IudexMensurarumMysteriorum\Analyzer;

use jbboehr\IudexMensurarumMysteriorum\Exception\IncompatibleUnitException;
use jbboehr\IudexMensurarumMysteriorum\Dimension;
use jbboehr\IudexMensurarumMysteriorum\Expr;
use jbboehr\IudexMensurarumMysteriorum\Number\Rational;

final class ConversionFactorResolver
{
    private readonly DimensionResolver $dimensionResolver;

    public function __construct(
        private readonly UnitNormalizer $unitNormalizer,
    ) {
        $this->dimensionResolver = new DimensionResolver($this->unitNormalizer);
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

        if (!DimensionResolver::resolveNormalized($fromNormal)->equals(DimensionResolver::resolveNormalized($toNormal))) {
            throw IncompatibleUnitException::create($from, $to);
        }

        return NormalizedExpr::constant($fromNormal)->div(NormalizedExpr::constant($toNormal));
    }
}
