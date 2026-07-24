<?php

namespace jbboehr\IudexMensurarumMysteriorum\Tests;

use jbboehr\IudexMensurarumMysteriorum\Exception\IncompatibleUnitException;
use jbboehr\IudexMensurarumMysteriorum\Number\Rational;
use jbboehr\IudexMensurarumMysteriorum\Units;
use PHPUnit\Framework\TestCase;

final class QuantityTest extends TestCase
{
    public function testConvertsToCompatibleUnit(): void
    {
        $units = Units::default();

        $quantity = $units->quantity(1488, 'inch')->to('foot');

        $this->assertSame('124', $quantity->valueToString());
        $this->assertSame('foot', $quantity->unitToString());
        $this->assertSame('1488', $quantity->valueIn('inch')->toString());
        $this->assertSame('124 * foot', $quantity->toString());
    }

    public function testAddsCompatibleQuantities(): void
    {
        $units = Units::default();

        $quantity = $units->quantity(1, 'meter')->add($units->quantity(2, 'meter'));

        $this->assertSame('3', $quantity->valueToString());
        $this->assertSame('meter', $quantity->unitToString());
        $this->assertSame('300', $quantity->valueIn('centimeter')->toString());
    }

    public function testAddsExplicitlyConvertedQuantities(): void
    {
        $units = Units::default();

        $quantity = $units->quantity(1, 'meter')->add($units->quantity(100, 'centimeter')->to('meter'));

        $this->assertSame('2', $quantity->valueToString());
        $this->assertSame('meter', $quantity->unitToString());
        $this->assertSame('200', $quantity->valueIn('centimeter')->toString());
    }

    public function testSubtractsCompatibleQuantities(): void
    {
        $units = Units::default();

        $quantity = $units->quantity(1, 'meter')->sub($units->quantity(1, 'meter')->div(new Rational(4)));

        $this->assertSame('3/4', $quantity->valueToString());
        $this->assertSame('75', $quantity->valueIn('centimeter')->toString());
    }

    public function testAdditionDoesNotImplicitlyConvertUnits(): void
    {
        $units = Units::default();

        $this->expectException(IncompatibleUnitException::class);

        $units->quantity(1, 'meter')->add($units->quantity(100, 'centimeter'));
    }

    public function testRejectsIncompatibleAddition(): void
    {
        $units = Units::default();

        $this->expectException(IncompatibleUnitException::class);

        $units->quantity(1, 'meter')->add($units->quantity(1, 'second'));
    }

    public function testMultipliesByQuantity(): void
    {
        $units = Units::default();

        $quantity = $units->quantity(2, 'meter')->mul($units->quantity(3, 'second'));

        $this->assertSame('6', $quantity->valueToString());
        $this->assertSame('meter * second', $quantity->unitToString());
        $this->assertSame('6 * meter * second', $quantity->expr()->toString());
    }

    public function testDividesByQuantity(): void
    {
        $units = Units::default();

        $quantity = $units->quantity(3, 'meter')->div($units->quantity(2, 'second'));

        $this->assertSame('3/2', $quantity->valueToString());
        $this->assertSame('meter / second', $quantity->unitToString());
        $this->assertSame('3/2 * meter * second ^ -1', $quantity->expr()->toString());
    }

    public function testMultipliesByScalar(): void
    {
        $units = Units::default();

        $quantity = $units->quantity(3, 'meter')->mul(2);

        $this->assertSame('6', $quantity->valueToString());
        $this->assertSame('meter', $quantity->unitToString());
        $this->assertSame('6 * meter', $quantity->toString());
    }

    public function testDividesByScalar(): void
    {
        $units = Units::default();

        $quantity = $units->quantity(3, 'meter')->div(new Rational(2));

        $this->assertSame('3/2', $quantity->valueToString());
        $this->assertSame('meter', $quantity->unitToString());
        $this->assertSame('3/2 * meter', $quantity->toString());
    }

    public function testExpressionUsesSymbolicUnitMath(): void
    {
        $units = Units::default();

        $quantity = $units->quantity(100, 'centimeter');

        $this->assertSame('100 * centimeter', $quantity->toString());
        $this->assertSame('100 * centimeter', $quantity->expr()->toString());
        $this->assertSame('1', $quantity->valueIn('meter')->toString());
    }

    public function testArithmeticReducesChosenUnitsWithoutSubstitution(): void
    {
        $units = Units::default();

        $quantity = $units->quantity(2, 'meter / second')->mul($units->quantity(3, 'second'));

        $this->assertSame('6', $quantity->valueToString());
        $this->assertSame('meter', $quantity->unitToString());
        $this->assertSame('6 * meter', $quantity->toString());
    }

    public function testArithmeticDoesNotSubstituteCompatibleUnitDefinitions(): void
    {
        $units = Units::default();

        $quantity = $units->quantity(2, 'centimeter / second')->div($units->quantity(3, 'foot'));

        $this->assertSame('2/3', $quantity->valueToString());
        $this->assertSame('centimeter / (foot * second)', $quantity->unitToString());
        $this->assertSame('2/3 * centimeter / (foot * second)', $quantity->toString());
    }

    public function testNormalizesUnitDefinitionsWithoutChangingValue(): void
    {
        $units = Units::default();

        $quantity = $units->quantity(2, 'centimeter / second')->normalize();

        $this->assertSame('2', $quantity->valueToString());
        $this->assertSame('1/100 * meter / second', $quantity->unitToString());
        $this->assertSame('1/50 * meter / second', $quantity->toString());
        $this->assertSame('1/50', $quantity->valueIn('meter / second')->toString());
    }

    public function testSimplifiesNormalizedUnitScaleIntoValue(): void
    {
        $units = Units::default();

        $quantity = $units->quantity(2, 'centimeter / second')->simplify();

        $this->assertSame('1/50', $quantity->valueToString());
        $this->assertSame('meter / second', $quantity->unitToString());
        $this->assertSame('1/50 * meter / second', $quantity->toString());
        $this->assertSame('2', $quantity->valueIn('centimeter / second')->toString());
    }

    public function testSimplifiesComposedCompatibleUnitDefinitions(): void
    {
        $units = Units::default();

        $quantity = $units
            ->quantity(2, 'centimeter / second')
            ->div($units->quantity(3, 'foot'))
            ->simplify();

        $this->assertSame('25/1143', $quantity->valueToString());
        $this->assertSame('1 / second', $quantity->unitToString());
        $this->assertSame('25/1143 / second', $quantity->toString());
    }

    public function testAccessorsExposeStoredValueAndUnit(): void
    {
        $units = Units::default();

        $quantity = $units->quantity(2, 'centimeter / second')->normalize();

        $this->assertSame('2', $quantity->value()->toString());
        $this->assertSame('1/100 * meter * second ^ -1', $quantity->unit()->toString());
    }
}
