<?php

namespace jbboehr\IudexMensurarumMysteriorum\Analyzer;

use jbboehr\IudexMensurarumMysteriorum\Dimension;
use jbboehr\IudexMensurarumMysteriorum\Exception\UnsupportedUnitDimensionException;
use jbboehr\IudexMensurarumMysteriorum\Expr;
use jbboehr\IudexMensurarumMysteriorum\Expr\Compound;
use jbboehr\IudexMensurarumMysteriorum\Expr\Constant;
use jbboehr\IudexMensurarumMysteriorum\Expr\Term;
use jbboehr\IudexMensurarumMysteriorum\Expr\Unit;

final class DimensionResolver
{
    /** @var array<string, array{int, int, int, int, int, int, int}> */
    private const BASE_DIMENSIONS = [
        'meter' => [1, 0, 0, 0, 0, 0, 0],
        'kilogram' => [0, 1, 0, 0, 0, 0, 0],
        'second' => [0, 0, 1, 0, 0, 0, 0],
        'ampere' => [0, 0, 0, 1, 0, 0, 0],
        'kelvin' => [0, 0, 0, 0, 1, 0, 0],
        'mole' => [0, 0, 0, 0, 0, 1, 0],
        'candela' => [0, 0, 0, 0, 0, 0, 1],
    ];

    public function __construct(
        private readonly UnitNormalizer $unitNormalizer,
    ) {
    }

    public function resolve(Expr $expr): Dimension
    {
        return self::resolveNormalized($this->unitNormalizer->normalize($expr));
    }

    public static function resolveNormalized(Expr $expr): Dimension
    {
        return self::collect(ExprReducer::reduce($expr));
    }

    private static function collect(Expr $expr): Dimension
    {
        if ($expr instanceof Compound) {
            $dimension = Dimension::dimensionless();

            foreach ($expr->exprs as $subexpr) {
                $dimension = $dimension->mul(self::collect($subexpr));
            }

            return $dimension;
        }

        if ($expr instanceof Constant) {
            return Dimension::dimensionless();
        }

        if ($expr instanceof Term) {
            return self::collect($expr->value)->pow($expr->power);
        }

        if ($expr instanceof Unit) {
            return self::unitDimension($expr);
        }

        throw new \LogicException('Cannot resolve dimension for expression of type ' . $expr::class);
    }

    private static function unitDimension(Unit $unit): Dimension
    {
        $powers = self::BASE_DIMENSIONS[$unit->name] ?? null;

        if ($powers === null) {
            throw UnsupportedUnitDimensionException::create($unit->name);
        }

        return Dimension::fromPowers($powers);
    }
}
