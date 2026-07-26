<?php

namespace jbboehr\Yumemi\Tests\Analyzer;

use jbboehr\Yumemi\Analyzer\DimensionResolver;
use jbboehr\Yumemi\Analyzer\UnitNormalizer;
use jbboehr\Yumemi\Exception\UnsupportedUnitDimensionException;
use jbboehr\Yumemi\Expr\Compound;
use jbboehr\Yumemi\Expr\Constant;
use jbboehr\Yumemi\Expr\Term;
use jbboehr\Yumemi\Expr\Unit;
use PHPUnit\Framework\TestCase;

final class DimensionResolverTest extends TestCase
{
    public function testResolvesBaseUnitDimension(): void
    {
        $resolver = new DimensionResolver(new UnitNormalizer());

        $this->assertSame([1, 0, 0, 0, 0, 0, 0], $resolver->resolve(new Unit('meter'))->powers());
        $this->assertSame([0, 1, 0, 0, 0, 0, 0], $resolver->resolve(new Unit('kilogram'))->powers());
        $this->assertSame([0, 0, 1, 0, 0, 0, 0], $resolver->resolve(new Unit('second'))->powers());
        $this->assertSame([0, 0, 0, 1, 0, 0, 0], $resolver->resolve(new Unit('ampere'))->powers());
        $this->assertSame([0, 0, 0, 0, 1, 0, 0], $resolver->resolve(new Unit('kelvin'))->powers());
        $this->assertSame([0, 0, 0, 0, 0, 1, 0], $resolver->resolve(new Unit('mole'))->powers());
        $this->assertSame([0, 0, 0, 0, 0, 0, 1], $resolver->resolve(new Unit('candela'))->powers());
    }

    public function testResolvesCompoundDimension(): void
    {
        $resolver = new DimensionResolver(new UnitNormalizer());
        $dimension = $resolver->resolve(new Compound([
            new Unit('kilogram'),
            new Unit('meter'),
            new Term(new Unit('second'), -2),
        ]));

        $this->assertSame([1, 1, -2, 0, 0, 0, 0], $dimension->powers());
        $this->assertSame('length * mass / time ^ 2', $dimension->toString());
    }

    public function testIgnoresConstants(): void
    {
        $resolver = new DimensionResolver(new UnitNormalizer());
        $dimension = $resolver->resolve(new Compound([
            new Constant(1000),
            new Unit('meter'),
        ]));

        $this->assertSame([1, 0, 0, 0, 0, 0, 0], $dimension->powers());
    }

    public function testResolvesDerivedUnitDimension(): void
    {
        $meter = new Unit('meter');
        $second = new Unit('second');
        $kilogram = new Unit('kilogram');
        $newton = new Unit('newton', new Compound([
            $kilogram,
            $meter,
            new Term($second, -2),
        ]));

        $resolver = new DimensionResolver(new UnitNormalizer());

        $this->assertSame([1, 1, -2, 0, 0, 0, 0], $resolver->resolve($newton)->powers());
    }

    public function testRejectsUnknownBaseUnitDimension(): void
    {
        $resolver = new DimensionResolver(new UnitNormalizer());

        $this->expectException(UnsupportedUnitDimensionException::class);

        $resolver->resolve(new Unit('widget'));
    }
}
