<?php

namespace jbboehr\IudexMensurarumMysteriorum\Tests\Analyzer;

use jbboehr\IudexMensurarumMysteriorum\Analyzer\ConversionFactorResolver;
use jbboehr\IudexMensurarumMysteriorum\Analyzer\UnitNormalizer;
use jbboehr\IudexMensurarumMysteriorum\Exception\IncompatibleUnitException;
use jbboehr\IudexMensurarumMysteriorum\Expr\Compound;
use jbboehr\IudexMensurarumMysteriorum\Expr\Constant;
use jbboehr\IudexMensurarumMysteriorum\Expr\Term;
use jbboehr\IudexMensurarumMysteriorum\Expr\Unit;
use PHPUnit\Framework\TestCase;

final class ConversionFactorResolverTest extends TestCase
{
    public function testCompatibleUnitsResolveScaleFactor(): void
    {
        $meter = new Unit('meter');
        $kilometer = new Unit('kilometer', new Compound([
            new Constant(1000),
            $meter,
        ]));

        $resolver = new ConversionFactorResolver(new UnitNormalizer());

        $this->assertSame('1000', $resolver->resolve($kilometer, $meter)->toString());
        $this->assertSame('1/1000', $resolver->resolve($meter, $kilometer)->toString());
    }

    public function testIncompatibleUnitsFail(): void
    {
        $resolver = new ConversionFactorResolver(new UnitNormalizer());

        $this->expectException(IncompatibleUnitException::class);
        $this->expectExceptionMessage('Dimensions: length vs time');

        $resolver->resolve(new Unit('meter'), new Unit('second'));
    }

    public function testCompoundCompatibleUnitsResolveScaleFactor(): void
    {
        $meter = new Unit('meter');
        $second = new Unit('second');
        $kilometer = new Unit('kilometer', new Compound([
            new Constant(1000),
            $meter,
        ]));
        $minute = new Unit('minute', new Compound([
            new Constant(60),
            $second,
        ]));

        $resolver = new ConversionFactorResolver(new UnitNormalizer());

        $metersPerSecond = new Compound([
            $meter,
            new Term($second, -1),
        ]);
        $kilometersPerMinute = new Compound([
            $kilometer,
            new Term($minute, -1),
        ]);

        $this->assertTrue($resolver->compatible($metersPerSecond, $kilometersPerMinute));
        $this->assertSame('50/3', $resolver->resolve($kilometersPerMinute, $metersPerSecond)->toString());
        $this->assertSame('3/50', $resolver->resolve($metersPerSecond, $kilometersPerMinute)->toString());
    }
}
