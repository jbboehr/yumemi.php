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

use jbboehr\Yumemi\Dimension;
use jbboehr\Yumemi\Exception\IncompatibleQuantityContextException;
use jbboehr\Yumemi\Exception\IncompatibleUnitException;
use jbboehr\Yumemi\Exception\UnsupportedUnitAlgebraException;
use jbboehr\Yumemi\Exception\UnsupportedUnitCompactionException;
use jbboehr\Yumemi\Number\Rational;
use jbboehr\Yumemi\Registry\UnitRegistryBuilder;
use jbboehr\Yumemi\Units;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class QuantityCompactionTest extends TestCase
{
    /**
     * @param int|Rational $value
     */
    #[DataProvider('engineeringBoundaryProvider')]
    public function testSelectsEngineeringPrefixesAtExactBoundaries(
        int|Rational $value,
        string $expectedValue,
        string $expectedUnit,
    ): void {
        $compact = Units::default()->quantity($value, 'meter')->toCompact('meter');

        self::assertSame($expectedValue, $compact->valueToString());
        self::assertSame($expectedUnit, $compact->unitToString());
    }

    /**
     * @return iterable<string, array{int|Rational, string, string}>
     */
    public static function engineeringBoundaryProvider(): iterable
    {
        yield 'below upper boundary' => [999, '999', 'meter'];
        yield 'at upper boundary' => [1000, '1', 'kilometer'];
        yield 'at lower boundary' => [new Rational(1, 1000), '1', 'millimeter'];
        yield 'non-engineering prefix is skipped' => [new Rational(1, 100), '10', 'millimeter'];
        yield 'negative value' => [-1250, '-5/4', 'kilometer'];
        yield 'zero uses the selected base' => [0, '0', 'meter'];
    }

    public function testSaturatesAtTheAvailablePrefixLimits(): void
    {
        $units = Units::default();

        $large = $units->quantity(new Rational(gmp_pow(10, 27)), 'meter')->toCompact('meter');
        $small = $units->quantity(new Rational(1, gmp_pow(10, 27)), 'meter')->toCompact('meter');

        self::assertSame('1000', $large->valueToString());
        self::assertSame('yottameter', $large->unitToString());
        self::assertSame('1/1000', $small->valueToString());
        self::assertSame('yoctometer', $small->unitToString());
    }

    public function testAcceptsAnExactlyMatchingCatalogCollision(): void
    {
        $units = Units::default();

        $compact = $units->quantity(1, 'kilogram')->toCompact('gram');

        self::assertSame('1', $compact->valueToString());
        self::assertSame('kilogram', $compact->unitToString());
    }

    public function testCompactsANamedDerivedUnit(): void
    {
        $compact = Units::default()->quantity(1_000_000, 'watt')->toCompact('watt');

        self::assertSame('1', $compact->valueToString());
        self::assertSame('megawatt', $compact->unitToString());
    }

    public function testCompactsAnApplicationUnitUsingPrefixesFromItsRegistry(): void
    {
        $widget = self::runtimeUnit('widget');
        $units = new Units(UnitRegistryBuilder::default()->define($widget . ' = meter')->build());

        $compact = $units->quantity(1000, $widget)->toCompact($widget);

        self::assertSame('1', $compact->valueToString());
        self::assertSame('kilowidget', $compact->unitToString());
    }

    public function testUsesOnlyTheUnprefixedCandidateWhenTheRegistryHasNoPrefixes(): void
    {
        $credit = self::runtimeUnit('credit');
        $units = new Units(UnitRegistryBuilder::empty()->baseUnit($credit, Dimension::CURRENCY)->build());

        $compact = $units->quantity(1000, $credit)->toCompact($credit);

        self::assertSame('1000', $compact->valueToString());
        self::assertSame('credit', $compact->unitToString());
    }

    public function testRejectsAnExactNameCollisionWithTheWrongScale(): void
    {
        $widget = self::runtimeUnit('widget');
        $kilowidget = self::runtimeUnit('kilowidget');
        $units = new Units(UnitRegistryBuilder::default()
            ->define($widget . ' = meter')
            ->define($kilowidget . ' = 2 * ' . $widget)
            ->build());

        $compact = $units->quantity(1000, $widget)->toCompact($widget);

        self::assertSame('1000', $compact->valueToString());
        self::assertSame('widget', $compact->unitToString());
    }

    public function testAcceptsAUnitExpressionObjectAsTheFamilyRoot(): void
    {
        $units = Units::default();

        $compact = $units->quantity(1000, 'meter')->toCompact($units->unit('meter'));

        self::assertSame('1', $compact->valueToString());
        self::assertSame('kilometer', $compact->unitToString());
    }

    /**
     * @param string $baseUnit
     */
    #[DataProvider('unsupportedBaseProvider')]
    public function testRejectsABaseThatIsNotOneNamedUnit(string $baseUnit): void
    {
        $this->expectException(UnsupportedUnitCompactionException::class);
        $this->expectExceptionMessage('requires one named unit');

        Units::default()->quantity(1, 'meter')->toCompact($baseUnit);
    }

    /** @return iterable<string, array{string}> */
    public static function unsupportedBaseProvider(): iterable
    {
        yield 'compound' => ['meter / second'];
        yield 'power' => ['meter ^ 2'];
        yield 'numeric multiplier' => ['1000 * meter'];
        yield 'dimensionless constant' => ['1'];
    }

    public function testRetainsExistingAffineSemanticFailure(): void
    {
        $this->expectException(UnsupportedUnitAlgebraException::class);

        Units::default()->quantity(1, 'meter')->toCompact(self::runtimeUnit('celsius'));
    }

    public function testRejectsAnIncompatibleUnitFamily(): void
    {
        $this->expectException(IncompatibleUnitException::class);

        Units::default()->quantity(1, 'meter')->toCompact(self::runtimeUnit('second'));
    }

    public function testInternalSelectorRejectsAQuantityFromAnotherContext(): void
    {
        $left = new Units(UnitRegistryBuilder::default()->build());
        $right = new Units(UnitRegistryBuilder::default()->build());

        $this->expectException(IncompatibleQuantityContextException::class);

        $left->compactQuantity($right->quantity(1, 'meter'), 'meter');
    }

    private static function runtimeUnit(string $unit): string
    {
        return $unit;
    }
}
