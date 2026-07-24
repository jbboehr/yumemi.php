<?php

namespace jbboehr\IudexMensurarumMysteriorum;

use jbboehr\IudexMensurarumMysteriorum\Analyzer\AstConverter;
use jbboehr\IudexMensurarumMysteriorum\Analyzer\ConversionFactorResolver;
use jbboehr\IudexMensurarumMysteriorum\Analyzer\DimensionResolver;
use jbboehr\IudexMensurarumMysteriorum\Analyzer\ExprReducer;
use jbboehr\IudexMensurarumMysteriorum\Analyzer\UnitNormalizer;
use jbboehr\IudexMensurarumMysteriorum\Analyzer\UnitResolver;
use jbboehr\IudexMensurarumMysteriorum\Expr\Compound;
use jbboehr\IudexMensurarumMysteriorum\Expr\Term;
use jbboehr\IudexMensurarumMysteriorum\Expr\Unit;
use jbboehr\IudexMensurarumMysteriorum\Number\Rational;
use jbboehr\IudexMensurarumMysteriorum\Parser\Parser;
use jbboehr\IudexMensurarumMysteriorum\Registry\UnitRegistry;
use jbboehr\IudexMensurarumMysteriorum\Registry\Udunits2UnitRegistry;

final class Units
{
    private readonly AstConverter $astConverter;
    private readonly ConversionFactorResolver $conversionFactorResolver;
    private readonly DimensionResolver $dimensionResolver;
    private readonly UnitNormalizer $unitNormalizer;
    private readonly UnitResolver $unitResolver;

    public function __construct(
        private readonly UnitRegistry $unitRegistry,
    ) {
        $this->unitResolver = new UnitResolver($this->unitRegistry);
        $this->astConverter = new AstConverter($this->unitResolver);
        $this->unitNormalizer = new UnitNormalizer();
        $this->dimensionResolver = new DimensionResolver($this->unitNormalizer);
        $this->conversionFactorResolver = new ConversionFactorResolver($this->unitNormalizer);
    }

    public static function default(): self
    {
        return new self(new Udunits2UnitRegistry());
    }

    public function compatible(Expr|string $left, Expr|string $right): bool
    {
        return $this->conversionFactorResolver->compatible($this->expr($left), $this->expr($right));
    }

    public function conversionFactor(Expr|string $from, Expr|string $to): Rational
    {
        return $this->conversionFactorResolver->resolve($this->expr($from), $this->expr($to));
    }

    public function convert(int|Rational $value, Expr|string $from, Expr|string $to): Rational
    {
        $value = $value instanceof Rational ? $value : new Rational($value);

        return $value->mul($this->conversionFactor($from, $to));
    }

    public function dimension(Expr|string $expr): Dimension
    {
        return $this->dimensionResolver->resolve($this->expr($expr));
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
