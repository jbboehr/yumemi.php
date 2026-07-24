<?php

namespace jbboehr\IudexMensurarumMysteriorum\Tests\PHPStan;

use jbboehr\IudexMensurarumMysteriorum\PHPStan\UnitExpressionParser;
use jbboehr\IudexMensurarumMysteriorum\PHPStan\UnitFloatType;
use jbboehr\IudexMensurarumMysteriorum\PHPStan\UnitIntegerType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\VerbosityLevel;
use PHPUnit\Framework\TestCase;

final class UnitMagnitudeTypeTest extends TestCase
{
    public function testIntegerTypeDescribesUnit(): void
    {
        $type = $this->unitInt('meter / second');

        $this->assertSame("unit_int<'meter / second'>", $type->describe(VerbosityLevel::precise()));
        $this->assertTrue($type->isInteger()->yes());
        $this->assertTrue($type->isFloat()->no());
    }

    public function testFloatTypeDescribesUnit(): void
    {
        $type = $this->unitFloat('kilogram');

        $this->assertSame("unit_float<'kilogram'>", $type->describe(VerbosityLevel::precise()));
        $this->assertTrue($type->isFloat()->yes());
    }

    public function testEqualUnitsAreEqual(): void
    {
        $a = $this->unitInt('meter * second');
        $b = $this->unitInt('second * meter');

        $this->assertTrue($a->equals($b));
        $this->assertTrue($a->accepts($b, true)->yes());
        $this->assertTrue($a->isSuperTypeOf($b)->yes());
    }

    public function testDifferentUnitsAreNotAssignable(): void
    {
        $meters = $this->unitInt('meter');
        $seconds = $this->unitInt('second');

        $this->assertFalse($meters->equals($seconds));
        $this->assertTrue($meters->accepts($seconds, true)->no());
        $this->assertTrue($meters->isSuperTypeOf($seconds)->no());
    }

    public function testBareIntegerIsNotAssignableToUnitInteger(): void
    {
        $meters = $this->unitInt('meter');
        $bare = new IntegerType();

        $this->assertTrue($meters->accepts($bare, true)->no());
        $this->assertTrue($meters->isSuperTypeOf($bare)->no());
    }

    public function testBareFloatIsNotAssignableToUnitFloat(): void
    {
        $meters = $this->unitFloat('meter');
        $bare = new FloatType();

        $this->assertTrue($meters->accepts($bare, true)->no());
    }

    public function testIntegerAndFloatUnitTypesAreDistinct(): void
    {
        $intMeters = $this->unitInt('meter');
        $floatMeters = $this->unitFloat('meter');

        $this->assertFalse($intMeters->equals($floatMeters));
        $this->assertTrue($intMeters->accepts($floatMeters, true)->no());
    }

    public function testDefinitionallyEquivalentUnitsAreAssignable(): void
    {
        $km = $this->unitFloat('kilometer');
        $thousandMeters = $this->unitFloat('1000 * meter');
        $hundredThousandCm = $this->unitFloat('100000 * centimeter');

        $this->assertTrue($km->getUnitExpression()->equivalent($thousandMeters->getUnitExpression()));
        $this->assertTrue($km->accepts($thousandMeters, true)->yes());
        $this->assertTrue($thousandMeters->accepts($hundredThousandCm, true)->yes());
        $this->assertTrue($km->isSuperTypeOf($hundredThousandCm)->yes());
    }

    public function testDerivedSiUnitsMatchExpandedBaseForm(): void
    {
        $newton = $this->unitFloat('newton');
        $expanded = $this->unitFloat('kilogram * meter / second^2');

        $this->assertTrue($newton->accepts($expanded, true)->yes());
        $this->assertTrue($expanded->accepts($newton, true)->yes());
    }

    public function testSameDimensionDifferentScaleIsNotAssignable(): void
    {
        $meters = $this->unitFloat('meter');
        $feet = $this->unitFloat('foot');

        $this->assertTrue($meters->getUnitExpression()->sameDimension($feet->getUnitExpression()));
        $this->assertFalse($meters->getUnitExpression()->equivalent($feet->getUnitExpression()));
        $this->assertTrue($meters->accepts($feet, true)->no());
    }

    public function testUnitFloatAcceptsEquivalentUnitInt(): void
    {
        $floatMeters = $this->unitFloat('meter');
        $intMeters = $this->unitInt('meter');

        $this->assertTrue($floatMeters->accepts($intMeters, true)->yes());
        $this->assertTrue($floatMeters->isSuperTypeOf($intMeters)->yes());
    }

    private function unitInt(string $unit): UnitIntegerType
    {
        $parsed = (new UnitExpressionParser())->parse($unit);
        $this->assertTrue($parsed->isOk(), $parsed->errorMessage() ?? '');

        return new UnitIntegerType($parsed->expression());
    }

    private function unitFloat(string $unit): UnitFloatType
    {
        $parsed = (new UnitExpressionParser())->parse($unit);
        $this->assertTrue($parsed->isOk(), $parsed->errorMessage() ?? '');

        return new UnitFloatType($parsed->expression());
    }
}
