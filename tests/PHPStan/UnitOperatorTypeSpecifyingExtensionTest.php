<?php

/**
 * +--------------------------------------------------------------------------------------------------------------+
 * |        *                 .                         *                  .                         *            |
 * |   .              *                      .                    *                      .                        |
 * |             .                 .                  *                         .                 *               |
 * -      *                    .             *                    .                         .                     -
 *
 *                               Iudex Mensurarum Mysticarum『夢見』〜ＹＵＭＥＭＩ〜
 *
 * -                                          .----------------.                                                  -
 * |                                      .--'        __        '--.                                              |
 * |                                  .--'          .'  '.          '--.                                          |
 * |                             .---'            .'      '.            '---.                                     |
 * +--------------------------------------------------------------------------------------------------------------+
 *
 * Copyright (c) anno Domini nostri Jesu Christi MMXXVI, John Boehr & contributors
 *
 * SPDX-License-Identifier: AGPL-3.0-only WITH romic-exception
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License version 3,
 * as published by the Free Software Foundation, together with the Romic
 * Exception (an additional permission under section 7 of that license).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * and the Romic Exception along with this program.  If not, see
 * <http://www.gnu.org/licenses/> and the LICENSE_EXCEPTION file.
 */

namespace jbboehr\Yumemi\Tests\PHPStan;

use jbboehr\Yumemi\PHPStan\UnitExpressionParser;
use jbboehr\Yumemi\PHPStan\UnitFloatType;
use jbboehr\Yumemi\PHPStan\UnitIntegerType;
use jbboehr\Yumemi\PHPStan\UnitOperatorTypeSpecifyingExtension;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
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

    public function testSupportsArithmeticWhenAUnionArmHasUnit(): void
    {
        $union = TypeCombinator::union($this->unitInt('meter'), new IntegerType());

        $this->assertTrue($this->extension->isOperatorSupported('*', $union, $this->unitInt('second')));
    }

    public function testAddSameIntegerUnitAllowsFloatOverflow(): void
    {
        $a = $this->unitInt('meter');
        $b = $this->unitInt('meter');

        $result = $this->extension->specifyType('+', $a, $b);

        $this->assertInstanceOf(BenevolentUnionType::class, $result);
        $this->assertSame("(unit_float<'meter'>|unit_int<'meter'>)", $result->describe(VerbosityLevel::precise()));
    }

    public function testAddWithFloatPromotesToUnitFloat(): void
    {
        $a = $this->unitInt('meter');
        $b = $this->unitFloat('meter');

        $result = $this->extension->specifyType('+', $a, $b);

        $this->assertInstanceOf(UnitFloatType::class, $result);
        $this->assertSame("unit_float<'meter'>", $result->describe(VerbosityLevel::precise()));
    }

    public function testSubtractSameIntegerUnitAllowsFloatOverflow(): void
    {
        $meters = $this->unitInt('meter');
        $result = $this->extension->specifyType('-', $meters, $meters);

        $this->assertInstanceOf(BenevolentUnionType::class, $result);
        $this->assertSame("(unit_float<'meter'>|unit_int<'meter'>)", $result->describe(VerbosityLevel::precise()));
    }

    public function testAddDifferentUnitsIsError(): void
    {
        $result = $this->extension->specifyType('+', $this->unitInt('meter'), $this->unitInt('second'));

        $this->assertInstanceOf(ErrorType::class, $result);
        $this->assertStringContainsString('incompatible units', strtolower($result->getReason() ?? ''));
    }

    public function testAddDefinitionallyEquivalentUnitsSucceeds(): void
    {
        $result = $this->extension->specifyType(
            '+',
            $this->unitFloat('kilometer'),
            $this->unitFloat('1000 * meter'),
        );

        $this->assertInstanceOf(UnitFloatType::class, $result);
    }

    public function testAddSameDimensionDifferentScaleIsError(): void
    {
        $result = $this->extension->specifyType(
            '+',
            $this->unitFloat('meter'),
            $this->unitFloat('foot'),
        );

        $this->assertInstanceOf(ErrorType::class, $result);
    }

    public function testAddUnitAndBareNumericIsError(): void
    {
        $result = $this->extension->specifyType('+', $this->unitInt('meter'), new IntegerType());

        $this->assertInstanceOf(ErrorType::class, $result);
    }

    public function testMulCombinesUnitsAndAllowsFloatOverflow(): void
    {
        $speed = $this->unitInt('meter / second');
        $time = $this->unitInt('second');

        $result = $this->extension->specifyType('*', $speed, $time);

        $this->assertInstanceOf(BenevolentUnionType::class, $result);
        $this->assertSame("(unit_float<'meter'>|unit_int<'meter'>)", $result->describe(VerbosityLevel::precise()));
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

    public function testMulByBareIntegerAllowsFloatOverflow(): void
    {
        $meters = $this->unitInt('meter');
        $result = $this->extension->specifyType('*', $meters, new IntegerType());

        $this->assertInstanceOf(BenevolentUnionType::class, $result);
        $this->assertSame("(unit_float<'meter'>|unit_int<'meter'>)", $result->describe(VerbosityLevel::precise()));
    }

    public function testMulByZeroOrOneCannotOverflow(): void
    {
        $meters = $this->unitInt('meter');

        foreach ([new ConstantIntegerType(0), new ConstantIntegerType(1)] as $identity) {
            $rightResult = $this->extension->specifyType('*', $meters, $identity);
            $leftResult = $this->extension->specifyType('*', $identity, $meters);

            $this->assertInstanceOf(UnitIntegerType::class, $rightResult);
            $this->assertInstanceOf(UnitIntegerType::class, $leftResult);
            $this->assertSame("unit_int<'meter'>", $rightResult->describe(VerbosityLevel::precise()));
            $this->assertSame("unit_int<'meter'>", $leftResult->describe(VerbosityLevel::precise()));
        }
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

    public function testMulEvaluatesFiniteUnitUnionArmByArm(): void
    {
        $left = TypeCombinator::union($this->unitInt('meter'), $this->unitInt('second'));

        $result = $this->extension->specifyType('*', $left, $this->unitInt('meter'));

        $this->assertSame(
            "(unit_float<'meter * second'>|unit_float<'meter ^ 2'>|unit_int<'meter * second'>|unit_int<'meter ^ 2'>)",
            $result->describe(VerbosityLevel::precise()),
        );
    }

    public function testMulEvaluatesMixedUnitAndBareUnionArmByArm(): void
    {
        // TypeCombinator normally collapses int|unit_int to int; use the raw form to cover defensive handling.
        $left = new UnionType([$this->unitInt('meter'), new IntegerType()]);

        $result = $this->extension->specifyType('*', $left, $this->unitInt('second'));

        $this->assertSame(
            "(unit_float<'meter * second'>|unit_float<'second'>|unit_int<'meter * second'>|unit_int<'second'>)",
            $result->describe(VerbosityLevel::precise()),
        );
    }

    public function testInvalidFiniteUnionPairingRejectsWholeOperation(): void
    {
        $left = TypeCombinator::union($this->unitInt('meter'), $this->unitInt('second'));

        $result = $this->extension->specifyType('+', $left, $this->unitInt('meter'));

        $this->assertInstanceOf(ErrorType::class, $result);
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

    public function testPowZeroProducesDimensionlessBrand(): void
    {
        $result = $this->extension->specifyType('**', $this->unitFloat('meter'), new ConstantIntegerType(0));

        $this->assertSame("unit_float<'1'>", $result->describe(VerbosityLevel::precise()));
    }

    public function testIntegerPowResultKindsAccountForOverflow(): void
    {
        $meters = $this->unitInt('meter');

        $negative = $this->extension->specifyType('**', $meters, new ConstantIntegerType(-1));
        $zero = $this->extension->specifyType('**', $meters, new ConstantIntegerType(0));
        $one = $this->extension->specifyType('**', $meters, new ConstantIntegerType(1));
        $two = $this->extension->specifyType('**', $meters, new ConstantIntegerType(2));

        $this->assertSame("unit_float<'1 / meter'>", $negative->describe(VerbosityLevel::precise()));
        $this->assertSame("unit_int<'1'>", $zero->describe(VerbosityLevel::precise()));
        $this->assertSame("unit_int<'meter'>", $one->describe(VerbosityLevel::precise()));
        $this->assertInstanceOf(BenevolentUnionType::class, $two);
        $this->assertSame(
            "(unit_float<'meter ^ 2'>|unit_int<'meter ^ 2'>)",
            $two->describe(VerbosityLevel::precise()),
        );
    }

    public function testOverflowPromotionCanBeDisabled(): void
    {
        $extension = new UnitOperatorTypeSpecifyingExtension(false);
        $meters = $this->unitInt('meter');

        foreach (
            [
                $extension->specifyType('+', $meters, $meters),
                $extension->specifyType('-', $meters, $meters),
                $extension->specifyType('*', $meters, new IntegerType()),
                $extension->specifyType('**', $meters, new ConstantIntegerType(2)),
            ] as $result
        ) {
            $this->assertInstanceOf(UnitIntegerType::class, $result);
        }
    }

    public function testPowEvaluatesFiniteExponentUnionArmByArm(): void
    {
        $exponents = TypeCombinator::union(new ConstantIntegerType(2), new ConstantIntegerType(3));

        $result = $this->extension->specifyType('**', $this->unitFloat('meter'), $exponents);

        $this->assertSame(
            "unit_float<'meter ^ 2'>|unit_float<'meter ^ 3'>",
            $result->describe(VerbosityLevel::precise()),
        );
    }

    public function testPowNonConstantExponentIsError(): void
    {
        $result = $this->extension->specifyType('**', $this->unitFloat('meter'), new IntegerType());

        $this->assertInstanceOf(ErrorType::class, $result);
    }

    public function testPowOutOfRangeConstantExponentIsError(): void
    {
        $result = $this->extension->specifyType('**', $this->unitFloat('meter'), new ConstantIntegerType(10_001));

        $this->assertInstanceOf(ErrorType::class, $result);
        $this->assertStringContainsString('-10000 through 10000', $result->getReason() ?? '');
    }

    public function testModSameUnitKeepsUnit(): void
    {
        $a = $this->unitInt('meter');
        $b = $this->unitInt('meter');

        $result = $this->extension->specifyType('%', $a, $b);

        $this->assertInstanceOf(UnitIntegerType::class, $result);
        $this->assertSame("unit_int<'meter'>", $result->describe(VerbosityLevel::precise()));
    }

    public function testModDefinitionallyEquivalentUnitsKeepsLeftUnit(): void
    {
        $result = $this->extension->specifyType(
            '%',
            $this->unitInt('kilometer'),
            $this->unitInt('1000 * meter'),
        );

        $this->assertInstanceOf(UnitIntegerType::class, $result);
        $this->assertSame("unit_int<'1000 * meter'>", $result->describe(VerbosityLevel::precise()));
    }

    public function testModDifferentUnitsIsError(): void
    {
        $result = $this->extension->specifyType('%', $this->unitInt('meter'), $this->unitInt('second'));

        $this->assertInstanceOf(ErrorType::class, $result);
    }

    public function testModUnitAndDimensionlessUnitIsError(): void
    {
        $result = $this->extension->specifyType('%', $this->unitInt('meter'), $this->unitInt('1'));

        $this->assertInstanceOf(ErrorType::class, $result);
    }

    public function testModUnitFloatIsError(): void
    {
        $result = $this->extension->specifyType('%', $this->unitFloat('meter'), $this->unitFloat('meter'));

        $this->assertInstanceOf(ErrorType::class, $result);
    }

    public function testModUnitAndBareNumericIsError(): void
    {
        $result = $this->extension->specifyType('%', $this->unitInt('meter'), new IntegerType());

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
