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

namespace jbboehr\Yumemi\Tests;

use jbboehr\Yumemi\Exception\IncompatibleQuantityContextException;
use jbboehr\Yumemi\Exception\IncompatibleUnitException;
use jbboehr\Yumemi\Exception\NonExactRootException;
use jbboehr\Yumemi\Number\DecimalNotation;
use jbboehr\Yumemi\Number\FloatRangePolicy;
use jbboehr\Yumemi\Number\Rational;
use jbboehr\Yumemi\Quantity;
use jbboehr\Yumemi\Registry\Udunits2UnitRegistry;
use jbboehr\Yumemi\Units;
use PHPUnit\Framework\Attributes\DataProvider;
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

    public function testReturnsExactConvertedIntegerValue(): void
    {
        $units = Units::default();

        $quantity = $units->quantity(1488, 'inch');

        $this->assertSame(124, $quantity->exactIntValueIn('foot'));
        $this->assertSame(124, $quantity->intValueIn('foot'));
    }

    public function testReturnsConvertedIntegerValueByTruncatingTowardZero(): void
    {
        $units = Units::default();

        $this->assertSame(1, $units->quantity(5, 'foot')->intValueIn('meter'));
        $this->assertSame(-1, $units->quantity(-5, 'foot')->intValueIn('meter'));
    }

    public function testExactConvertedIntegerValueRejectsFraction(): void
    {
        $units = Units::default();

        $this->expectException(\UnexpectedValueException::class);

        $units->quantity(5, 'foot')->exactIntValueIn('meter');
    }

    public function testComparesCompatibleQuantitiesAfterExactConversion(): void
    {
        $units = Units::default();
        $meter = $units->quantity(1, 'meter');
        $hundredCentimeters = $units->quantity(100, 'centimeter');
        $threeFeet = $units->quantity(3, 'foot');
        $fourFeet = $units->quantity(4, 'foot');

        $this->assertSame(0, $meter->compareTo($hundredCentimeters));
        $this->assertTrue($meter->equals($hundredCentimeters));
        $this->assertFalse($meter->lessThan($hundredCentimeters));
        $this->assertTrue($meter->lessThanOrEqualTo($hundredCentimeters));
        $this->assertFalse($meter->greaterThan($hundredCentimeters));
        $this->assertTrue($meter->greaterThanOrEqualTo($hundredCentimeters));

        $this->assertSame(1, $meter->compareTo($threeFeet));
        $this->assertFalse($meter->equals($threeFeet));
        $this->assertFalse($meter->lessThan($threeFeet));
        $this->assertFalse($meter->lessThanOrEqualTo($threeFeet));
        $this->assertTrue($meter->greaterThan($threeFeet));
        $this->assertTrue($meter->greaterThanOrEqualTo($threeFeet));

        $this->assertSame(-1, $meter->compareTo($fourFeet));
        $this->assertFalse($meter->equals($fourFeet));
        $this->assertTrue($meter->lessThan($fourFeet));
        $this->assertTrue($meter->lessThanOrEqualTo($fourFeet));
        $this->assertFalse($meter->greaterThan($fourFeet));
        $this->assertFalse($meter->greaterThanOrEqualTo($fourFeet));
    }

    public function testComparisonRetainsExactnessBeyondFloatPrecision(): void
    {
        $units = Units::default();
        $scale = gmp_pow(10, 30);
        $slightlyMoreThanOne = new Rational(gmp_add($scale, 1), $scale);

        $this->assertTrue(
            $units->quantity($slightlyMoreThanOne, 'meter')->greaterThan($units->quantity(100, 'centimeter')),
        );
    }

    public function testComparesDimensionlessAndDefinitionallyEquivalentQuantities(): void
    {
        $units = Units::default();

        $this->assertTrue(
            $units->quantity(1, 'kilometer')->equals($units->quantity(1, '1000 * meter')),
        );
        $this->assertTrue(
            $units->quantity(1, 'percent')->equals($units->quantity(new Rational(1, 100), '1')),
        );
    }

    public function testComparisonDoesNotMutateOperands(): void
    {
        $units = Units::default();
        $left = $units->quantity(1, 'meter');
        $right = $units->quantity(100, 'centimeter');

        $left->compareTo($right);

        $this->assertSame('1', $left->valueToString());
        $this->assertSame('meter', $left->unitToString());
        $this->assertSame('100', $right->valueToString());
        $this->assertSame('centimeter', $right->unitToString());
    }

    /**
     * @param \Closure(Quantity, Quantity): (int|bool) $comparison
     */
    #[DataProvider('quantityComparisonProvider')]
    public function testComparisonsRejectIncompatibleDimensions(\Closure $comparison): void
    {
        $units = Units::default();

        $this->expectException(IncompatibleUnitException::class);

        $comparison(
            $units->quantity(1, 'meter'),
            self::unbrandedQuantity($units, 1, 'second'),
        );
    }

    /**
     * @return iterable<string, array{\Closure(Quantity, Quantity): (int|bool)}>
     */
    public static function quantityComparisonProvider(): iterable
    {
        yield 'compareTo' => [static fn (Quantity $left, Quantity $right): int => $left->compareTo($right)];
        yield 'equals' => [static fn (Quantity $left, Quantity $right): bool => $left->equals($right)];
        yield 'lessThan' => [static fn (Quantity $left, Quantity $right): bool => $left->lessThan($right)];
        yield 'lessThanOrEqualTo' => [
            static fn (Quantity $left, Quantity $right): bool => $left->lessThanOrEqualTo($right),
        ];
        yield 'greaterThan' => [static fn (Quantity $left, Quantity $right): bool => $left->greaterThan($right)];
        yield 'greaterThanOrEqualTo' => [
            static fn (Quantity $left, Quantity $right): bool => $left->greaterThanOrEqualTo($right),
        ];
    }

    public function testComparisonRejectsDifferentUnitsContexts(): void
    {
        $left = new Units(new Udunits2UnitRegistry());
        $right = new Units(new Udunits2UnitRegistry());

        $this->expectException(IncompatibleQuantityContextException::class);

        $left->quantity(1, 'meter')->compareTo($right->quantity(1, 'meter'));
    }

    public function testAddsCompatibleQuantities(): void
    {
        $units = Units::default();

        $quantity = $units->quantity(1, 'meter')->add($units->quantity(2, 'meter'));

        $this->assertSame('3', $quantity->valueToString());
        $this->assertSame('meter', $quantity->unitToString());
        $this->assertSame('300', $quantity->valueIn('centimeter')->toString());
    }

    public function testAddsQuantitiesWithStructurallyEqualUnitsRegardlessOfOperandOrder(): void
    {
        $units = Units::default();

        $left = $units->quantity(1, 'meter * second');
        $right = $units->quantity(2, 'second * meter');

        $quantity = $left->add($right);

        $this->assertSame('3', $quantity->valueToString());
        $this->assertTrue($left->unit()->equals($right->unit()));
    }

    public function testAddsExactScaleAliasUnitsWithSameUnitMethod(): void
    {
        $units = Units::default();

        // kilometer and 1000 * meter are definitionally equivalent (exact-scale
        // aliases), so no value conversion is needed and raw magnitudes add.
        $quantity = $units->quantity(1, 'kilometer')->addWithSameUnit(
            $units->quantity(1, '1000 * meter'),
        );

        $this->assertSame('2', $quantity->valueToString());
        $this->assertSame('kilometer', $quantity->unitToString());
        $this->assertSame('2000', $quantity->valueIn('meter')->toString());
    }

    public function testSubtractsExactScaleAliasUnitsWithSameUnitMethod(): void
    {
        $units = Units::default();

        $quantity = $units->quantity(3, 'kilometer')->subWithSameUnit(
            $units->quantity(1, '1000 * meter'),
        );

        $this->assertSame('2', $quantity->valueToString());
        $this->assertSame('kilometer', $quantity->unitToString());
        $this->assertSame('2000', $quantity->valueIn('meter')->toString());
    }

    public function testAdditionConvertsDifferentScaleUnitsToTheLeftUnit(): void
    {
        $units = Units::default();

        $quantity = $units->quantity(1, 'kilometer')->add($units->quantity(1000, 'meter'));

        $this->assertSame('2', $quantity->valueToString());
        $this->assertSame('kilometer', $quantity->unitToString());
        $this->assertSame('2000', $quantity->valueIn('meter')->toString());
    }

    public function testAdditionConvertsCompatibleSymbolicUnitsToTheLeftUnit(): void
    {
        $units = Units::default();

        $quantity = $units->quantity(1, 'meter')->add($units->quantity(1, 'foot'));

        $this->assertSame('1631/1250', $quantity->valueToString());
        $this->assertSame('meter', $quantity->unitToString());
    }

    public function testAdditionConversionPreservesEitherLeftUnit(): void
    {
        $units = Units::default();

        $quantity = $units->quantity(1, 'foot')->add($units->quantity(1, 'meter'));

        $this->assertSame('1631/381', $quantity->valueToString());
        $this->assertSame('foot', $quantity->unitToString());
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

    public function testAdditionConvertsCompatibleUnits(): void
    {
        $units = Units::default();

        $quantity = $units->quantity(1, 'meter')->add($units->quantity(100, 'centimeter'));

        $this->assertSame('2', $quantity->valueToString());
        $this->assertSame('meter', $quantity->unitToString());
    }

    public function testSubtractionConvertsCompatibleUnits(): void
    {
        $units = Units::default();

        $quantity = $units->quantity(1, 'meter')->sub($units->quantity(1, 'foot'));

        $this->assertSame('869/1250', $quantity->valueToString());
        $this->assertSame('meter', $quantity->unitToString());
    }

    public function testSameUnitAdditionRejectsUnitsThatRequireConversion(): void
    {
        $units = Units::default();

        $this->expectException(IncompatibleUnitException::class);

        $units->quantity(1, 'meter')->addWithSameUnit(self::unbrandedQuantity($units, 1, 'foot'));
    }

    public function testSameUnitSubtractionRejectsUnitsThatRequireConversion(): void
    {
        $units = Units::default();

        $this->expectException(IncompatibleUnitException::class);

        $units->quantity(1, 'meter')->subWithSameUnit(self::unbrandedQuantity($units, 1, 'foot'));
    }

    public function testSameUnitAdditionRejectsAQuantityFromAnotherContext(): void
    {
        $left = new Units(new Udunits2UnitRegistry());
        $right = new Units(new Udunits2UnitRegistry());

        $this->expectException(IncompatibleQuantityContextException::class);

        $left->quantity(1, 'meter')->addWithSameUnit($right->quantity(1, 'meter'));
    }

    public function testSameUnitSubtractionRejectsAQuantityFromAnotherContext(): void
    {
        $left = new Units(new Udunits2UnitRegistry());
        $right = new Units(new Udunits2UnitRegistry());

        $this->expectException(IncompatibleQuantityContextException::class);

        $left->quantity(1, 'meter')->subWithSameUnit($right->quantity(1, 'meter'));
    }

    public function testRejectsIncompatibleAddition(): void
    {
        $units = Units::default();

        $this->expectException(IncompatibleUnitException::class);

        $units->quantity(1, 'meter')->add(self::unbrandedQuantity($units, 1, 'second'));
    }

    public function testRejectsIncompatibleSubtraction(): void
    {
        $units = Units::default();

        $this->expectException(IncompatibleUnitException::class);

        $units->quantity(1, 'meter')->sub(self::unbrandedQuantity($units, 1, 'second'));
    }

    public function testDefaultUnitsInstanceIsShared(): void
    {
        $this->assertSame(Units::default(), Units::default());

        $total = Units::default()
            ->quantity(1, 'meter')
            ->add(Units::default()->quantity(2, 'meter'));

        $this->assertSame('3', $total->valueToString());
    }

    public function testRejectsAdditionAcrossDifferentUnitsContexts(): void
    {
        $left = new Units(new Udunits2UnitRegistry());
        $right = new Units(new Udunits2UnitRegistry());

        try {
            $left->quantity(1, 'meter')->add($right->quantity(1, 'meter'));
            self::fail('Expected IncompatibleQuantityContextException');
        } catch (IncompatibleQuantityContextException $exception) {
            $this->assertNotNull($exception->leftContextId);
            $this->assertNotNull($exception->rightContextId);
            $this->assertNotSame($exception->leftContextId, $exception->rightContextId);
            $this->assertStringContainsString('#' . $exception->leftContextId, $exception->getMessage());
        }
    }

    public function testMultipliesByQuantity(): void
    {
        $units = Units::default();

        $quantity = $units->quantity(2, 'meter')->mul($units->quantity(3, 'second'));

        $this->assertSame('6', $quantity->valueToString());
        $this->assertSame('meter * second', $quantity->unitToString());
        $this->assertSame('6 * meter * second', $quantity->toExpr()->toString());
    }

    public function testRaisesQuantityToIntegerPower(): void
    {
        $units = Units::default();

        $area = $units->quantity(3, 'meter')->pow(2);

        $this->assertSame('9', $area->valueToString());
        $this->assertSame('meter ^ 2', $area->unitToString());
        $this->assertSame('9', $area->valueIn('meter^2')->toString());
    }

    public function testRaisesQuantityToNegativePowerInvertsUnit(): void
    {
        $units = Units::default();

        $rate = $units->quantity(2, 'second')->pow(-1);

        $this->assertSame('1/2', $rate->valueToString());
        $this->assertSame('1 / second', $rate->unitToString());
    }

    public function testZeroPowerProducesDimensionlessOneIncludingForZeroMagnitude(): void
    {
        $result = Units::default()->quantity(0, 'meter')->pow(0);

        $this->assertSame('1', $result->valueToString());
        $this->assertSame('1', $result->unitToString());
        $this->assertTrue($result->dimension()->isDimensionless());
    }

    public function testTakesExactQuantityRootWhilePreservingSymbolicUnit(): void
    {
        $quantity = Units::default()->quantity(new Rational(4, 9), 'centimeter^2 / second^4');
        $root = $quantity->root(2);

        $this->assertSame('2/3', $root->valueToString());
        $this->assertSame('centimeter / second ^ 2', $root->unitToString());
        $this->assertTrue($root->pow(2)->value()->equals($quantity->value()));
        $this->assertSame($quantity->unitToString(), $root->pow(2)->unitToString());
    }

    public function testQuantityRootPreservesExactConstantInUnit(): void
    {
        $root = Units::default()->quantity(9, '4 * meter^2')->root(2);

        $this->assertSame('3', $root->valueToString());
        $this->assertSame('2 * meter', $root->unitToString());
        $this->assertSame('6', $root->valueIn('meter')->toString());
    }

    public function testSimplifyMakesDefinitionallyExactUnitRootExplicit(): void
    {
        $quantity = Units::default()->quantity(1, $this->definitionallyExactSymbolicSquare());

        $this->expectException(NonExactRootException::class);
        $quantity->root(2);
    }

    private function definitionallyExactSymbolicSquare(): string
    {
        return 'kilometer * millimeter';
    }

    public function testTakesDefinitionallyExactUnitRootAfterExplicitSimplification(): void
    {
        $root = Units::default()
            ->quantity(1, 'kilometer * millimeter')
            ->simplify()
            ->root(2);

        $this->assertSame('1', $root->valueToString());
        $this->assertSame('meter', $root->unitToString());
    }

    public function testTakesDefinitionallyExactUnitRootAfterExplicitNormalization(): void
    {
        $root = Units::default()
            ->quantity(1, 'hectare')
            ->normalize()
            ->root(2);

        $this->assertSame('1', $root->valueToString());
        $this->assertSame('100 * meter', $root->unitToString());
        $this->assertSame('100', $root->valueIn('meter')->toString());
    }

    public function testRejectsQuantityWithNonExactMagnitudeRoot(): void
    {
        $this->expectException(NonExactRootException::class);

        Units::default()->quantity(2, 'meter^2')->root(2);
    }

    public function testTakesNegativeOddQuantityRoot(): void
    {
        $root = Units::default()->quantity(-8, 'meter^3')->root(3);

        $this->assertSame('-2', $root->valueToString());
        $this->assertSame('meter', $root->unitToString());
    }

    public function testNegatesQuantityMagnitude(): void
    {
        $units = Units::default();

        $quantity = $units->quantity(5, 'meter')->neg();

        $this->assertSame('-5', $quantity->valueToString());
        $this->assertSame('meter', $quantity->unitToString());
        $this->assertSame('5', $quantity->neg()->valueToString());
    }

    public function testReturnsAbsoluteQuantityWithoutChangingItsUnit(): void
    {
        $quantity = Units::default()->quantity(new Rational(-3, 2), 'meter')->abs();

        $this->assertSame('3/2', $quantity->valueToString());
        $this->assertSame('meter', $quantity->unitToString());
        $this->assertSame('150', $quantity->valueIn('centimeter')->toString());
    }

    public function testRecognizesExactZeroIndependentlyOfUnit(): void
    {
        $units = Units::default();

        $this->assertTrue($units->quantity(0, 'meter')->isZero());
        $this->assertTrue($units->quantity(new Rational(0, 7), 'second')->isZero());
        $this->assertFalse($units->quantity(new Rational(1, gmp_pow(10, 100)), 'meter')->isZero());
        $this->assertFalse($units->quantity(-1, 'meter')->isZero());
    }

    public function testChecksQuantityCompatibilityWithoutConverting(): void
    {
        $units = Units::default();
        $meters = $units->quantity(1, 'meter');

        $this->assertTrue($meters->isCompatibleWith($units->quantity(1, 'foot')));
        $this->assertTrue($meters->isCompatibleWith($units->quantity(1, 'kilometer')));
        $this->assertFalse($meters->isCompatibleWith($units->quantity(1, 'second')));
    }

    public function testQuantitiesFromDifferentContextsAreNotCompatible(): void
    {
        $left = new Units(new Udunits2UnitRegistry());
        $right = new Units(new Udunits2UnitRegistry());

        $this->assertFalse(
            $left->quantity(1, 'meter')->isCompatibleWith($right->quantity(1, 'meter')),
        );
    }

    public function testRejectsMultiplicationAcrossDifferentUnitsContexts(): void
    {
        $this->expectException(IncompatibleQuantityContextException::class);

        $left = new Units(new Udunits2UnitRegistry());
        $right = new Units(new Udunits2UnitRegistry());

        $left->quantity(1, 'meter')->mul($right->quantity(1, 'meter'));
    }

    public function testDividesByQuantity(): void
    {
        $units = Units::default();

        $quantity = $units->quantity(3, 'meter')->div($units->quantity(2, 'second'));

        $this->assertSame('3/2', $quantity->valueToString());
        $this->assertSame('meter / second', $quantity->unitToString());
        $this->assertSame('3/2 * meter * second ^ -1', $quantity->toExpr()->toString());
    }

    public function testRejectsDivisionAcrossDifferentUnitsContexts(): void
    {
        $this->expectException(IncompatibleQuantityContextException::class);

        $left = new Units(new Udunits2UnitRegistry());
        $right = new Units(new Udunits2UnitRegistry());

        $left->quantity(1, 'meter')->div($right->quantity(1, 'meter'));
    }

    public function testRejectsSubtractionAcrossDifferentUnitsContexts(): void
    {
        $this->expectException(IncompatibleQuantityContextException::class);

        $left = new Units(new Udunits2UnitRegistry());
        $right = new Units(new Udunits2UnitRegistry());

        $left->quantity(1, 'meter')->sub($right->quantity(1, 'meter'));
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

    public function testExposesDimension(): void
    {
        $units = Units::default();

        $quantity = $units->quantity(2, 'centimeter / second');

        $this->assertSame([1, 0, -1, 0, 0, 0, 0], $quantity->dimension()->powers());
        $this->assertTrue($quantity->dimension()->equals($units->dimension('meter / second')));
    }

    public function testExpressionUsesSymbolicUnitMath(): void
    {
        $units = Units::default();

        $quantity = $units->quantity(100, 'centimeter');

        $this->assertSame('100 * centimeter', $quantity->toString());
        $this->assertSame('100 * centimeter', $quantity->toExpr()->toString());
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

    public function testReturnsRoundedDecimalValueInCompatibleUnit(): void
    {
        $quantity = Units::default()->quantity(1, 'meter');

        $this->assertSame('3.281', $quantity->decimalValueIn('foot', 3, \RoundingMode::HalfEven));
    }

    public function testReturnsSignificantDecimalValueAfterCompatibleConversion(): void
    {
        $quantity = Units::default()->quantity(1, 'meter');

        $this->assertSame(
            '3.281',
            $quantity->significantDecimalValueIn('foot', 4, \RoundingMode::HalfEven),
        );
        $this->assertSame(
            '3.281e+0',
            $quantity->significantDecimalValueIn(
                'foot',
                4,
                \RoundingMode::HalfEven,
                DecimalNotation::Scientific,
            ),
        );
    }

    public function testReturnsExactDecimalValueInCompatibleUnit(): void
    {
        $quantity = Units::default()->quantity(1, 'foot');

        $this->assertSame('0.3048', $quantity->exactDecimalValueIn('meter'));
    }

    public function testExactDecimalValueRejectsNonTerminatingConversion(): void
    {
        $quantity = Units::default()->quantity(1, 'meter');

        $this->expectException(\UnexpectedValueException::class);

        $quantity->exactDecimalValueIn('foot');
    }

    public function testReturnsFloatValueInCompatibleUnit(): void
    {
        $quantity = Units::default()->quantity(1, 'foot');

        $this->assertSame(0.3048, $quantity->floatValueIn('meter'));
    }

    #[DataProvider('nativeValueExtractionProvider')]
    public function testNativeValueExtractionRejectsIncompatibleUnit(\Closure $extract): void
    {
        $quantity = Units::default()->quantity(1, 'meter');

        $this->expectException(IncompatibleUnitException::class);

        $extract($quantity);
    }

    /**
     * @return iterable<string, array{\Closure(Quantity): mixed}>
     */
    public static function nativeValueExtractionProvider(): iterable
    {
        yield 'decimal' => [
            static fn (Quantity $quantity): string => $quantity->decimalValueIn(
                'second',
                2,
                \RoundingMode::HalfEven,
            ),
        ];
        yield 'significant decimal' => [
            static fn (Quantity $quantity): string => $quantity->significantDecimalValueIn(
                'second',
                3,
                \RoundingMode::HalfEven,
            ),
        ];
        yield 'exact decimal' => [
            static fn (Quantity $quantity): string => $quantity->exactDecimalValueIn('second'),
        ];
        yield 'float' => [static fn (Quantity $quantity): float => $quantity->floatValueIn('second')];
    }

    public function testFloatValueRejectsOverflowAfterConversion(): void
    {
        $quantity = Units::default()->quantity(new Rational(gmp_pow(2, 1024)), 'meter');

        $this->expectException(\OverflowException::class);

        $quantity->floatValueIn('meter');
    }

    public function testFloatValueRejectsUnderflowAfterConversion(): void
    {
        $quantity = Units::default()->quantity(new Rational(1, gmp_pow(2, 1075)), 'meter');

        $this->expectException(\UnderflowException::class);

        $quantity->floatValueIn('meter');
    }

    public function testFloatValueCanReturnInfinityAfterConversion(): void
    {
        $quantity = Units::default()->quantity(new Rational(gmp_pow(2, 1024)), 'meter');

        $this->assertSame(INF, $quantity->floatValueIn('meter', FloatRangePolicy::Ieee754));
    }

    public function testAccessorsExposeStoredValueAndUnit(): void
    {
        $units = Units::default();

        $quantity = $units->quantity(2, 'centimeter / second')->normalize();

        $this->assertSame('2', $quantity->value()->toString());
        $this->assertSame('1/100 * meter * second ^ -1', $quantity->unit()->toString());
    }

    public function testConstructorRequiresUnitsContext(): void
    {
        $constructor = new \ReflectionMethod(Quantity::class, '__construct');
        $parameters = $constructor->getParameters();
        $parameter = $parameters[2] ?? throw new \LogicException('Quantity constructor context parameter is missing.');
        $type = $parameter->getType();

        $this->assertFalse($parameter->isOptional());
        $this->assertInstanceOf(\ReflectionNamedType::class, $type);
        $this->assertSame(Units::class, $type->getName());
    }

    /**
     * Keep intentionally invalid runtime calls unbranded so the repository-level PHPStan run does
     * not report the diagnostics covered separately by InvalidQuantityArithmeticRuleTest.
     */
    private static function unbrandedQuantity(Units $units, int $value, string $unit): Quantity
    {
        return $units->quantity($value, $unit);
    }
}
