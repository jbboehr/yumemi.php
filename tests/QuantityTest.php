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
use jbboehr\Yumemi\Number\Rational;
use jbboehr\Yumemi\Quantity;
use jbboehr\Yumemi\Registry\Udunits2UnitRegistry;
use jbboehr\Yumemi\Units;
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
        $this->assertSame('6 * meter * second', $quantity->expr()->toString());
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

    public function testNegatesQuantityMagnitude(): void
    {
        $units = Units::default();

        $quantity = $units->quantity(5, 'meter')->neg();

        $this->assertSame('-5', $quantity->valueToString());
        $this->assertSame('meter', $quantity->unitToString());
        $this->assertSame('5', $quantity->neg()->valueToString());
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
        $this->assertSame('3/2 * meter * second ^ -1', $quantity->expr()->toString());
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
        $this->assertSame('100 * centimeter', $quantity->expr()->toString());
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
