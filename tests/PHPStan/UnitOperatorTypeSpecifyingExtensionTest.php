<?php

namespace jbboehr\IudexMensurarumMysteriorum\Tests\PHPStan;

use jbboehr\IudexMensurarumMysteriorum\PHPStan\UnitExpressionParser;
use jbboehr\IudexMensurarumMysteriorum\PHPStan\UnitFloatType;
use jbboehr\IudexMensurarumMysteriorum\PHPStan\UnitIntegerType;
use jbboehr\IudexMensurarumMysteriorum\PHPStan\UnitOperatorTypeSpecifyingExtension;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\VerbosityLevel;
use PHPUnit\Framework\TestCase;

final class UnitOperatorTypeSpecifyingExtensionTest extends TestCase
{
    private UnitOperatorTypeSpecifyingExtension $extension;

    protected function setUp(): void
    {
        $this->extension = new UnitOperatorTypeSpecifyingExtension();
    }

    public function testSupportsArithmeticWhenEitherSideHasUnit(): void
    {
        $meters = $this->unitInt('meter');
        $bare = new IntegerType();

        $this->assertTrue($this->extension->isOperatorSupported('+', $meters, $meters));
        $this->assertTrue($this->extension->isOperatorSupported('*', $meters, $bare));
        $this->assertTrue($this->extension->isOperatorSupported('%', $meters, $meters));
        $this->assertTrue($this->extension->isOperatorSupported('**', $meters, $bare));
        $this->assertFalse($this->extension->isOperatorSupported('+', $bare, $bare));
        $this->assertFalse($this->extension->isOperatorSupported('~', $meters, $meters));
    }

    public function testAddSameUnitKeepsUnitAndIntegerKind(): void
    {
        $a = $this->unitInt('meter');
        $b = $this->unitInt('meter');

        $result = $this->extension->specifyType('+', $a, $b);

        $this->assertInstanceOf(UnitIntegerType::class, $result);
        $this->assertSame("unit_int<'meter'>", $result->describe(VerbosityLevel::precise()));
    }

    public function testAddWithFloatPromotesToUnitFloat(): void
    {
        $a = $this->unitInt('meter');
        $b = $this->unitFloat('meter');

        $result = $this->extension->specifyType('+', $a, $b);

        $this->assertInstanceOf(UnitFloatType::class, $result);
        $this->assertSame("unit_float<'meter'>", $result->describe(VerbosityLevel::precise()));
    }

    public function testAddDifferentUnitsIsError(): void
    {
        $result = $this->extension->specifyType('+', $this->unitInt('meter'), $this->unitInt('second'));

        $this->assertInstanceOf(ErrorType::class, $result);
        $this->assertStringContainsString('incompatible units', strtolower($result->getReason() ?? ''));
    }

    public function testAddUnitAndBareNumericIsError(): void
    {
        $result = $this->extension->specifyType('+', $this->unitInt('meter'), new IntegerType());

        $this->assertInstanceOf(ErrorType::class, $result);
    }

    public function testMulCombinesUnits(): void
    {
        $speed = $this->unitInt('meter / second');
        $time = $this->unitInt('second');

        $result = $this->extension->specifyType('*', $speed, $time);

        $this->assertInstanceOf(UnitIntegerType::class, $result);
        $this->assertSame("unit_int<'meter'>", $result->describe(VerbosityLevel::precise()));
    }

    public function testDivCombinesUnitsAndAlwaysReturnsFloat(): void
    {
        $distance = $this->unitInt('meter');
        $time = $this->unitInt('second');

        $result = $this->extension->specifyType('/', $distance, $time);

        $this->assertInstanceOf(UnitFloatType::class, $result);
        $this->assertSame("unit_float<'meter / second'>", $result->describe(VerbosityLevel::precise()));
    }

    public function testIntDivIntSameUnitIsFloat(): void
    {
        $a = $this->unitInt('meter');
        $b = $this->unitInt('meter');

        $result = $this->extension->specifyType('/', $a, $b);

        $this->assertInstanceOf(UnitFloatType::class, $result);
        // meter/meter → dimensionless "1" display
        $this->assertSame("unit_float<'1'>", $result->describe(VerbosityLevel::precise()));
    }

    public function testMulByBareScalarKeepsUnit(): void
    {
        $meters = $this->unitInt('meter');
        $result = $this->extension->specifyType('*', $meters, new IntegerType());

        $this->assertInstanceOf(UnitIntegerType::class, $result);
        $this->assertSame("unit_int<'meter'>", $result->describe(VerbosityLevel::precise()));
    }

    public function testDivByBareScalarKeepsUnitAsFloat(): void
    {
        $meters = $this->unitInt('meter');
        $result = $this->extension->specifyType('/', $meters, new IntegerType());

        $this->assertInstanceOf(UnitFloatType::class, $result);
        $this->assertSame("unit_float<'meter'>", $result->describe(VerbosityLevel::precise()));
    }

    public function testBareScalarDivUnitInvertsUnitAsFloat(): void
    {
        $seconds = $this->unitInt('second');
        $result = $this->extension->specifyType('/', new IntegerType(), $seconds);

        $this->assertInstanceOf(UnitFloatType::class, $result);
        $this->assertSame("unit_float<'1 / second'>", $result->describe(VerbosityLevel::precise()));
    }

    public function testMulByBareFloatPromotesToUnitFloat(): void
    {
        $meters = $this->unitInt('meter');
        $result = $this->extension->specifyType('*', $meters, new FloatType());

        $this->assertInstanceOf(UnitFloatType::class, $result);
        $this->assertSame("unit_float<'meter'>", $result->describe(VerbosityLevel::precise()));
    }

    public function testSubSameUnitKeepsUnit(): void
    {
        $a = $this->unitFloat('meter');
        $b = $this->unitFloat('meter');

        $result = $this->extension->specifyType('-', $a, $b);

        $this->assertInstanceOf(UnitFloatType::class, $result);
        $this->assertSame("unit_float<'meter'>", $result->describe(VerbosityLevel::precise()));
    }

    public function testPowConstantIntegerRaisesUnit(): void
    {
        $side = $this->unitFloat('meter');
        $result = $this->extension->specifyType('**', $side, new ConstantIntegerType(2));

        $this->assertInstanceOf(UnitFloatType::class, $result);
        $this->assertSame("unit_float<'meter ^ 2'>", $result->describe(VerbosityLevel::precise()));
    }

    public function testPowNonConstantExponentIsError(): void
    {
        $result = $this->extension->specifyType('**', $this->unitFloat('meter'), new IntegerType());

        $this->assertInstanceOf(ErrorType::class, $result);
    }

    public function testModSameUnitKeepsUnit(): void
    {
        $a = $this->unitInt('meter');
        $b = $this->unitInt('meter');

        $result = $this->extension->specifyType('%', $a, $b);

        $this->assertInstanceOf(UnitIntegerType::class, $result);
        $this->assertSame("unit_int<'meter'>", $result->describe(VerbosityLevel::precise()));
    }

    public function testModDifferentUnitsIsError(): void
    {
        $result = $this->extension->specifyType('%', $this->unitInt('meter'), $this->unitInt('second'));

        $this->assertInstanceOf(ErrorType::class, $result);
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
