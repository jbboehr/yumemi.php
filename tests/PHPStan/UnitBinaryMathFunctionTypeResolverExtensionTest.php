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

use jbboehr\Yumemi\PHPStan\UnitBinaryMathFunctionTypeResolverExtension;
use jbboehr\Yumemi\PHPStan\UnitConstantFloatType;
use jbboehr\Yumemi\PHPStan\UnitConstantIntegerType;
use jbboehr\Yumemi\PHPStan\UnitExpression;
use jbboehr\Yumemi\PHPStan\UnitExpressionParser;
use jbboehr\Yumemi\PHPStan\UnitFloatType;
use jbboehr\Yumemi\PHPStan\UnitIntegerType;
use jbboehr\Yumemi\PHPStan\UnitIntegerTypeHelper;
use jbboehr\Yumemi\PHPStan\UnitOperatorTypeSpecifyingExtension;
use PhpParser\Node\Arg;
use PhpParser\Node\Identifier;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use PHPUnit\Framework\TestCase;

final class UnitBinaryMathFunctionTypeResolverExtensionTest extends TestCase
{
    public function testIncompleteCallReturnsNeutralAnalysis(): void
    {
        $extension = $this->extension('fdiv');
        $analysis = $extension->analyseCall(
            new FuncCall(new Name('fdiv')),
            $this->scope(new FloatType(), new FloatType()),
        );
        $left = new Variable('left');
        $missingRight = $extension->analyseCall(
            new FuncCall(new Name('fdiv'), [new Arg($left)]),
            $this->scope(new FloatType(), new FloatType()),
        );
        $power = $this->extension('pow');
        $missingExponent = $power->analyseCall(
            new FuncCall(new Name('pow'), [new Arg($left)]),
            $this->scope(new UnitFloatType($this->unit('meter')), new IntegerType()),
        );

        self::assertSame(['type' => null, 'message' => null], $analysis);
        self::assertSame(['type' => null, 'message' => null], $missingRight);
        self::assertSame(['type' => null, 'message' => null], $missingExponent);
    }

    public function testBareOperandsRemainOwnedByNativePhpstan(): void
    {
        $analysis = $this->analyse('fdiv', new FloatType(), new FloatType());

        self::assertSame(['type' => null, 'message' => null], $analysis);
    }

    public function testPowerMatchesNativeOperatorUnitAndConstantSemantics(): void
    {
        $square = $this->analyse(
            'pow',
            new UnitConstantIntegerType(3, $this->unit('meter')),
            new ConstantIntegerType(2),
        );
        $reciprocal = $this->analyse(
            'pow',
            new UnitConstantIntegerType(2, $this->unit('second')),
            new ConstantIntegerType(-1),
        );
        $zero = $this->analyse(
            'pow',
            new UnitConstantFloatType(0.0, $this->unit('meter')),
            new ConstantIntegerType(0),
        );
        $undefinedReciprocal = $this->analyse(
            'pow',
            new UnitConstantIntegerType(0, $this->unit('meter')),
            new ConstantIntegerType(-1),
        );

        self::assertSame("9&unit_int<'meter ^ 2'>", $square['type']?->describe(VerbosityLevel::precise()));
        self::assertSame("0.5&unit_float<'1 / second'>", $reciprocal['type']?->describe(VerbosityLevel::precise()));
        self::assertSame("1.0&unit_float<'1'>", $zero['type']?->describe(VerbosityLevel::precise()));
        self::assertSame(
            "unit_float<'1 / meter'>",
            $undefinedReciprocal['type']?->describe(VerbosityLevel::precise()),
        );
        self::assertNull($square['message']);
        self::assertNull($reciprocal['message']);
        self::assertNull($zero['message']);
        self::assertNull($undefinedReciprocal['message']);
    }

    public function testPowerResolvesNativeNamedArguments(): void
    {
        $base = new Variable('left');
        $exponent = new Variable('right');
        $analysis = $this->extension('pow')->analyseCall(
            new FuncCall(new Name('pow'), [
                new Arg($exponent, name: new Identifier('exponent')),
                new Arg($base, name: new Identifier('num')),
            ]),
            $this->scope(
                new UnitConstantIntegerType(3, $this->unit('meter')),
                new ConstantIntegerType(2),
            ),
        );

        self::assertSame("9&unit_int<'meter ^ 2'>", $analysis['type']?->describe(VerbosityLevel::precise()));
        self::assertNull($analysis['message']);
    }

    public function testPowerPreservesFiniteExponentAlternativesAndIntegerOverflowPolicy(): void
    {
        $exponents = TypeCombinator::union(new ConstantIntegerType(2), new ConstantIntegerType(3));
        $alternatives = $this->analyse('pow', new UnitFloatType($this->unit('meter')), $exponents);
        $default = $this->analyse('pow', new UnitIntegerType($this->unit('meter')), new ConstantIntegerType(2));
        $strict = $this->analyse(
            'pow',
            new UnitIntegerType($this->unit('meter')),
            new ConstantIntegerType(2),
            false,
        );

        self::assertSame(
            "unit_float<'meter ^ 2'>|unit_float<'meter ^ 3'>",
            $alternatives['type']?->describe(VerbosityLevel::precise()),
        );
        self::assertInstanceOf(BenevolentUnionType::class, $default['type']);
        self::assertSame("unit_int<'meter ^ 2'>", $strict['type']?->describe(VerbosityLevel::precise()));
    }

    public function testPowerRejectsInvalidOrUnrepresentableExponents(): void
    {
        $dynamic = $this->analyse(
            'pow',
            new UnitFloatType($this->unit('meter')),
            new IntegerType(),
        );
        $branded = $this->analyse(
            'pow',
            new UnitFloatType($this->unit('meter')),
            new UnitConstantIntegerType(2, $this->unit('second')),
        );
        $mixedBase = $this->analyse(
            'pow',
            new UnionType([
                new UnitFloatType($this->unit('meter')),
                new FloatType(),
            ]),
            new ConstantIntegerType(2),
        );
        $outsidePolicy = $this->analyse(
            'pow',
            new UnitFloatType($this->unit('meter')),
            new ConstantIntegerType(10_001),
        );
        $boundary = $this->analyse(
            'pow',
            new UnitFloatType($this->unit('meter')),
            new ConstantIntegerType(10_000),
        );
        $derivedOverflow = $this->analyse(
            'pow',
            new UnitFloatType($this->unit('meter ^ 10000')),
            new ConstantIntegerType(2),
        );

        self::assertSame(
            'Cannot call pow(): unit exponentiation requires a constant integer exponent (e.g. $length ** 2).',
            $dynamic['message'],
        );
        self::assertSame(
            'Cannot call pow(): cannot raise a value to a unit power; the exponent must be a bare integer.',
            $branded['message'],
        );
        self::assertSame(
            'Cannot call pow(): cannot raise a bare numeric value to a power involving units.',
            $mixedBase['message'],
        );
        self::assertSame(
            'Cannot call pow(): unit exponentiation supports exponents from -10000 through 10000.',
            $outsidePolicy['message'],
        );
        self::assertSame("unit_float<'meter ^ 10000'>", $boundary['type']?->describe(VerbosityLevel::precise()));
        self::assertNull($boundary['message']);
        self::assertSame(
            'Cannot call pow(): unit exponentiation produces a unit outside the supported exponent range.',
            $derivedOverflow['message'],
        );
        self::assertNull($dynamic['type']);
        self::assertNull($branded['type']);
        self::assertNull($mixedBase['type']);
        self::assertNull($outsidePolicy['type']);
        self::assertNull($derivedOverflow['type']);
    }

    public function testPowerDefersBareAndNonnumericCallsToNativePhpstan(): void
    {
        $array = new ArrayType(new FloatType(), new FloatType());

        self::assertSame(
            ['type' => null, 'message' => null],
            $this->analyse('pow', new ConstantIntegerType(2), new ConstantIntegerType(3)),
        );
        self::assertSame(
            ['type' => null, 'message' => null],
            $this->analyse('pow', new UnitFloatType($this->unit('meter')), $array),
        );
        self::assertSame(
            ['type' => null, 'message' => null],
            $this->analyse('pow', $array, new UnitConstantIntegerType(2, $this->unit('second'))),
        );
    }

    public function testIntegerDivisionAppliesQuotientUnitAlgebraAndTruncatesConstants(): void
    {
        $unitQuotient = $this->analyse(
            'intdiv',
            new UnitConstantIntegerType(7, $this->unit('meter')),
            new UnitConstantIntegerType(3, $this->unit('second')),
        );
        $brandedDividend = $this->analyse(
            'intdiv',
            new UnitConstantIntegerType(-7, $this->unit('meter')),
            new ConstantIntegerType(3),
        );
        $brandedDivisor = $this->analyse(
            'intdiv',
            new ConstantIntegerType(7),
            new UnitConstantIntegerType(-3, $this->unit('second')),
        );

        self::assertSame(
            "2&unit_int<'meter / second'>",
            $unitQuotient['type']?->describe(VerbosityLevel::precise()),
        );
        self::assertSame("-2&unit_int<'meter'>", $brandedDividend['type']?->describe(VerbosityLevel::precise()));
        self::assertSame(
            "-2&unit_int<'1 / second'>",
            $brandedDivisor['type']?->describe(VerbosityLevel::precise()),
        );
        self::assertNull($unitQuotient['message']);
        self::assertNull($brandedDividend['message']);
        self::assertNull($brandedDivisor['message']);
    }

    public function testIntegerDivisionResolvesNativeNamedArguments(): void
    {
        $left = new Variable('left');
        $right = new Variable('right');
        $analysis = $this->extension('intdiv')->analyseCall(
            new FuncCall(new Name('intdiv'), [
                new Arg($right, name: new Identifier('num2')),
                new Arg($left, name: new Identifier('num1')),
            ]),
            $this->scope(
                new UnitConstantIntegerType(7, $this->unit('meter')),
                new ConstantIntegerType(3),
            ),
        );

        self::assertSame("2&unit_int<'meter'>", $analysis['type']?->describe(VerbosityLevel::precise()));
        self::assertNull($analysis['message']);
    }

    public function testIntegerDivisionPreservesBoundedSuccessfulResults(): void
    {
        $analysis = $this->analyse(
            'intdiv',
            UnitIntegerTypeHelper::create($this->unit('meter'), -7, 7),
            new ConstantIntegerType(3),
        );

        self::assertSame("unit_int<'meter'>&int<-2, 2>", $analysis['type']?->describe(VerbosityLevel::precise()));
        self::assertNull($analysis['message']);
    }

    public function testIntegerDivisionKeepsAConservativeBrandForAlwaysExceptionalInputs(): void
    {
        $zero = $this->analyse(
            'intdiv',
            new UnitConstantIntegerType(7, $this->unit('meter')),
            new ConstantIntegerType(0),
        );
        $overflow = $this->analyse(
            'intdiv',
            new UnitConstantIntegerType(PHP_INT_MIN, $this->unit('meter')),
            new ConstantIntegerType(-1),
        );

        self::assertSame("unit_int<'meter'>", $zero['type']?->describe(VerbosityLevel::precise()));
        self::assertSame("unit_int<'meter'>", $overflow['type']?->describe(VerbosityLevel::precise()));
        self::assertNull($zero['message']);
        self::assertNull($overflow['message']);
    }

    public function testIntegerDivisionRejectsPossibleUnbrandedResults(): void
    {
        $analysis = $this->analyse(
            'intdiv',
            new UnionType([
                new UnitIntegerType($this->unit('meter')),
                new IntegerType(),
            ]),
            new ConstantIntegerType(2),
        );

        self::assertNull($analysis['type']);
        self::assertSame(
            'Cannot call intdiv() when a possible result is unbranded; every operand pairing must retain a unit.',
            $analysis['message'],
        );
    }

    public function testIntegerDivisionPreservesBenevolenceOnlyWithoutAnOrdinaryUnionSource(): void
    {
        $benevolentLeft = new BenevolentUnionType([
            new UnitConstantIntegerType(8, $this->unit('meter')),
            new UnitConstantIntegerType(9, $this->unit('second')),
        ]);
        $benevolent = $this->analyse('intdiv', $benevolentLeft, new ConstantIntegerType(2));
        $ordinary = $this->analyse(
            'intdiv',
            $benevolentLeft,
            new UnionType([
                new ConstantIntegerType(2),
                new ConstantIntegerType(3),
            ]),
        );

        self::assertInstanceOf(BenevolentUnionType::class, $benevolent['type']);
        self::assertInstanceOf(UnionType::class, $ordinary['type']);
        self::assertNotInstanceOf(BenevolentUnionType::class, $ordinary['type']);
        self::assertNull($benevolent['message']);
        self::assertNull($ordinary['message']);
    }

    public function testIntegerDivisionDefersBareAndInvalidNativeOperands(): void
    {
        $array = new ArrayType(new IntegerType(), new IntegerType());

        self::assertSame(
            ['type' => null, 'message' => null],
            $this->analyse('intdiv', new ConstantIntegerType(7), new ConstantIntegerType(3)),
        );
        self::assertSame(
            ['type' => null, 'message' => null],
            $this->analyse('intdiv', new UnitFloatType($this->unit('meter')), new ConstantIntegerType(3)),
        );
        self::assertSame(
            ['type' => null, 'message' => null],
            $this->analyse('intdiv', new UnitIntegerType($this->unit('meter')), $array),
        );
    }

    public function testIntegerDivisionReportsUnitExponentOverflow(): void
    {
        $analysis = $this->analyse(
            'intdiv',
            new UnitIntegerType($this->unit('meter ^ 10000')),
            new UnitIntegerType($this->unit('meter ^ -10000')),
        );

        self::assertNull($analysis['type']);
        self::assertSame(
            'Cannot call intdiv() because the resulting unit exceeds the supported exponent range.',
            $analysis['message'],
        );
    }

    public function testFloatDivisionCombinesUnitsAndPreservesFiniteConstants(): void
    {
        $analysis = $this->analyse(
            'fdiv',
            new UnitConstantIntegerType(6, $this->unit('meter')),
            new UnitConstantIntegerType(2, $this->unit('second')),
        );

        self::assertSame(
            "3.0&unit_float<'meter / second'>",
            $analysis['type']?->describe(VerbosityLevel::precise()),
        );
        self::assertNull($analysis['message']);
    }

    public function testFloatDivisionByZeroRetainsOnlyTheDerivedBrand(): void
    {
        $analysis = $this->analyse(
            'fdiv',
            new UnitConstantFloatType(1.0, $this->unit('meter')),
            new UnitConstantFloatType(0.0, $this->unit('second')),
        );

        self::assertSame(
            "unit_float<'meter / second'>",
            $analysis['type']?->describe(VerbosityLevel::precise()),
        );
    }

    public function testFloatDivisionPreservesBareConstantOperands(): void
    {
        $brandedDividend = $this->analyse(
            'fdiv',
            new UnitConstantIntegerType(6, $this->unit('meter')),
            new ConstantFloatType(2.0),
        );
        $brandedDivisor = $this->analyse(
            'fdiv',
            new ConstantFloatType(6.0),
            new UnitConstantIntegerType(2, $this->unit('second')),
        );

        self::assertSame(
            "3.0&unit_float<'meter'>",
            $brandedDividend['type']?->describe(VerbosityLevel::precise()),
        );
        self::assertSame(
            "3.0&unit_float<'1 / second'>",
            $brandedDivisor['type']?->describe(VerbosityLevel::precise()),
        );
    }

    public function testUnitDetectionIncludesEveryReachableUnionArm(): void
    {
        $unitAfterBareLeftArm = $this->analyse(
            'fmod',
            new UnionType([
                new FloatType(),
                new UnitIntegerType($this->unit('meter')),
            ]),
            new FloatType(),
        );
        $unitAfterBareRightArm = $this->analyse(
            'hypot',
            new FloatType(),
            new UnionType([
                new FloatType(),
                new UnitIntegerType($this->unit('meter')),
            ]),
        );

        self::assertSame(
            'Cannot call fmod() with unit-bearing and unbranded operands; both operands need one definitionally equivalent unit.',
            $unitAfterBareLeftArm['message'],
        );
        self::assertNull($unitAfterBareLeftArm['type']);
        self::assertSame(
            'Cannot call hypot() with unit-bearing and unbranded operands; both operands need one definitionally equivalent unit.',
            $unitAfterBareRightArm['message'],
        );
        self::assertNull($unitAfterBareRightArm['type']);
    }

    public function testFloatRemainderAndHypotenusePreserveEquivalentUnit(): void
    {
        $remainder = $this->analyse(
            'fmod',
            new UnitConstantIntegerType(7, $this->unit('meter')),
            new UnitConstantIntegerType(3, $this->unit('m')),
        );
        $hypotenuse = $this->analyse(
            'hypot',
            new UnitConstantIntegerType(3, $this->unit('meter')),
            new UnitConstantIntegerType(4, $this->unit('m')),
        );

        self::assertSame("1.0&unit_float<'meter'>", $remainder['type']?->describe(VerbosityLevel::precise()));
        self::assertSame("5.0&unit_float<'meter'>", $hypotenuse['type']?->describe(VerbosityLevel::precise()));
    }

    public function testFloatRemainderByZeroRetainsOnlyTheUnit(): void
    {
        $analysis = $this->analyse(
            'fmod',
            new UnitConstantIntegerType(7, $this->unit('meter')),
            new UnitConstantIntegerType(0, $this->unit('meter')),
        );

        self::assertSame("unit_float<'meter'>", $analysis['type']?->describe(VerbosityLevel::precise()));
        self::assertNull($analysis['message']);
    }

    public function testSameUnitFunctionsRejectBareAndInequivalentOperands(): void
    {
        $mixed = $this->analyse('hypot', new UnitFloatType($this->unit('meter')), new FloatType());
        $inequivalent = $this->analyse(
            'fmod',
            new UnitFloatType($this->unit('meter')),
            new UnitFloatType($this->unit('second')),
        );

        self::assertSame(
            'Cannot call hypot() with unit-bearing and unbranded operands; both operands need one definitionally equivalent unit.',
            $mixed['message'],
        );
        self::assertNull($mixed['type']);
        self::assertSame(
            'Cannot call fmod(): argument #1 has unit meter but argument #2 has unit second; they are not definitionally equivalent.',
            $inequivalent['message'],
        );
        self::assertNull($inequivalent['type']);
    }

    public function testSameUnitFunctionDiagnosticPreservesOperandOrder(): void
    {
        $analysis = $this->analyse(
            'fmod',
            new UnitFloatType($this->unit('second')),
            new UnitFloatType($this->unit('meter')),
        );

        self::assertSame(
            'Cannot call fmod(): argument #1 has unit second but argument #2 has unit meter; they are not definitionally equivalent.',
            $analysis['message'],
        );
    }

    public function testSameUnitFunctionFailsClosedAcrossUnionArms(): void
    {
        $analysis = $this->analyse(
            'hypot',
            TypeCombinator::union(
                new UnitIntegerType($this->unit('meter')),
                new FloatType(),
            ),
            new UnitFloatType($this->unit('meter')),
        );

        self::assertNull($analysis['type']);
        self::assertSame(
            'Cannot call hypot() with unit-bearing and unbranded operands; both operands need one definitionally equivalent unit.',
            $analysis['message'],
        );
    }

    public function testUnsupportedOperandFallsBackToNativeAnalysis(): void
    {
        $unsupportedRight = $this->analyse(
            'hypot',
            new UnitFloatType($this->unit('meter')),
            new ArrayType(new FloatType(), new FloatType()),
        );
        $unsupportedLeft = $this->analyse(
            'hypot',
            new ArrayType(new FloatType(), new FloatType()),
            new UnitFloatType($this->unit('meter')),
        );

        self::assertSame(['type' => null, 'message' => null], $unsupportedRight);
        self::assertSame(['type' => null, 'message' => null], $unsupportedLeft);
    }

    public function testFloatDivisionDefersNonnumericAlternatives(): void
    {
        $array = new ArrayType(new FloatType(), new FloatType());
        $unit = new UnitFloatType($this->unit('meter'));

        self::assertSame(['type' => null, 'message' => null], $this->analyse('fdiv', $array, $unit));
        self::assertSame(['type' => null, 'message' => null], $this->analyse('fdiv', $unit, $array));
    }

    public function testFloatDivisionReportsUnitExponentOverflow(): void
    {
        $analysis = $this->analyse(
            'fdiv',
            new UnitFloatType($this->unit('meter ^ 10000')),
            new UnitFloatType($this->unit('meter ^ -10000')),
        );

        self::assertNull($analysis['type']);
        self::assertSame(
            'Cannot call fdiv() because the resulting unit exceeds the supported exponent range.',
            $analysis['message'],
        );
    }

    public function testFloatDivisionPreservesBenevolentUnitUnion(): void
    {
        $analysis = $this->analyse(
            'fdiv',
            new BenevolentUnionType([
                new UnitFloatType($this->unit('meter')),
                new UnitFloatType($this->unit('second')),
            ]),
            new UnitConstantIntegerType(2, $this->unit('minute')),
        );

        self::assertInstanceOf(BenevolentUnionType::class, $analysis['type']);
        self::assertNull($analysis['message']);
    }

    public function testOrdinaryOperandUnionPreventsBenevolentResult(): void
    {
        $analysis = $this->analyse(
            'fdiv',
            new BenevolentUnionType([
                new UnitIntegerType($this->unit('meter')),
                new UnitFloatType($this->unit('meter')),
            ]),
            new UnionType([
                new UnitIntegerType($this->unit('second')),
                new UnitIntegerType($this->unit('minute')),
            ]),
        );

        self::assertInstanceOf(UnionType::class, $analysis['type']);
        self::assertNotInstanceOf(BenevolentUnionType::class, $analysis['type']);
        self::assertNull($analysis['message']);
    }

    public function testBenevolenceIsPreservedSymmetricallyAndNeverOverridesAnOrdinaryUnion(): void
    {
        $rightBenevolent = $this->analyse(
            'fdiv',
            new UnitFloatType($this->unit('meter')),
            new BenevolentUnionType([
                new UnitIntegerType($this->unit('second')),
                new UnitIntegerType($this->unit('minute')),
            ]),
        );
        $bothBenevolent = $this->analyse(
            'fdiv',
            new BenevolentUnionType([
                new UnitIntegerType($this->unit('meter')),
                new UnitIntegerType($this->unit('foot')),
            ]),
            new BenevolentUnionType([
                new UnitIntegerType($this->unit('second')),
                new UnitIntegerType($this->unit('minute')),
            ]),
        );
        $ordinaryLeft = $this->analyse(
            'fdiv',
            new UnionType([
                new UnitIntegerType($this->unit('meter')),
                new UnitIntegerType($this->unit('foot')),
            ]),
            new BenevolentUnionType([
                new UnitIntegerType($this->unit('second')),
                new UnitIntegerType($this->unit('minute')),
            ]),
        );

        self::assertInstanceOf(BenevolentUnionType::class, $rightBenevolent['type']);
        self::assertInstanceOf(BenevolentUnionType::class, $bothBenevolent['type']);
        self::assertInstanceOf(UnionType::class, $ordinaryLeft['type']);
        self::assertNotInstanceOf(BenevolentUnionType::class, $ordinaryLeft['type']);
    }

    public function testCartesianConstantUnionsRetainEveryResult(): void
    {
        $division = $this->analyse(
            'fdiv',
            new UnionType([
                new UnitConstantIntegerType(6, $this->unit('meter')),
                new UnitConstantIntegerType(8, $this->unit('meter')),
            ]),
            new UnionType([
                new UnitConstantIntegerType(2, $this->unit('second')),
                new UnitConstantIntegerType(4, $this->unit('second')),
            ]),
        );
        $hypotenuse = $this->analyse(
            'hypot',
            new UnionType([
                new UnitConstantIntegerType(3, $this->unit('meter')),
                new UnitConstantIntegerType(4, $this->unit('meter')),
            ]),
            new UnionType([
                new UnitConstantIntegerType(4, $this->unit('meter')),
                new UnitConstantIntegerType(5, $this->unit('meter')),
            ]),
        );

        self::assertSame([1.5, 2.0, 3.0, 4.0], $this->constantFloatValues($division['type']));
        self::assertCount(4, $this->constantFloatValues($hypotenuse['type']));
    }

    /** @return array{type: Type|null, message: string|null} */
    private function analyse(
        string $function,
        Type $leftType,
        Type $rightType,
        bool $integerOverflowToFloat = true,
    ): array {
        $left = new Variable('left');
        $right = new Variable('right');

        return $this->extension($function, $integerOverflowToFloat)->analyseCall(
            new FuncCall(new Name($function), [new Arg($left), new Arg($right)]),
            $this->scope($leftType, $rightType),
        );
    }

    private function extension(
        string $functionName,
        bool $integerOverflowToFloat = true,
    ): UnitBinaryMathFunctionTypeResolverExtension {
        $function = self::createStub(FunctionReflection::class);
        $function->method('getName')->willReturn($functionName);

        $reflectionProvider = self::createStub(ReflectionProvider::class);
        $reflectionProvider->method('hasFunction')->willReturn(true);
        $reflectionProvider->method('getFunction')->willReturn($function);

        return new UnitBinaryMathFunctionTypeResolverExtension(
            $reflectionProvider,
            new UnitOperatorTypeSpecifyingExtension($integerOverflowToFloat),
        );
    }

    private function scope(Type $leftType, Type $rightType): Scope
    {
        $scope = self::createStub(Scope::class);
        $scope->method('getType')->willReturnCallback(
            static fn (Variable $variable): Type => $variable->name === 'left' ? $leftType : $rightType,
        );

        return $scope;
    }

    private function unit(string $unit): UnitExpression
    {
        $result = (new UnitExpressionParser())->parse($unit);
        self::assertTrue($result->isOk(), $result->errorMessage() ?? $unit);

        return $result->expression();
    }

    /** @return list<float> */
    private function constantFloatValues(?Type $type): array
    {
        self::assertNotNull($type);
        $types = $type instanceof UnionType ? $type->getTypes() : [$type];
        $values = [];
        foreach ($types as $constant) {
            self::assertInstanceOf(ConstantFloatType::class, $constant);
            $values[] = $constant->getValue();
        }
        sort($values);

        return $values;
    }
}
