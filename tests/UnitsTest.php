<?php

namespace jbboehr\Yumemi\Tests;

use jbboehr\Yumemi\Exception\IncompatibleUnitException;
use jbboehr\Yumemi\Exception\UnsupportedSyntaxException;
use jbboehr\Yumemi\Exception\UnitNotFoundException;
use jbboehr\Yumemi\Expr\Compound;
use jbboehr\Yumemi\Expr\Term;
use jbboehr\Yumemi\Units;
use PHPUnit\Framework\TestCase;

final class UnitsTest extends TestCase
{
    public function testDefaultUnitsNormalizeDerivedUnits(): void
    {
        $units = Units::default();

        $this->assertSame('1000 * meter', $units->normalize('kilometer')->toString());
    }

    public function testDefaultUnitsCheckCompatibility(): void
    {
        $units = Units::default();

        $this->assertTrue($units->compatible('kilometer', 'meter'));
        $this->assertFalse($units->compatible('meter', 'second'));
    }

    public function testDefaultUnitsExposeDimensions(): void
    {
        $units = Units::default();
        $dimension = $units->dimension('newton');

        $this->assertSame([1, 1, -2, 0, 0, 0, 0], $dimension->powers());
        $this->assertSame('length * mass / time ^ 2', $dimension->toString());
        $this->assertTrue($dimension->equals($units->dimension('kilogram * meter / second^2')));
        $this->assertTrue($units->dimension('percent')->isDimensionless());
    }

    public function testUnitExpressionsExposeDimensionDirectly(): void
    {
        $units = Units::default();

        // Units::unit() returns an Expr that may be a Compound (prefixed/derived),
        // yet dimension() now resolves on any node, not just Unit leaves.
        $this->assertInstanceOf(Compound::class, $units->unit('centimeter'));
        $this->assertSame('length', $units->unit('centimeter')->dimension()->toString());
        $this->assertSame('length', $units->unit('kilometer')->dimension()->toString());

        $newton = $units->unit('newton')->dimension();
        $this->assertSame([1, 1, -2, 0, 0, 0, 0], $newton->powers());
        $this->assertTrue($newton->equals($units->dimension('newton')));

        // Parsed compound expressions resolve too, matching the Units facade.
        $this->assertSame('mass / time ^ 2', $units->parse('newton / meter')->dimension()->toString());
        $this->assertSame('length ^ 2', $units->parse('meter^2')->dimension()->toString());
        $this->assertTrue($units->parse('meter^0')->dimension()->isDimensionless());
    }

    public function testDefaultUnitsConvertValues(): void
    {
        $units = Units::default();

        $this->assertSame('1000', $units->convert(1, 'kilometer', 'meter')->toString());
        $this->assertSame('60', $units->convert(1, 'minute', 'second')->toString());
    }

    public function testDefaultUnitsUseUdunits2AliasesForImperialConversions(): void
    {
        $units = Units::default();

        $this->assertSame('1/12', $units->conversionFactor('inch', 'foot')->toString());
        $this->assertSame('124', $units->convert(1488, 'inch', 'foot')->toString());
    }

    public function testDefaultUnitsUseUdunits2LargeScaleConversions(): void
    {
        $units = Units::default();

        $this->assertSame('94607300000000000000000000', $units->conversionFactor('light_year', 'angstrom')->toString());
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

        $this->assertSame('1000 * meter * minute ^ -1', $units->parse('kilometer / minute')->toString());
        $this->assertSame('50/3 * meter * second ^ -1', $units->normalize(
            $units->parse('kilometer / minute'),
        )->toString());
    }

    public function testParseReducesZeroPowersToDimensionless(): void
    {
        $units = Units::default();

        $this->assertSame('1', $units->parse('meter^0')->toString());
        $this->assertTrue($units->dimension('meter^0')->isDimensionless());
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

    public function testDefaultUnitsAcceptStringExpressions(): void
    {
        $units = Units::default();

        $this->assertSame('1000 * meter', $units->normalize('kilometer')->toString());
        $this->assertTrue($units->compatible('meter / second', 'kilometer / minute'));
        $this->assertSame('3/50', $units->conversionFactor('meter / second', 'kilometer / minute')->toString());
        $this->assertSame('3/50', $units->convert(1, 'meter / second', 'kilometer / minute')->toString());
        $this->assertSame('5 * meter', $units->quantity(5, 'meter')->toString());
    }

    public function testStringExpressionsStillRejectIncompatibleUnits(): void
    {
        $units = Units::default();

        $this->expectException(IncompatibleUnitException::class);
        $units->conversionFactor('meter', 'second');
    }

    public function testStringExpressionsStillRejectUnknownUnits(): void
    {
        $units = Units::default();

        $this->expectException(UnitNotFoundException::class);
        $units->normalize('league');
    }

    public function testDefaultUnitsRejectAffineUdunits2DefinitionsForNow(): void
    {
        $units = Units::default();

        try {
            $units->normalize('degree_Celsius');
            self::fail('Expected UnsupportedSyntaxException');
        } catch (UnsupportedSyntaxException $exception) {
            $this->assertStringContainsString('@', $exception->getMessage());
            $this->assertStringContainsString('Affine', $exception->getMessage());
            $this->assertStringContainsString('@', $exception->expression);
        }
    }
}
