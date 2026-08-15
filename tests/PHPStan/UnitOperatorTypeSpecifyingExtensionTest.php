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
use jbboehr\Yumemi\PHPStan\UnitConstantFloatType;
use jbboehr\Yumemi\PHPStan\UnitConstantIntegerType;
use jbboehr\Yumemi\PHPStan\UnitFloatType;
use jbboehr\Yumemi\PHPStan\UnitIntegerType;
use jbboehr\Yumemi\PHPStan\UnitIntegerTypeHelper;
use jbboehr\Yumemi\PHPStan\UnitNumericStringType;
use jbboehr\Yumemi\PHPStan\UnitOperatorTypeSpecifyingExtension;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\Constant\ConstantFloatType;
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

    public function testConstantFloatArithmeticPreservesValueAndDerivedUnit(): void
    {
        $meters = $this->unitConstantFloat(1.5, 'meter');
        $otherMeters = $this->unitConstantFloat(2.25, 'meter');
        $seconds = $this->unitConstantFloat(2.0, 'second');

        $this->assertSame(
            "3.75&unit_float<'meter'>",
            $this->extension->specifyType('+', $meters, $otherMeters)->describe(VerbosityLevel::precise()),
        );
        $this->assertSame(
            "-0.75&unit_float<'meter'>",
            $this->extension->specifyType('-', $meters, $otherMeters)->describe(VerbosityLevel::precise()),
        );
        $this->assertSame(
            "3.0&unit_float<'meter * second'>",
            $this->extension->specifyType('*', $meters, $seconds)->describe(VerbosityLevel::precise()),
        );
        $this->assertSame(
            "0.75&unit_float<'meter / second'>",
            $this->extension->specifyType('/', $meters, $seconds)->describe(VerbosityLevel::precise()),
        );
        $this->assertSame(
            "4.5&unit_float<'meter'>",
            $this->extension->specifyType('*', $meters, new ConstantIntegerType(3))
                ->describe(VerbosityLevel::precise()),
        );
        $this->assertSame(
            "4.0&unit_float<'1 / second'>",
            $this->extension->specifyType('/', new ConstantFloatType(8.0), $seconds)
                ->describe(VerbosityLevel::precise()),
        );
        $this->assertSame(
            "2.25&unit_float<'meter ^ 2'>",
            $this->extension->specifyType('**', $meters, new ConstantIntegerType(2))
                ->describe(VerbosityLevel::precise()),
        );
    }

    public function testPartiallyKnownFloatArithmeticWidensWithoutDiscardingTheUnit(): void
    {
        $constantMeters = $this->unitConstantFloat(1.5, 'meter');

        $this->assertSame(
            "unit_float<'meter'>",
            $this->extension->specifyType('+', $constantMeters, $this->unitFloat('meter'))
                ->describe(VerbosityLevel::precise()),
        );
        $this->assertSame(
            "unit_float<'meter'>",
            $this->extension->specifyType('*', $constantMeters, new FloatType())
                ->describe(VerbosityLevel::precise()),
        );
    }

    public function testUndefinedConstantFloatArithmeticWidensInsteadOfFolding(): void
    {
        $zeroSeconds = $this->unitConstantFloat(0.0, 'second');

        $this->assertSame(
            "unit_float<'meter / second'>",
            $this->extension->specifyType('/', $this->unitConstantFloat(1.0, 'meter'), $zeroSeconds)
                ->describe(VerbosityLevel::precise()),
        );
        $this->assertSame(
            "unit_float<'meter'>",
            $this->extension->specifyType('/', $this->unitConstantFloat(1.0, 'meter'), new ConstantFloatType(0.0))
                ->describe(VerbosityLevel::precise()),
        );
        $this->assertSame(
            "unit_float<'1 / second'>",
            $this->extension->specifyType('/', new ConstantFloatType(1.0), $zeroSeconds)
                ->describe(VerbosityLevel::precise()),
        );
        $this->assertSame(
            "unit_float<'1 / meter'>",
            $this->extension->specifyType(
                '**',
                $this->unitConstantFloat(0.0, 'meter'),
                new ConstantIntegerType(-1),
            )->describe(VerbosityLevel::precise()),
        );
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
        $this->assertSame(
            'Cannot use + with units meter and second because they are not definitionally equivalent.',
            $result->getReason(),
        );
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
        $this->assertSame(
            'Cannot use + with units meter and international_foot because they are not definitionally equivalent.',
            $result->getReason(),
        );
    }

    public function testAddUnitAndBareNumericIsError(): void
    {
        $result = $this->extension->specifyType('+', $this->unitInt('meter'), new IntegerType());

        $this->assertInstanceOf(ErrorType::class, $result);
    }

    public function testArithmeticDoesNotTreatBrandedNumericStringsAsBareScalars(): void
    {
        $numericString = $this->unitNumericString('second');

        foreach (['+', '-', '*', '/', '%', '**'] as $operator) {
            $result = $this->extension->specifyType($operator, $this->unitInt('meter'), $numericString);

            $this->assertInstanceOf(ErrorType::class, $result, $operator);
        }
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

    public function testMulByZeroProducesBrandedConstant(): void
    {
        $meters = $this->unitInt('meter');
        $rightResult = $this->extension->specifyType('*', $meters, new ConstantIntegerType(0));
        $leftResult = $this->extension->specifyType('*', new ConstantIntegerType(0), $meters);

        $this->assertInstanceOf(UnitConstantIntegerType::class, $rightResult);
        $this->assertInstanceOf(UnitConstantIntegerType::class, $leftResult);
        $this->assertSame("0&unit_int<'meter'>", $rightResult->describe(VerbosityLevel::precise()));
        $this->assertSame("0&unit_int<'meter'>", $leftResult->describe(VerbosityLevel::precise()));
    }

    public function testMulByOnePreservesUnboundedBrandedInteger(): void
    {
        $meters = $this->unitInt('meter');
        $rightResult = $this->extension->specifyType('*', $meters, new ConstantIntegerType(1));
        $leftResult = $this->extension->specifyType('*', new ConstantIntegerType(1), $meters);

        $this->assertInstanceOf(UnitIntegerType::class, $rightResult);
        $this->assertInstanceOf(UnitIntegerType::class, $leftResult);
        $this->assertSame("unit_int<'meter'>", $rightResult->describe(VerbosityLevel::precise()));
        $this->assertSame("unit_int<'meter'>", $leftResult->describe(VerbosityLevel::precise()));
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
        $this->assertSame("1&unit_int<'1'>", $zero->describe(VerbosityLevel::precise()));
        $this->assertSame("unit_int<'meter'>", $one->describe(VerbosityLevel::precise()));
        $this->assertInstanceOf(BenevolentUnionType::class, $two);
        $this->assertSame(
            "((unit_int<'meter ^ 2'>&int<0, max>)|unit_float<'meter ^ 2'>)",
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

    public function testSafeIntegerRangesPropagateThroughAddSubtractAndMultiply(): void
    {
        $left = UnitIntegerTypeHelper::create($this->unit('meter'), -2, 3);
        $right = UnitIntegerTypeHelper::create($this->unit('meter'), 4, 5);

        $this->assertSame(
            "unit_int<'meter'>&int<2, 8>",
            $this->extension->specifyType('+', $left, $right)->describe(VerbosityLevel::precise()),
        );
        $this->assertSame(
            "unit_int<'meter'>&int<-7, -1>",
            $this->extension->specifyType('-', $left, $right)->describe(VerbosityLevel::precise()),
        );
        $this->assertSame(
            "unit_int<'meter ^ 2'>&int<-10, 15>",
            $this->extension->specifyType('*', $left, $right)->describe(VerbosityLevel::precise()),
        );
    }

    public function testIntegerRangesDistinguishPartialAndGuaranteedOverflow(): void
    {
        $almostMax = UnitIntegerTypeHelper::create($this->unit('meter'), PHP_INT_MAX - 1, PHP_INT_MAX);
        $one = UnitIntegerTypeHelper::create($this->unit('meter'), 1, 1);
        $max = UnitIntegerTypeHelper::create($this->unit('meter'), PHP_INT_MAX, PHP_INT_MAX);

        $partial = $this->extension->specifyType('+', $almostMax, $one);
        $guaranteed = $this->extension->specifyType('+', $max, $one);

        $this->assertInstanceOf(BenevolentUnionType::class, $partial);
        $this->assertSame(
            sprintf("(%d&unit_int<'meter'>|unit_float<'meter'>)", PHP_INT_MAX),
            $partial->describe(VerbosityLevel::precise()),
        );
        $this->assertInstanceOf(UnitFloatType::class, $guaranteed);
        $this->assertSame("unit_float<'meter'>", $guaranteed->describe(VerbosityLevel::precise()));
    }

    public function testSubtractionMultiplicationAndPowerCanProveGuaranteedOverflow(): void
    {
        $unit = $this->unit('meter');
        $minimum = UnitIntegerTypeHelper::create($unit, PHP_INT_MIN, PHP_INT_MIN);
        $maximum = UnitIntegerTypeHelper::create($unit, PHP_INT_MAX, PHP_INT_MAX);
        $one = UnitIntegerTypeHelper::create($unit, 1, 1);

        $subtraction = $this->extension->specifyType('-', $minimum, $one);
        $multiplication = $this->extension->specifyType('*', $maximum, new ConstantIntegerType(2));
        $power = $this->extension->specifyType('**', $maximum, new ConstantIntegerType(2));

        $this->assertSame("unit_float<'meter'>", $subtraction->describe(VerbosityLevel::precise()));
        $this->assertSame("unit_float<'meter'>", $multiplication->describe(VerbosityLevel::precise()));
        $this->assertSame("unit_float<'meter ^ 2'>", $power->describe(VerbosityLevel::precise()));
    }

    public function testDisablingOverflowPromotionWidensUnsafeRangeResults(): void
    {
        $extension = new UnitOperatorTypeSpecifyingExtension(false);
        $unit = $this->unit('meter');
        $maximum = UnitIntegerTypeHelper::create($unit, PHP_INT_MAX, PHP_INT_MAX);
        $one = UnitIntegerTypeHelper::create($unit, 1, 1);

        $result = $extension->specifyType('+', $maximum, $one);

        $this->assertInstanceOf(UnitIntegerType::class, $result);
        $this->assertSame("unit_int<'meter'>", $result->describe(VerbosityLevel::precise()));
    }

    public function testPositivePowersPreserveSignedRangeBounds(): void
    {
        $base = UnitIntegerTypeHelper::create($this->unit('meter'), -2, 3);

        $square = $this->extension->specifyType('**', $base, new ConstantIntegerType(2));
        $cube = $this->extension->specifyType('**', $base, new ConstantIntegerType(3));

        $this->assertSame(
            "unit_int<'meter ^ 2'>&int<0, 9>",
            $square->describe(VerbosityLevel::precise()),
        );
        $this->assertSame(
            "unit_int<'meter ^ 3'>&int<-8, 27>",
            $cube->describe(VerbosityLevel::precise()),
        );
    }

    public function testKnownModuloProducesBrandedConstant(): void
    {
        $left = UnitIntegerTypeHelper::create($this->unit('meter'), 17, 17);
        $right = UnitIntegerTypeHelper::create($this->unit('meter'), 5, 5);

        $result = $this->extension->specifyType('%', $left, $right);

        $this->assertSame("2&unit_int<'meter'>", $result->describe(VerbosityLevel::precise()));
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
        $positiveBoundary = $this->extension->specifyType(
            '**',
            $this->unitFloat('meter'),
            new ConstantIntegerType(10_000),
        );
        $negativeBoundary = $this->extension->specifyType(
            '**',
            $this->unitFloat('meter'),
            new ConstantIntegerType(-10_000),
        );
        $result = $this->extension->specifyType('**', $this->unitFloat('meter'), new ConstantIntegerType(10_001));

        $this->assertSame("unit_float<'meter ^ 10000'>", $positiveBoundary->describe(VerbosityLevel::precise()));
        $this->assertSame("unit_float<'1 / meter ^ 10000'>", $negativeBoundary->describe(VerbosityLevel::precise()));
        $this->assertInstanceOf(ErrorType::class, $result);
        $this->assertStringContainsString('-10000 through 10000', $result->getReason() ?? '');
    }

    public function testPowRejectsDerivedUnitExponentOverflow(): void
    {
        $result = $this->extension->specifyType(
            '**',
            $this->unitFloat('meter ^ 10000'),
            new ConstantIntegerType(2),
        );

        $this->assertInstanceOf(ErrorType::class, $result);
        $this->assertSame(
            'Unit exponentiation produces a unit outside the supported exponent range.',
            $result->getReason(),
        );
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
        $this->assertSame(
            'Cannot use % with units meter and second because they are not definitionally equivalent.',
            $result->getReason(),
        );
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

    private function unitConstantFloat(float $value, string $unit): UnitConstantFloatType
    {
        return new UnitConstantFloatType($value, $this->unit($unit));
    }

    private function unitNumericString(string $unit): UnitNumericStringType
    {
        return new UnitNumericStringType($this->unit($unit));
    }

    private function unit(string $unit): \jbboehr\Yumemi\PHPStan\UnitExpression
    {
        $parsed = (new UnitExpressionParser())->parse($unit);
        $this->assertTrue($parsed->isOk(), $parsed->errorMessage() ?? '');

        return $parsed->expression();
    }
}
