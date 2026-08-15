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
use jbboehr\Yumemi\PHPStan\UnitFloatType;
use jbboehr\Yumemi\PHPStan\UnitIntegerType;
use jbboehr\Yumemi\PHPStan\UnitIntegerTypeHelper;
use jbboehr\Yumemi\PHPStan\UnitNumericStringType;
use PHPStan\Type\Accessory\AccessoryLiteralStringType;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\StringType;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
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

    public function testNumericStringTypeDescribesUnitAndRetainsItsNativeCarrier(): void
    {
        $type = $this->unitNumericString('second');

        $this->assertSame("unit_numeric_string<'second'>", $type->describe(VerbosityLevel::precise()));
        $this->assertTrue($type->isString()->yes());
        $this->assertTrue($type->isNumericString()->yes());
        $this->assertTrue($type->isNonEmptyString()->yes());
        $this->assertTrue($type->isClassString()->no());
        $this->assertTrue($type->isInteger()->no());
        $this->assertTrue($type->isFloat()->no());
    }

    public function testNumericStringUnitRelationshipsRequireAUnitBrand(): void
    {
        $seconds = $this->unitNumericString('second');
        $equivalentSeconds = $this->unitNumericString('1000 * millisecond');
        $meters = $this->unitNumericString('meter');
        $bareNumericString = TypeCombinator::intersect(
            new StringType(),
            new AccessoryNumericStringType(),
        );

        $this->assertTrue($seconds->equals($equivalentSeconds));
        $this->assertFalse($seconds->equals($meters));
        $this->assertTrue($seconds->accepts($equivalentSeconds, true)->yes());
        $differentUnit = $seconds->accepts($meters, true);
        $bareNumeric = $seconds->accepts($bareNumericString, true);
        $bareString = $seconds->accepts(new StringType(), true);

        $this->assertTrue($differentUnit->no());
        $this->assertSame([
            "Unit unit_numeric_string<'meter'> is not assignable to unit_numeric_string<'second'> "
                . '(normalized forms differ).',
        ], $differentUnit->reasons);
        $this->assertTrue($bareNumeric->no());
        $this->assertSame([
            "Bare numeric string is not assignable to unit_numeric_string<'second'>; keep the unit annotation.",
        ], $bareNumeric->reasons);
        $this->assertTrue($bareString->no());
        $this->assertSame([
            "Bare string is not assignable to unit_numeric_string<'second'>; keep the unit annotation.",
        ], $bareString->reasons);
        $this->assertTrue($seconds->isSuperTypeOf($equivalentSeconds)->yes());
        $this->assertTrue($seconds->isSuperTypeOf($meters)->no());
        $this->assertTrue($seconds->isSuperTypeOf($bareNumericString)->no());
        $this->assertTrue($bareNumericString->accepts($seconds, true)->yes());
    }

    public function testNumericStringRequiresExplicitCastToPreserveItsUnit(): void
    {
        $seconds = $this->unitNumericString('second');
        $numericStringUnion = new UnionType([$seconds, $this->unitNumericString('meter')]);

        $this->assertSame("unit_int<'second'>", $seconds->toInteger()->describe(VerbosityLevel::precise()));
        $this->assertSame("unit_float<'second'>", $seconds->toFloat()->describe(VerbosityLevel::precise()));
        $this->assertStringNotContainsString('unit_', $seconds->toNumber()->describe(VerbosityLevel::precise()));
        $this->assertStringNotContainsString(
            'unit_int',
            $seconds->toCoercedArgumentType(false)->describe(VerbosityLevel::precise()),
        );
        $this->assertStringNotContainsString(
            'unit_float',
            $seconds->toCoercedArgumentType(false)->describe(VerbosityLevel::precise()),
        );
        $this->assertSame($seconds, $seconds->toCoercedArgumentType(true));
        $this->assertTrue($this->unitInt('second')->accepts($seconds, false)->no());
        $this->assertTrue($this->unitFloat('second')->accepts($seconds, false)->no());
        $this->assertTrue($this->unitInt('second')->accepts($numericStringUnion, false)->no());
        $this->assertTrue($this->unitFloat('second')->accepts($numericStringUnion, false)->no());
    }

    public function testNumericStringExtractionDoesNotDescendIntoContainersOrUnions(): void
    {
        $seconds = $this->unitNumericString('second');
        $equivalentSeconds = $this->unitNumericString('1000 * millisecond');
        $meters = $this->unitNumericString('meter');
        $array = new ArrayType($seconds, $equivalentSeconds);
        $union = new UnionType([$seconds, $equivalentSeconds]);
        $refined = new IntersectionType([new AccessoryLiteralStringType(), $seconds]);
        $conflicting = new IntersectionType([$seconds, $meters]);
        $refinedUnit = UnitNumericStringType::extractUnit($refined);

        $this->assertNull(UnitNumericStringType::extractUnit($array));
        $this->assertNull(UnitNumericStringType::extractUnit($union));
        $this->assertNull(UnitNumericStringType::extractUnit(new StringType()));
        $this->assertNull(UnitNumericStringType::extractUnit($conflicting));
        $this->assertNotNull($refinedUnit);
        $this->assertTrue($seconds->getUnitExpression()->equivalent($refinedUnit));
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

    public function testUnitFloatRelationshipMatrix(): void
    {
        $meters = $this->unitFloat('meter');
        $equivalentMeters = $this->unitFloat('100 * centimeter');
        $feet = $this->unitFloat('foot');
        $integerMeters = $this->unitInt('meter');
        $integerFeet = $this->unitInt('foot');
        $constantMeters = UnitIntegerTypeHelper::create($meters->getUnitExpression(), 3, 3);
        $rangeMeters = UnitIntegerTypeHelper::create($meters->getUnitExpression(), 1, 5);
        $rangeFeet = UnitIntegerTypeHelper::create($feet->getUnitExpression(), 1, 5);

        $this->assertTrue($meters->equals($equivalentMeters));
        $this->assertFalse($meters->equals($feet));
        $this->assertFalse($meters->equals($integerMeters));

        $this->assertTrue($meters->accepts($equivalentMeters, true)->yes());
        $this->assertTrue($meters->accepts($feet, true)->no());
        $this->assertTrue($meters->accepts($integerMeters, true)->yes());
        $this->assertTrue($meters->accepts($integerFeet, true)->no());
        $this->assertTrue($meters->accepts($constantMeters, true)->yes());
        $this->assertTrue($meters->accepts($rangeMeters, true)->yes());
        $this->assertTrue($meters->accepts($rangeFeet, true)->no());

        $this->assertTrue($meters->isSuperTypeOf($equivalentMeters)->yes());
        $this->assertTrue($meters->isSuperTypeOf($feet)->no());
        $this->assertTrue($meters->isSuperTypeOf($integerMeters)->no());
        $this->assertTrue($meters->isSuperTypeOf($constantMeters)->no());

        $bareFloatAcceptance = $meters->accepts(new FloatType(), true);
        $bareIntegerAcceptance = $meters->accepts(new IntegerType(), true);
        $unrelatedAcceptance = $meters->accepts(new StringType(), true);

        $this->assertTrue($bareFloatAcceptance->no());
        $this->assertNotSame([], $bareFloatAcceptance->reasons);
        $this->assertTrue($bareIntegerAcceptance->no());
        $this->assertNotSame([], $bareIntegerAcceptance->reasons);
        $this->assertTrue($unrelatedAcceptance->no());
        $this->assertSame([], $unrelatedAcceptance->reasons);
        $this->assertTrue($meters->isSuperTypeOf(new FloatType())->no());
        $this->assertTrue($meters->isSuperTypeOf(new IntegerType())->no());
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

    public function testDefinitionallyEquivalentNominalBrandsRemainDistinctDuringTypeCombination(): void
    {
        $canonicalFloat = $this->unitFloat('arc_degree');
        $directionalFloat = $this->unitFloat('degree_north');
        $canonicalInteger = $this->unitInt('arc_degree');
        $directionalInteger = $this->unitInt('degree_north');
        $canonicalString = $this->unitNumericString('arc_degree');
        $directionalString = $this->unitNumericString('degree_north');
        $canonicalRange = UnitIntegerTypeHelper::create($canonicalInteger->getUnitExpression(), 0, 360);
        $directionalRange = UnitIntegerTypeHelper::create($directionalInteger->getUnitExpression(), 0, 360);

        $this->assertFalse($canonicalFloat->equals($directionalFloat));
        $this->assertTrue($canonicalFloat->accepts($directionalFloat, true)->yes());
        $this->assertTrue($canonicalFloat->isSuperTypeOf($directionalFloat)->maybe());
        $this->assertFalse($canonicalInteger->equals($directionalInteger));
        $this->assertTrue($canonicalInteger->accepts($directionalInteger, true)->yes());
        $this->assertTrue($canonicalInteger->isSuperTypeOf($directionalInteger)->maybe());
        $this->assertFalse($canonicalString->equals($directionalString));
        $this->assertTrue($canonicalString->accepts($directionalString, true)->yes());
        $this->assertTrue($canonicalString->isSuperTypeOf($directionalString)->maybe());

        foreach ([
            [$canonicalFloat, $directionalFloat],
            [$canonicalInteger, $directionalInteger],
            [$canonicalString, $directionalString],
            [$canonicalRange, $directionalRange],
        ] as $types) {
            $this->assertInstanceOf(UnionType::class, TypeCombinator::union(...$types));
        }
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
        $this->assertTrue($floatMeters->isSuperTypeOf($intMeters)->no());
        $this->assertSame(
            "unit_float<'meter'>|unit_int<'meter'>",
            \PHPStan\Type\TypeCombinator::union($floatMeters, $intMeters)->describe(VerbosityLevel::precise()),
        );
    }

    public function testNumericCastsPreserveUnitsWithRepresentablePrecision(): void
    {
        $integer = $this->unitInt('meter');
        $float = $this->unitFloat('second');
        $constant = UnitIntegerTypeHelper::create($integer->getUnitExpression(), 3, 3);
        $range = UnitIntegerTypeHelper::create($integer->getUnitExpression(), -5, 10);

        $this->assertSame($integer, $integer->toInteger());
        $this->assertSame($float, $float->toFloat());
        $this->assertSame($constant, $constant->toInteger());
        $this->assertSame(
            "unit_int<'meter'>&int<-5, 10>",
            $range->toInteger()->describe(VerbosityLevel::precise()),
        );
        $this->assertSame(
            "unit_float<'meter'>",
            $integer->toFloat()->describe(VerbosityLevel::precise()),
        );
        $this->assertSame(
            "3.0&unit_float<'meter'>",
            $constant->toFloat()->describe(VerbosityLevel::precise()),
        );
        $this->assertSame(
            "unit_float<'meter'>",
            $range->toFloat()->describe(VerbosityLevel::precise()),
        );
        $this->assertSame(
            "unit_int<'second'>",
            $float->toInteger()->describe(VerbosityLevel::precise()),
        );
    }

    public function testConstantFloatRetainsValueAndUnitIdentity(): void
    {
        $meters = $this->unitFloat('meter')->getUnitExpression();
        $equivalentMeters = $this->unitFloat('100 * centimeter')->getUnitExpression();
        $constant = new UnitConstantFloatType(1.5, $meters);
        $equivalent = new UnitConstantFloatType(1.5, $equivalentMeters);
        $differentValue = new UnitConstantFloatType(2.5, $meters);
        $seconds = new UnitConstantFloatType(1.5, $this->unitFloat('second')->getUnitExpression());
        $partlyMatching = new UnionType([$equivalent, $differentValue]);
        $partlyWrongUnit = new UnionType([$equivalent, $seconds]);
        $fullyRejected = new UnionType([$differentValue, $seconds]);

        $this->assertSame("1.5&unit_float<'meter'>", $constant->describe(VerbosityLevel::precise()));
        $this->assertTrue($constant->equals($equivalent));
        $this->assertFalse($constant->equals($differentValue));
        $this->assertFalse($constant->equals($seconds));
        $this->assertTrue($constant->accepts($equivalent, true)->yes());
        $this->assertTrue($constant->accepts($differentValue, true)->no());
        $this->assertTrue($constant->accepts($seconds, true)->no());
        $this->assertTrue($constant->accepts($this->unitFloat('meter'), true)->maybe());
        $this->assertTrue($constant->accepts($partlyMatching, true)->maybe());
        $this->assertTrue($constant->accepts($partlyWrongUnit, true)->maybe());
        $this->assertTrue($constant->accepts($fullyRejected, true)->no());
        $this->assertTrue($constant->accepts(new ConstantFloatType(1.5), true)->no());
        $this->assertTrue($this->unitFloat('meter')->accepts($constant, true)->yes());
        $this->assertTrue($this->unitFloat('meter')->isSuperTypeOf($constant)->yes());
        $this->assertTrue($constant->isSuperTypeOf($equivalent)->yes());
        $this->assertTrue($constant->isSuperTypeOf($differentValue)->no());
        $this->assertTrue($constant->isSuperTypeOf($partlyMatching)->maybe());
        $this->assertTrue($constant->isSuperTypeOf($partlyWrongUnit)->maybe());
        $this->assertTrue($constant->isSuperTypeOf($fullyRejected)->no());
    }

    public function testConstantFloatPreservesBrandAcrossScalarTransforms(): void
    {
        $constant = new UnitConstantFloatType(-1.5, $this->unitFloat('meter')->getUnitExpression());

        $this->assertSame("unit_float<'meter'>", $constant
            ->generalize(\PHPStan\Type\GeneralizePrecision::moreSpecific())
            ->describe(VerbosityLevel::precise()));
        $this->assertSame([], $constant->getConstantScalarTypes());
        $this->assertSame("unit_float<'meter'>", $constant->toNumber()->describe(VerbosityLevel::precise()));
        $this->assertSame("-1&unit_int<'meter'>", $constant->toInteger()->describe(VerbosityLevel::precise()));
        $this->assertSame(
            "1.5&unit_float<'meter'>",
            $constant->toAbsoluteNumber()->describe(VerbosityLevel::precise()),
        );
        $this->assertTrue($constant->equals(UnitConstantFloatType::__set_state([
            'value' => -1.5,
            'unit' => $constant->getUnitExpression(),
        ])));
    }

    public function testFloatBrandingPreservesDirectConstantsWithoutInspectingContainers(): void
    {
        $meters = $this->unitFloat('meter')->getUnitExpression();
        $one = UnitFloatType::brand(new ConstantFloatType(1.0), $meters);
        $alternatives = UnitFloatType::brand(new UnionType([
            new ConstantFloatType(1.0),
            new ConstantFloatType(2.0),
        ]), $meters);
        $mixedUnits = new UnionType([
            new UnitConstantFloatType(1.0, $meters),
            new UnitConstantFloatType(2.0, $this->unitFloat('second')->getUnitExpression()),
        ]);

        $this->assertInstanceOf(UnitConstantFloatType::class, $one);
        $this->assertSame("1.0&unit_float<'meter'>", $one->describe(VerbosityLevel::precise()));
        $this->assertSame(
            "1.0&unit_float<'meter'>|2.0&unit_float<'meter'>",
            $alternatives->describe(VerbosityLevel::precise()),
        );
        $this->assertTrue($this->unitFloat('meter')->accepts($alternatives, true)->yes());
        $this->assertTrue($this->unitFloat('meter')->isSuperTypeOf($alternatives)->yes());
        $this->assertTrue($this->unitFloat('meter')->accepts($mixedUnits, true)->maybe());
        $this->assertTrue($this->unitFloat('meter')->isSuperTypeOf($mixedUnits)->maybe());
        $this->assertSame(
            "unit_float<'meter'>",
            UnitFloatType::brand(new FloatType(), $meters)->describe(VerbosityLevel::precise()),
        );
        $this->assertSame(
            "unit_float<'meter'>",
            UnitFloatType::brand(new UnionType([
                new ConstantFloatType(1.0),
                new IntegerType(),
            ]), $meters)->describe(VerbosityLevel::precise()),
        );
        $this->assertNull(UnitFloatType::extract(new ArrayType(new IntegerType(), $one)));
        $this->assertSame(
            "unit_float<'meter'>",
            TypeCombinator::union($this->unitFloat('meter'), $one)->describe(VerbosityLevel::precise()),
        );
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

    private function unitNumericString(string $unit): UnitNumericStringType
    {
        $parsed = (new UnitExpressionParser())->parse($unit);
        $this->assertTrue($parsed->isOk(), $parsed->errorMessage() ?? '');

        return new UnitNumericStringType($parsed->expression());
    }
}
