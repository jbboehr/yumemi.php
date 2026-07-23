<?php

namespace jbboehr\IudexMensurarumMysteriorum;

use jbboehr\IudexMensurarumMysteriorum\Analyzer\ConversionFactorResolver;
use jbboehr\IudexMensurarumMysteriorum\Analyzer\UnitNormalizer;
use jbboehr\IudexMensurarumMysteriorum\Expr\Constant;
use jbboehr\IudexMensurarumMysteriorum\Expr\Unit;
use jbboehr\IudexMensurarumMysteriorum\Number\Rational;
use jbboehr\IudexMensurarumMysteriorum\Registry\UnitRegistry;

final class Units
{
    private readonly ConversionFactorResolver $conversionFactorResolver;
    private readonly UnitNormalizer $unitNormalizer;

    public function __construct(
        private readonly UnitRegistry $unitRegistry,
    ) {
        $this->unitNormalizer = new UnitNormalizer();
        $this->conversionFactorResolver = new ConversionFactorResolver($this->unitNormalizer);
    }

    public static function default(): self
    {
        return new self(UnitRegistry::defaults());
    }

    public function compatible(Expr $left, Expr $right): bool
    {
        return $this->conversionFactorResolver->compatible($left, $right);
    }

    public function conversionFactor(Expr $from, Expr $to): Rational
    {
        return $this->conversionFactorResolver->resolve($from, $to);
    }

    public function convert(int|Rational $value, Expr $from, Expr $to): Rational
    {
        $value = $value instanceof Rational ? $value : new Rational($value);

        return $value->mul($this->conversionFactor($from, $to));
    }

    public function normalize(Expr $expr): Expr
    {
        return $this->unitNormalizer->normalize($expr);
    }

    public function quantity(int|Rational $value, Expr $unit): Expr
    {
        return (new Constant($value))->mul($unit);
    }

    public function unit(string $name): Unit
    {
        return $this->unitRegistry->get($name);
    }
}
