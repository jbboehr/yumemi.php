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
