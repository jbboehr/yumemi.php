<?php

namespace jbboehr\IudexMensurarumMysteriorum\Tests;

use jbboehr\IudexMensurarumMysteriorum\Expr\Compound;
use jbboehr\IudexMensurarumMysteriorum\Expr\Term;
use jbboehr\IudexMensurarumMysteriorum\Units;
use PHPUnit\Framework\TestCase;

final class UnitsTest extends TestCase
{
    public function testDefaultUnitsNormalizeDerivedUnits(): void
    {
        $units = Units::default();

        $this->assertSame('1000 * meter', $units->normalize($units->unit('kilometer'))->toString());
    }

    public function testDefaultUnitsCheckCompatibility(): void
    {
        $units = Units::default();

        $this->assertTrue($units->compatible($units->unit('kilometer'), $units->unit('meter')));
        $this->assertFalse($units->compatible($units->unit('meter'), $units->unit('second')));
    }

    public function testDefaultUnitsConvertValues(): void
    {
        $units = Units::default();

        $this->assertSame('1000', $units->convert(1, $units->unit('kilometer'), $units->unit('meter'))->toString());
        $this->assertSame('60', $units->convert(1, $units->unit('minute'), $units->unit('second'))->toString());
    }

    public function testDefaultUnitsConvertCompoundValues(): void
    {
        $units = Units::default();

        $metersPerSecond = new Compound([
            $units->unit('meter'),
            new Term($units->unit('second'), -1),
        ]);
        $kilometersPerMinute = new Compound([
            $units->unit('kilometer'),
            new Term($units->unit('minute'), -1),
        ]);

        $this->assertSame('3/50', $units->convert(1, $metersPerSecond, $kilometersPerMinute)->toString());
    }

    public function testDefaultUnitsParseExpressions(): void
    {
        $units = Units::default();

        $this->assertSame('kilometer * minute ^ -1', $units->parse('kilometer / minute')->toString());
        $this->assertSame('50/3 * meter * second ^ -1', $units->normalize(
            $units->parse('kilometer / minute'),
        )->toString());
    }

    public function testDefaultUnitsUseParsedExpressionsForConversion(): void
    {
        $units = Units::default();

        $this->assertSame('3/50', $units->convert(
            1,
            $units->parse('meter / second'),
            $units->parse('kilometer / minute'),
        )->toString());
    }
}
