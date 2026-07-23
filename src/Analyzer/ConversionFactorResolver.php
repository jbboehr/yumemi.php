<?php

namespace jbboehr\IudexMensurarumMysteriorum\Analyzer;

use jbboehr\IudexMensurarumMysteriorum\Exception\IncompatibleUnitException;
use jbboehr\IudexMensurarumMysteriorum\Expr;
use jbboehr\IudexMensurarumMysteriorum\Number\Rational;

final class ConversionFactorResolver
{
    public function __construct(
        private readonly UnitNormalizer $unitNormalizer,
    ) {
    }

    public function compatible(Expr $left, Expr $right): bool
    {
        return $this->dimension($left)->toString() === $this->dimension($right)->toString();
    }

    public function dimension(Expr $expr): Expr
    {
        return NormalizedExpr::withoutConstant($this->unitNormalizer->normalize($expr));
    }

    public function resolve(Expr $from, Expr $to): Rational
    {
        $fromNormal = $this->unitNormalizer->normalize($from);
        $toNormal = $this->unitNormalizer->normalize($to);

        if (NormalizedExpr::withoutConstant($fromNormal)->toString() !== NormalizedExpr::withoutConstant($toNormal)->toString()) {
            throw IncompatibleUnitException::create($from, $to);
        }

        return NormalizedExpr::constant($fromNormal)->div(NormalizedExpr::constant($toNormal));
    }
}
