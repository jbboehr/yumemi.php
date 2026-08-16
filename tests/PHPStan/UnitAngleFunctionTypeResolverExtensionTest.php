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

use jbboehr\Yumemi\Expr\Unit;
use jbboehr\Yumemi\PHPStan\UnitAngleFunctionTypeResolverExtension;
use jbboehr\Yumemi\PHPStan\UnitConstantFloatType;
use jbboehr\Yumemi\PHPStan\UnitConstantIntegerType;
use jbboehr\Yumemi\PHPStan\UnitExpression;
use jbboehr\Yumemi\PHPStan\UnitExpressionParser;
use jbboehr\Yumemi\PHPStan\UnitFloatType;
use jbboehr\Yumemi\PHPStan\UnitIntegerType;
use jbboehr\Yumemi\PHPStan\UnitIntegerTypeHelper;
use jbboehr\Yumemi\PHPStan\UnitNumericStringType;
use jbboehr\Yumemi\Registry\UnitRegistry;
use jbboehr\Yumemi\Registry\UnitRegistryBuilder;
use jbboehr\Yumemi\Units;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use PHPUnit\Framework\TestCase;

final class UnitAngleFunctionTypeResolverExtensionTest extends TestCase
{
    public function testIncompleteBareAndUnsupportedCallsRemainNeutral(): void
    {
        $extension = $this->extension('deg2rad');

        self::assertSame(
            ['type' => null, 'message' => null],
            $extension->analyseCall(new FuncCall(new Name('deg2rad')), $this->scope(new FloatType())),
        );
        self::assertSame(
            ['type' => null, 'message' => null],
            $this->analyse('deg2rad', new FloatType()),
        );
        self::assertSame(
            ['type' => null, 'message' => null],
            $this->analyse('atan2', new UnitFloatType($this->unit('radian'))),
        );
        self::assertSame(
            ['type' => null, 'message' => null],
            $this->analyseAtan2(new FloatType(), new FloatType()),
        );
    }

    public function testCanonicalAliasesConvertFiniteIntegerAndFloatConstants(): void
    {
        $radians = $this->analyse(
            'deg2rad',
            new UnitConstantIntegerType(180, $this->unit('degree')),
        );
        $degrees = $this->analyse(
            'rad2deg',
            new UnitConstantFloatType(M_PI, $this->unit('rad')),
        );
        $reducedDegrees = $this->analyse(
            'deg2rad',
            new UnitConstantIntegerType(180, $this->unit('2 * arc_degree / 2')),
        );

        self::assertSame(
            "3.141592653589793&unit_float<'radian'>",
            $radians['type']?->describe(VerbosityLevel::precise()),
        );
        self::assertSame(
            "180.0&unit_float<'arc_degree'>",
            $degrees['type']?->describe(VerbosityLevel::precise()),
        );
        self::assertSame(
            "3.141592653589793&unit_float<'radian'>",
            $reducedDegrees['type']?->describe(VerbosityLevel::precise()),
        );
        self::assertNull($radians['message']);
        self::assertNull($degrees['message']);
        self::assertNull($reducedDegrees['message']);
    }

    public function testGeneralBrandedNumbersReturnCanonicalFloatBrands(): void
    {
        $radians = $this->analyse('deg2rad', new UnitIntegerType($this->unit('arc_degree')));
        $degrees = $this->analyse('rad2deg', new UnitFloatType($this->unit('radian')));
        $ratio = $this->analyse('sin', new UnitIntegerType($this->unit('radian')));
        $inverse = $this->analyse('asin', new UnitFloatType($this->unit('1')));

        self::assertSame("unit_float<'radian'>", $radians['type']?->describe(VerbosityLevel::precise()));
        self::assertSame("unit_float<'arc_degree'>", $degrees['type']?->describe(VerbosityLevel::precise()));
        self::assertSame("unit_float<'1'>", $ratio['type']?->describe(VerbosityLevel::precise()));
        self::assertSame("unit_float<'radian'>", $inverse['type']?->describe(VerbosityLevel::precise()));
    }

    public function testDirectAndInverseTrigonometryRetainFiniteConstantResults(): void
    {
        foreach ([
            ['sin', 0.5, 'radian', sin(0.5), '1'],
            ['cos', 0.5, 'rad', cos(0.5), '1'],
            ['tan', 0.5, 'radian', tan(0.5), '1'],
            ['asin', 0.5, '1', asin(0.5), 'radian'],
            ['acos', 0.5, 'meter / meter', acos(0.5), 'radian'],
            ['atan', 1, 'second / second', atan(1), 'radian'],
        ] as [$functionName, $value, $inputUnit, $expected, $outputUnit]) {
            $input = is_int($value)
                ? new UnitConstantIntegerType($value, $this->unit($inputUnit))
                : new UnitConstantFloatType($value, $this->unit($inputUnit));
            $analysis = $this->analyse($functionName, $input);

            self::assertInstanceOf(UnitConstantFloatType::class, $analysis['type']);
            self::assertSame($expected, $analysis['type']->getValue());
            self::assertTrue($analysis['type']->getUnitExpression()->equals($this->unit($outputUnit)));
            self::assertNull($analysis['message']);
        }
    }

    public function testAtan2AcceptsDefinitionallyEquivalentBrandsAndRetainsFiniteConstants(): void
    {
        $constant = $this->analyseAtan2(
            new UnitConstantIntegerType(3, $this->unit('meter')),
            new UnitConstantIntegerType(4, $this->unit('100 * centimeter')),
        );
        $general = $this->analyseAtan2(
            new UnitIntegerType($this->unit('meter')),
            new UnitFloatType($this->unit('100 * centimeter')),
        );
        $ranged = $this->analyseAtan2(
            UnitIntegerTypeHelper::brand(IntegerRangeType::fromInterval(1, 5), $this->unit('meter')),
            new UnitConstantIntegerType(4, $this->unit('meter')),
        );
        $partiallyKnown = $this->analyseAtan2(
            new UnitConstantIntegerType(3, $this->unit('meter')),
            new UnitFloatType($this->unit('meter')),
        );

        self::assertInstanceOf(UnitConstantFloatType::class, $constant['type']);
        self::assertSame(atan2(3.0, 4.0), $constant['type']->getValue());
        self::assertTrue($constant['type']->getUnitExpression()->equals($this->unit('radian')));
        self::assertNull($constant['message']);
        self::assertSame("unit_float<'radian'>", $general['type']?->describe(VerbosityLevel::precise()));
        self::assertNull($general['message']);
        self::assertSame("unit_float<'radian'>", $ranged['type']?->describe(VerbosityLevel::precise()));
        self::assertSame("unit_float<'radian'>", $partiallyKnown['type']?->describe(VerbosityLevel::precise()));
    }

    public function testNonFiniteConstantsRetainOnlyTheOutputBrand(): void
    {
        foreach ([INF, NAN] as $value) {
            $analysis = $this->analyse(
                'deg2rad',
                new UnitConstantFloatType($value, $this->unit('arc_degree')),
            );

            self::assertSame("unit_float<'radian'>", $analysis['type']?->describe(VerbosityLevel::precise()));
            self::assertNull($analysis['message']);
        }

        $direct = $this->analyse('sin', new UnitConstantFloatType(INF, $this->unit('radian')));
        $outsideInverseDomain = $this->analyse('asin', new UnitConstantIntegerType(2, $this->unit('1')));
        $finiteAtInfinity = $this->analyse('atan', new UnitConstantFloatType(INF, $this->unit('1')));

        self::assertSame("unit_float<'1'>", $direct['type']?->describe(VerbosityLevel::precise()));
        self::assertSame("unit_float<'radian'>", $outsideInverseDomain['type']?->describe(VerbosityLevel::precise()));
        self::assertInstanceOf(UnitConstantFloatType::class, $finiteAtInfinity['type']);
        self::assertSame(atan(INF), $finiteAtInfinity['type']->getValue());
        self::assertTrue($finiteAtInfinity['type']->getUnitExpression()->equals($this->unit('radian')));

        $nonFiniteAtan2 = $this->analyseAtan2(
            new UnitConstantFloatType(NAN, $this->unit('meter')),
            new UnitConstantIntegerType(1, $this->unit('meter')),
        );
        $finiteAtan2 = $this->analyseAtan2(
            new UnitConstantFloatType(INF, $this->unit('meter')),
            new UnitConstantFloatType(INF, $this->unit('meter')),
        );

        self::assertSame("unit_float<'radian'>", $nonFiniteAtan2['type']?->describe(VerbosityLevel::precise()));
        self::assertInstanceOf(UnitConstantFloatType::class, $finiteAtan2['type']);
        self::assertSame(atan2(INF, INF), $finiteAtan2['type']->getValue());
        self::assertTrue($finiteAtan2['type']->getUnitExpression()->equals($this->unit('radian')));
    }

    public function testAtan2RejectsIncompatibleAndMixedOperands(): void
    {
        $incompatible = $this->analyseAtan2(
            new UnitFloatType($this->unit('meter')),
            new UnitFloatType($this->unit('second')),
        );
        $differentlyScaled = $this->analyseAtan2(
            new UnitFloatType($this->unit('meter')),
            new UnitFloatType($this->unit('foot')),
        );
        $mixedRight = $this->analyseAtan2(
            new UnitFloatType($this->unit('meter')),
            new FloatType(),
        );
        $mixedLeft = $this->analyseAtan2(
            new FloatType(),
            new UnitIntegerType($this->unit('meter')),
        );

        self::assertSame(
            'Cannot call atan2(): argument #1 has unit meter but argument #2 has unit second; they are not definitionally equivalent.',
            $incompatible['message'],
        );
        self::assertSame([
            'type' => null,
            'message' => 'Cannot call atan2(): argument #1 has unit meter but argument #2 has unit international_foot; they are not definitionally equivalent.',
        ], $differentlyScaled);
        self::assertSame([
            'type' => null,
            'message' => 'Cannot call atan2() with unit-bearing and unbranded operands; both operands need one definitionally equivalent unit.',
        ], $mixedRight);
        self::assertSame($mixedRight, $mixedLeft);
        self::assertNull($incompatible['type']);
    }

    public function testAtan2ValidatesEveryCartesianUnionPairAndKeepsOrdinaryResultsOrdinary(): void
    {
        $invalid = $this->analyseAtan2(
            new UnionType([
                new UnitFloatType($this->unit('meter')),
                new UnitFloatType($this->unit('second')),
            ]),
            new UnitFloatType($this->unit('meter')),
        );
        $valid = $this->analyseAtan2(
            new BenevolentUnionType([
                new UnitConstantIntegerType(0, $this->unit('meter')),
                new UnitConstantIntegerType(1, $this->unit('meter')),
            ]),
            new UnitConstantIntegerType(1, $this->unit('meter')),
        );
        $multipleResults = $this->analyseAtan2(
            new UnitConstantIntegerType(1, $this->unit('meter')),
            new UnionType([
                new UnitConstantIntegerType(1, $this->unit('meter')),
                new UnitConstantIntegerType(2, $this->unit('meter')),
            ]),
        );

        self::assertSame(
            'Cannot call atan2(): argument #1 has unit second but argument #2 has unit meter; they are not definitionally equivalent.',
            $invalid['message'],
        );
        self::assertNull($invalid['type']);
        self::assertInstanceOf(UnionType::class, $valid['type']);
        self::assertNotInstanceOf(BenevolentUnionType::class, $valid['type']);
        self::assertNull($valid['message']);
        self::assertInstanceOf(UnionType::class, $multipleResults['type']);
        self::assertCount(2, $multipleResults['type']->getTypes());
        self::assertNull($multipleResults['message']);
    }

    public function testAtan2NonnumericAlternativesRemainNeutral(): void
    {
        $array = new ArrayType(new FloatType(), new FloatType());
        $numericString = new UnitNumericStringType($this->unit('meter'));

        self::assertSame(
            ['type' => null, 'message' => null],
            $this->analyseAtan2($array, new UnitFloatType($this->unit('meter'))),
        );
        self::assertSame(
            ['type' => null, 'message' => null],
            $this->analyseAtan2(new UnitFloatType($this->unit('meter')), $array),
        );
        self::assertSame(
            ['type' => null, 'message' => null],
            $this->analyseAtan2($numericString, new UnitFloatType($this->unit('meter'))),
        );
    }

    public function testTrigFunctionsRejectNamedDimensionlessAndAngularLookalikes(): void
    {
        $direct = $this->analyse(
            'sin',
            new UnionType([
                new UnitFloatType($this->unit('arc_degree')),
                new UnitFloatType($this->unit('steradian')),
            ]),
        );
        $inverse = $this->analyse(
            'asin',
            new UnionType([
                new UnitFloatType($this->unit('count')),
                new UnitFloatType($this->unit('percent')),
                new UnitFloatType($this->unit('radian')),
            ]),
        );

        self::assertSame(
            'Cannot call sin() because at least one possible unit does not resolve canonically to radian: arc_degree, steradian.',
            $direct['message'],
        );
        self::assertSame(
            'Cannot call asin() because at least one possible unit does not resolve canonically to the exact unscaled ratio 1: count, percent, radian.',
            $inverse['message'],
        );
        self::assertNull($direct['type']);
        self::assertNull($inverse['type']);
    }

    public function testReducedRatiosAreAcceptedButBareAlternativesRemainNeutral(): void
    {
        $reduced = $this->analyse('acos', new UnitFloatType($this->unit('kilometer / kilometer')));
        $mixed = $this->analyse(
            'atan',
            new UnionType([
                new FloatType(),
                new UnitFloatType($this->unit('1')),
            ]),
        );

        self::assertSame("unit_float<'radian'>", $reduced['type']?->describe(VerbosityLevel::precise()));
        self::assertNull($reduced['message']);
        self::assertSame(['type' => null, 'message' => null], $mixed);
    }

    public function testInverseTrigonometryRejectsScaledDimensionlessRatios(): void
    {
        foreach (['2', '2 * meter / meter'] as $unit) {
            $analysis = $this->analyse('asin', new UnitFloatType($this->unit($unit)));

            self::assertSame(
                'Cannot call asin() because at least one possible unit does not resolve canonically to the exact unscaled ratio 1: 2.',
                $analysis['message'],
            );
            self::assertNull($analysis['type']);
        }
    }

    public function testDefinitionallyEquivalentNamedRatioAlternativeFailsClosedInEitherOrder(): void
    {
        $unscaled = new UnitFloatType($this->unit('1'));
        $count = new UnitFloatType($this->unit('count'));
        self::assertTrue($unscaled->accepts($count, true)->yes());
        self::assertTrue($unscaled->isSuperTypeOf($count)->maybe());

        foreach ([[$unscaled, $count], [$count, $unscaled]] as $types) {
            $union = TypeCombinator::union(...$types);
            self::assertInstanceOf(UnionType::class, $union);

            $analysis = $this->analyse('asin', $union);
            self::assertSame(
                'Cannot call asin() because at least one possible unit does not resolve canonically to the exact unscaled ratio 1: count.',
                $analysis['message'],
            );
            self::assertNull($analysis['type']);
        }
    }

    public function testEqualScaleAndDimensionlessLookalikesAreRejected(): void
    {
        $degrees = $this->analyse(
            'deg2rad',
            new UnionType([
                new UnitFloatType($this->unit('degree_north')),
                new UnitFloatType($this->unit('turn')),
            ]),
        );
        $radians = $this->analyse(
            'rad2deg',
            new UnionType([
                new UnitFloatType($this->unit('steradian')),
                new UnitFloatType($this->unit('count')),
            ]),
        );

        self::assertSame(
            'Cannot call deg2rad() because at least one possible unit does not resolve canonically to arc_degree: degree_north, turn.',
            $degrees['message'],
        );
        self::assertSame(
            'Cannot call rad2deg() because at least one possible unit does not resolve canonically to radian: count, steradian.',
            $radians['message'],
        );
        self::assertNull($degrees['type']);
        self::assertNull($radians['type']);
    }

    public function testInvalidBrandedArmIsNotHiddenByValidOrBareNumericArm(): void
    {
        $validAndInvalid = $this->analyse(
            'deg2rad',
            new UnionType([
                new UnitFloatType($this->unit('arc_degree')),
                new UnitFloatType($this->unit('degree_north')),
            ]),
        );
        $bareAndInvalid = $this->analyse(
            'rad2deg',
            new UnionType([
                new FloatType(),
                new UnitFloatType($this->unit('steradian')),
            ]),
        );

        self::assertSame(
            'Cannot call deg2rad() because at least one possible unit does not resolve canonically to arc_degree: degree_north.',
            $validAndInvalid['message'],
        );
        self::assertSame(
            'Cannot call rad2deg() because at least one possible unit does not resolve canonically to radian: steradian.',
            $bareAndInvalid['message'],
        );

        $duplicateInvalid = $this->analyse(
            'deg2rad',
            new UnionType([
                new UnitFloatType($this->unit('degree_north')),
                new UnitIntegerType($this->unit('degree_north')),
            ]),
        );
        self::assertSame(
            'Cannot call deg2rad() because at least one possible unit does not resolve canonically to arc_degree: degree_north.',
            $duplicateInvalid['message'],
        );
    }

    public function testDefinitionallyEquivalentNominalAlternativesFailClosedInEitherOrder(): void
    {
        $canonical = new UnitFloatType($this->unit('arc_degree'));
        $directional = new UnitFloatType($this->unit('degree_north'));
        self::assertTrue($canonical->accepts($directional, true)->yes());
        self::assertTrue($canonical->isSuperTypeOf($directional)->maybe());

        foreach ([[$canonical, $directional], [$directional, $canonical]] as $types) {
            $union = TypeCombinator::union(...$types);
            self::assertInstanceOf(UnionType::class, $union);

            $analysis = $this->analyse('deg2rad', $union);
            self::assertSame(
                'Cannot call deg2rad() because at least one possible unit does not resolve canonically to arc_degree: degree_north.',
                $analysis['message'],
            );
            self::assertNull($analysis['type']);
        }

        foreach ([
            [
                new UnitIntegerType($this->unit('arc_degree')),
                new UnitIntegerType($this->unit('degree_north')),
            ],
            [
                new UnitConstantFloatType(90.0, $this->unit('arc_degree')),
                new UnitConstantFloatType(90.0, $this->unit('degree_north')),
            ],
            [
                new UnitConstantIntegerType(90, $this->unit('arc_degree')),
                new UnitConstantIntegerType(90, $this->unit('degree_north')),
            ],
        ] as $types) {
            self::assertInstanceOf(UnionType::class, TypeCombinator::union(...$types));
        }
    }

    public function testBareNumericAndNonnumericAlternativesDeferToPhpstan(): void
    {
        $mixedNumeric = $this->analyse(
            'deg2rad',
            new UnionType([
                new FloatType(),
                new UnitFloatType($this->unit('arc_degree')),
            ]),
        );
        $nonnumeric = $this->analyse(
            'deg2rad',
            new UnionType([
                new ArrayType(new FloatType(), new FloatType()),
                new UnitFloatType($this->unit('arc_degree')),
            ]),
        );
        $invalidAndNonnumeric = $this->analyse(
            'deg2rad',
            new UnionType([
                new UnitFloatType($this->unit('degree_north')),
                new ArrayType(new FloatType(), new FloatType()),
            ]),
        );
        $nonnumericAndInvalid = $this->analyse(
            'deg2rad',
            new UnionType([
                new ArrayType(new FloatType(), new FloatType()),
                new UnitFloatType($this->unit('degree_north')),
            ]),
        );

        self::assertSame(['type' => null, 'message' => null], $mixedNumeric);
        self::assertSame(['type' => null, 'message' => null], $nonnumeric);
        self::assertSame(['type' => null, 'message' => null], $invalidAndNonnumeric);
        self::assertSame(['type' => null, 'message' => null], $nonnumericAndInvalid);
    }

    public function testNumericStringBrandRemainsNeutral(): void
    {
        self::assertSame(
            ['type' => null, 'message' => null],
            $this->analyse('deg2rad', new UnitNumericStringType($this->unit('arc_degree'))),
        );
    }

    public function testUnaryAngleFunctionsPreserveOnlyOriginalBenevolentUnions(): void
    {
        $arms = [
            new UnitConstantIntegerType(90, $this->unit('degree')),
            new UnitConstantIntegerType(180, $this->unit('arc_degree')),
        ];
        $ordinary = $this->analyse('deg2rad', new UnionType($arms));
        $benevolent = $this->analyse('deg2rad', new BenevolentUnionType($arms));

        self::assertInstanceOf(UnionType::class, $ordinary['type']);
        self::assertNotInstanceOf(BenevolentUnionType::class, $ordinary['type']);
        self::assertInstanceOf(BenevolentUnionType::class, $benevolent['type']);
        self::assertNull($ordinary['message']);
        self::assertNull($benevolent['message']);
    }

    public function testBenevolentUnionMayCollapseToOneConstantResult(): void
    {
        $analysis = $this->analyse(
            'deg2rad',
            new BenevolentUnionType([
                new UnitConstantIntegerType(180, $this->unit('degree')),
                new UnitConstantFloatType(180.0, $this->unit('arc_degree')),
            ]),
        );

        self::assertSame(
            "3.141592653589793&unit_float<'radian'>",
            $analysis['type']?->describe(VerbosityLevel::precise()),
        );
        self::assertNull($analysis['message']);
    }

    public function testCustomAliasToVerifiedCanonicalEntryIsAccepted(): void
    {
        $registry = UnitRegistryBuilder::default()
            ->alias('ordinary_degree', 'arc_degree')
            ->build();
        $parser = new UnitExpressionParser(new Units($registry));
        $analysis = $this->analyse(
            'deg2rad',
            new UnitConstantIntegerType(180, $this->unit('ordinary_degree', $parser)),
            $registry,
            $parser,
        );

        self::assertSame(
            "3.141592653589793&unit_float<'radian'>",
            $analysis['type']?->describe(VerbosityLevel::precise()),
        );
        self::assertNull($analysis['message']);
    }

    public function testSemanticallyIdenticalReplacementEntryIsAccepted(): void
    {
        $registry = UnitRegistryBuilder::default()
            ->define('arc_degree = (pi/180) rad')
            ->build();
        $parser = new UnitExpressionParser(new Units($registry));
        $analysis = $this->analyse(
            'deg2rad',
            new UnitConstantIntegerType(180, $this->unit('arc_degree', $parser)),
            $registry,
            $parser,
        );

        self::assertSame(
            "3.141592653589793&unit_float<'radian'>",
            $analysis['type']?->describe(VerbosityLevel::precise()),
        );
        self::assertNull($analysis['message']);
    }

    public function testMissingOrRedefinedCanonicalEntriesDisableAngleInference(): void
    {
        $registries = [
            UnitRegistryBuilder::empty()->define('widget = 1')->build(),
            UnitRegistryBuilder::default()->define('radian = count')->build(),
            UnitRegistryBuilder::default()->define('radian = 1')->build(),
            UnitRegistryBuilder::default()->define('arc_degree = degree_north')->build(),
            UnitRegistryBuilder::default()->define('pi = 2')->build(),
            UnitRegistryBuilder::default()->define('pi = missing_angle_constant')->build(),
            UnitRegistryBuilder::default()->alias('rad', 'meter')->build(),
            UnitRegistryBuilder::default()->add(new Unit('radian'))->build(),
        ];

        foreach ($registries as $registry) {
            $parser = new UnitExpressionParser(new Units($registry));
            $analysis = $this->analyse(
                'deg2rad',
                new UnitFloatType($this->unit('arc_degree')),
                $registry,
                $parser,
            );
            $atan2Analysis = $this->analyseAtan2(
                new UnitFloatType($this->unit('meter')),
                new UnitFloatType($this->unit('meter')),
                $registry,
                $parser,
            );

            self::assertSame(['type' => null, 'message' => null], $analysis);
            self::assertSame(['type' => null, 'message' => null], $atan2Analysis);
        }
    }

    /** @return array{type: Type|null, message: string|null} */
    private function analyse(
        string $functionName,
        Type $type,
        ?UnitRegistry $registry = null,
        ?UnitExpressionParser $parser = null,
    ): array {
        $value = new Variable('value');

        return $this->extension($functionName, $registry, $parser)->analyseCall(
            new FuncCall(new Name($functionName), [new Arg($value)]),
            $this->scope($type),
        );
    }

    /** @return array{type: Type|null, message: string|null} */
    private function analyseAtan2(
        Type $yType,
        Type $xType,
        ?UnitRegistry $registry = null,
        ?UnitExpressionParser $parser = null,
    ): array {
        $y = new Variable('y');
        $x = new Variable('x');
        $scope = self::createStub(Scope::class);
        $scope->method('getType')->willReturnCallback(
            static fn (Expr $expr): Type => $expr === $y ? $yType : $xType,
        );

        return $this->extension('atan2', $registry, $parser)->analyseCall(
            new FuncCall(new Name('atan2'), [new Arg($y), new Arg($x)]),
            $scope,
        );
    }

    private function extension(
        string $functionName,
        ?UnitRegistry $registry = null,
        ?UnitExpressionParser $parser = null,
    ): UnitAngleFunctionTypeResolverExtension {
        $function = self::createStub(FunctionReflection::class);
        $function->method('getName')->willReturn($functionName);

        $reflectionProvider = self::createStub(ReflectionProvider::class);
        $reflectionProvider->method('hasFunction')->willReturn(true);
        $reflectionProvider->method('getFunction')->willReturn($function);

        $registry ??= UnitRegistry::bundled();
        $parser ??= new UnitExpressionParser(new Units($registry));

        return new UnitAngleFunctionTypeResolverExtension($reflectionProvider, $parser, $registry);
    }

    private function scope(Type $type): Scope
    {
        $scope = self::createStub(Scope::class);
        $scope->method('getType')->willReturn($type);

        return $scope;
    }

    private function unit(string $unit, ?UnitExpressionParser $parser = null): UnitExpression
    {
        $result = ($parser ?? new UnitExpressionParser())->parse($unit);
        self::assertTrue($result->isOk(), $result->errorMessage() ?? $unit);

        return $result->expression();
    }
}
