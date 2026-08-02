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

use jbboehr\Yumemi\Catalog\UnitSemantics;
use jbboehr\Yumemi\Exception\IncompatibleUnitException;
use jbboehr\Yumemi\Exception\OverflowException;
use jbboehr\Yumemi\Exception\UnitNotFoundException;
use jbboehr\Yumemi\Exception\UnsupportedUnitAlgebraException;
use jbboehr\Yumemi\Exception\UnsupportedSyntaxException;
use jbboehr\Yumemi\Expr\Product;
use jbboehr\Yumemi\Expr\Power;
use jbboehr\Yumemi\Parser\ParseException;
use jbboehr\Yumemi\Quantity;
use jbboehr\Yumemi\Registry\UnitRegistryBuilder;
use jbboehr\Yumemi\Units;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class UnitsTest extends TestCase
{
    public function testDefaultUnitsNormalizeDerivedUnits(): void
    {
        $units = Units::default();

        $this->assertSame('1000 * meter', $units->normalize('kilometer')->toString());
    }

    public function testDefaultUnitsCheckCompatibility(): void
    {
        $units = Units::default();

        $this->assertTrue($units->areCompatible('kilometer', 'meter'));
        $this->assertFalse($units->areCompatible('meter', 'second'));
    }

    public function testDefaultContextCanBeTemporarilyReplacedAndRestored(): void
    {
        $original = Units::default();
        $custom = new Units(
            UnitRegistryBuilder::default()
                ->define('widget = 2 * meter')
                ->build(),
        );

        $previous = Units::setDefault($custom);

        try {
            $this->assertSame($original, $previous);
            $this->assertSame($custom, Units::default());
            $this->assertSame('2', Units::default()->quantity(1, 'widget')->valueIn('meter')->toString());
        } finally {
            $replaced = Units::setDefault($previous);
        }

        $this->assertSame($custom, $replaced);
        $this->assertSame($original, Units::default());
    }

    public function testClearingDefaultLazilyCreatesFreshBuiltinContext(): void
    {
        $original = Units::default();

        try {
            $this->assertSame($original, Units::setDefault(null));
            $fresh = Units::default();
            $this->assertNotSame($original, $fresh);
            $this->assertSame('meter', $fresh->unit('meter')->toString());
        } finally {
            Units::setDefault($original);
        }
    }

    public function testDefaultUnitsExposeDimensions(): void
    {
        $units = Units::default();
        $dimension = $units->dimension('newton');

        $this->assertSame([1, 1, -2, 0, 0, 0, 0], $dimension->powers());
        $this->assertSame('length * mass / time ^ 2', $dimension->toString());
        $this->assertTrue($dimension->equals($units->dimension('kilogram * meter / second^2')));
        $this->assertTrue($units->dimension('percent')->isDimensionless());
    }

    public function testConversionResolutionRejectsExponentBeyondSupportedRange(): void
    {
        $this->expectException(OverflowException::class);

        Units::default()->conversionFactor('meter^10001', 'meter');
    }

    public function testUnitExpressionsExposeDimensionDirectly(): void
    {
        $units = Units::default();

        // Units::unit() returns an Expr that may be a Product (prefixed/derived),
        // yet dimension() now resolves on any node, not just Unit leaves.
        $this->assertInstanceOf(Product::class, $units->unit('centimeter'));
        $this->assertSame('length', $units->unit('centimeter')->dimension()->toString());
        $this->assertSame('length', $units->unit('kilometer')->dimension()->toString());

        $newton = $units->unit('newton')->dimension();
        $this->assertSame([1, 1, -2, 0, 0, 0, 0], $newton->powers());
        $this->assertTrue($newton->equals($units->dimension('newton')));

        // Parsed compound expressions resolve too, matching the Units facade.
        $this->assertSame('mass / time ^ 2', $units->parse('newton / meter')->dimension()->toString());
        $this->assertSame('length ^ 2', $units->parse('meter^2')->dimension()->toString());
        $this->assertTrue($units->parse('meter^0')->dimension()->isDimensionless());
    }

    public function testDefaultUnitsConvertValues(): void
    {
        $units = Units::default();

        $this->assertSame('1000', $units->convert(1, 'kilometer', 'meter')->toString());
        $this->assertSame('60', $units->convert(1, 'minute', 'second')->toString());
    }

    public function testDefaultUnitsUseUdunits2AliasesForImperialConversions(): void
    {
        $units = Units::default();

        $this->assertSame('1/12', $units->conversionFactor('inch', 'foot')->toString());
        $this->assertSame('124', $units->convert(1488, 'inch', 'foot')->toString());
    }

    public function testDefaultUnitsUseUdunits2LargeScaleConversions(): void
    {
        $units = Units::default();

        $this->assertSame('94607300000000000000000000', $units->conversionFactor('light_year', 'angstrom')->toString());
    }

    public function testDefaultUnitsConvertCompoundValues(): void
    {
        $units = Units::default();

        $metersPerSecond = new Product([
            $units->unit('meter'),
            new Power($units->unit('second'), -1),
        ]);
        $kilometersPerMinute = new Product([
            $units->unit('kilometer'),
            new Power($units->unit('minute'), -1),
        ]);

        $this->assertSame('3/50', $units->convert(1, $metersPerSecond, $kilometersPerMinute)->toString());
    }

    public function testDefaultUnitsParseExpressions(): void
    {
        $units = Units::default();

        $this->assertSame('1000 * meter * minute ^ -1', $units->parse('kilometer / minute')->toString());
        $this->assertSame('50/3 * meter * second ^ -1', $units->normalize(
            $units->parse('kilometer / minute'),
        )->toString());
    }

    public function testParseUnitAliasesParse(): void
    {
        $units = Units::default();

        $this->assertTrue($units->parse('kilometer / minute')->equals(
            $units->parseUnit('kilometer / minute'),
        ));
    }

    public function testParseUnitPropagatesLookupErrors(): void
    {
        $this->expectException(UnitNotFoundException::class);

        Units::default()->parseUnit('not_a_real_unit_xyz');
    }

    #[DataProvider('parsedQuantityProvider')]
    public function testParseQuantityExtractsTheCompleteExplicitConstant(
        string $input,
        string $expectedValue,
        string $expectedUnit,
    ): void {
        $quantity = Units::default()->parseQuantity($input);

        $this->assertSame($expectedValue, $quantity->valueToString());
        $this->assertSame($expectedUnit, $quantity->unitToString());
    }

    public static function parsedQuantityProvider(): iterable
    {
        yield 'integer' => ['12 foot', '12', 'foot'];
        yield 'negative decimal and scientific notation' => [
            '-1.25e2 kilometer / (5 second)',
            '-25',
            'kilometer / second',
        ];
        yield 'constants throughout expression' => ['2 meter / (4 second)', '1/2', 'meter / second'];
        yield 'powered constant' => ['(2 meter)^2', '4', 'meter ^ 2'];
        yield 'implicit one' => ['meter / second', '1', 'meter / second'];
        yield 'dimensionless constant' => ['1000', '1000', '1'];
        yield 'zero retains unit' => ['0 meter', '0', 'meter'];
    }

    public function testParseQuantityKeepsCatalogScaleInTheUnit(): void
    {
        $units = Units::default();
        $feet = $units->parseQuantity('12 foot');
        $centimeters = $units->parseQuantity('100 centimeter');
        $percent = $units->parseQuantity('2 percent');

        $this->assertSame('12', $feet->valueToString());
        $this->assertSame('foot', $feet->unitToString());
        $this->assertSame('144', $feet->valueIn('inch')->toString());
        $this->assertSame('100', $centimeters->valueToString());
        $this->assertSame('centimeter', $centimeters->unitToString());
        $this->assertSame('1', $centimeters->valueIn('meter')->toString());
        $this->assertSame('2', $percent->valueToString());
        $this->assertSame('percent', $percent->unitToString());
        $this->assertSame('1/50', $percent->valueIn('1')->toString());
    }

    public function testParsedCompoundQuantityRetainsItsResolvedUnit(): void
    {
        $quantity = Units::default()->parseQuantity('2 kilometer / (4 minute)');

        $this->assertSame('1/2', $quantity->valueToString());
        $this->assertSame('kilometer / minute', $quantity->unitToString());
        $this->assertSame('25/3', $quantity->valueIn('meter / second')->toString());
        $this->assertSame('length / time', $quantity->dimension()->toString());
    }

    public function testParseQuantityRejectsUnknownUnits(): void
    {
        $this->expectException(UnitNotFoundException::class);

        self::parseQuantity(Units::default(), '2 not_a_real_unit_xyz');
    }

    public function testParseQuantityRejectsMalformedSyntax(): void
    {
        $this->expectException(ParseException::class);

        self::parseQuantity(Units::default(), 'meter * / second');
    }

    public function testParseQuantityRejectsUnsupportedSyntax(): void
    {
        $this->expectException(UnsupportedSyntaxException::class);

        self::parseQuantity(Units::default(), 'meter + second');
    }

    public function testParseQuantityRejectsUnsupportedCatalogUnits(): void
    {
        $this->expectException(UnsupportedUnitAlgebraException::class);

        self::parseQuantity(Units::default(), '2 degree_Celsius');
    }

    public function testParseReducesZeroPowersToDimensionless(): void
    {
        $units = Units::default();

        $this->assertSame('1', $units->parse('meter^0')->toString());
        $this->assertTrue($units->dimension('meter^0')->isDimensionless());
    }

    public function testDefaultUnitsUseParsedExpressionsForConversion(): void
    {
        $units = Units::default();

        $this->assertSame('3/50', $units->convert(
            1,
            $units->parse('meter / second'),
            $units->parse('kilometer / minute'),
        )->toString());
    }

    public function testDefaultUnitsAcceptStringExpressions(): void
    {
        $units = Units::default();

        $this->assertSame('1000 * meter', $units->normalize('kilometer')->toString());
        $this->assertTrue($units->areCompatible('meter / second', 'kilometer / minute'));
        $this->assertSame('3/50', $units->conversionFactor('meter / second', 'kilometer / minute')->toString());
        $this->assertSame('3/50', $units->convert(1, 'meter / second', 'kilometer / minute')->toString());
        $this->assertSame('5 * meter', $units->quantity(5, 'meter')->toString());
    }

    public function testStringExpressionsStillRejectIncompatibleUnits(): void
    {
        $units = Units::default();

        $this->expectException(IncompatibleUnitException::class);
        $units->conversionFactor('meter', 'second');
    }

    public function testStringExpressionsStillRejectUnknownUnits(): void
    {
        $units = Units::default();

        $this->expectException(UnitNotFoundException::class);
        $units->normalize('league');
    }

    public function testDefaultUnitsRejectAffineUdunits2DefinitionsForNow(): void
    {
        $units = Units::default();

        try {
            $units->normalize('degree_Celsius');
            self::fail('Expected UnsupportedUnitAlgebraException');
        } catch (UnsupportedUnitAlgebraException $exception) {
            $this->assertSame(UnitSemantics::Affine, $exception->semantics);
            $this->assertSame('degree_Celsius', $exception->unitName);
            $this->assertStringContainsString('@', $exception->definition);
            $this->assertStringContainsString('not supported by multiplicative unit algebra', $exception->getMessage());
        }
    }

    private static function parseQuantity(Units $units, string $input): Quantity
    {
        return $units->parseQuantity($input);
    }
}
