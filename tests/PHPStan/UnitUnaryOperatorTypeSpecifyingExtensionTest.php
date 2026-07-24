<?php

namespace jbboehr\IudexMensurarumMysteriorum\Tests\PHPStan;

use jbboehr\IudexMensurarumMysteriorum\PHPStan\UnitExpressionParser;
use jbboehr\IudexMensurarumMysteriorum\PHPStan\UnitFloatType;
use jbboehr\IudexMensurarumMysteriorum\PHPStan\UnitIntegerType;
use jbboehr\IudexMensurarumMysteriorum\PHPStan\UnitUnaryOperatorTypeSpecifyingExtension;
use PHPStan\Type\IntegerType;
use PHPStan\Type\VerbosityLevel;
use PHPUnit\Framework\TestCase;

final class UnitUnaryOperatorTypeSpecifyingExtensionTest extends TestCase
{
    private UnitUnaryOperatorTypeSpecifyingExtension $extension;

    protected function setUp(): void
    {
        $this->extension = new UnitUnaryOperatorTypeSpecifyingExtension();
    }

    public function testSupportsUnaryPlusAndMinusOnUnitTypesOnly(): void
    {
        $meters = $this->unitFloat('meter');

        $this->assertTrue($this->extension->isOperatorSupported('-', $meters));
        $this->assertTrue($this->extension->isOperatorSupported('+', $meters));
        $this->assertFalse($this->extension->isOperatorSupported('~', $meters));
        $this->assertFalse($this->extension->isOperatorSupported('-', new IntegerType()));
    }

    public function testUnaryMinusPreservesUnitAndKind(): void
    {
        $meters = $this->unitInt('meter');
        $result = $this->extension->specifyType('-', $meters);

        $this->assertInstanceOf(UnitIntegerType::class, $result);
        $this->assertSame("unit_int<'meter'>", $result->describe(VerbosityLevel::precise()));
    }

    public function testUnaryPlusPreservesUnitFloat(): void
    {
        $speed = $this->unitFloat('meter / second');
        $result = $this->extension->specifyType('+', $speed);

        $this->assertInstanceOf(UnitFloatType::class, $result);
        $this->assertSame("unit_float<'meter / second'>", $result->describe(VerbosityLevel::precise()));
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
