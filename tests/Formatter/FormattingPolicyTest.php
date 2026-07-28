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

namespace jbboehr\Yumemi\Tests\Formatter;

use jbboehr\Yumemi\Formatter\DimensionlessStyle;
use jbboehr\Yumemi\Formatter\FormatOptions;
use jbboehr\Yumemi\Formatter\Typography;
use jbboehr\Yumemi\Formatter\UnitNameStyle;
use jbboehr\Yumemi\Registry\UnitRegistryBuilder;
use jbboehr\Yumemi\Units;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FormattingPolicyTest extends TestCase
{
    public function testDefaultPolicyPreservesExistingOutputAndFormatterIsCached(): void
    {
        $units = Units::default();
        $quantity = $units->quantity(2, 'kilometers / second^2');

        $this->assertSame($units->formatter(), $units->formatter());
        $this->assertSame('kilometers / second ^ 2', $units->format('kilometers / second^2'));
        $this->assertSame('2 * kilometers / second ^ 2', $quantity->format());
        $this->assertSame($quantity->toString(), $quantity->format());
        $this->assertSame($quantity->unitToString(), $quantity->formatUnit());
    }

    #[DataProvider('unitNameStyleProvider')]
    public function testFormatsAliasesAndDynamicPrefixes(
        UnitNameStyle $style,
        Typography $typography,
        string $expected,
    ): void {
        $actual = Units::default()->format('kilometers / seconds^2', new FormatOptions(
            unitNames: $style,
            typography: $typography,
        ));

        $this->assertSame($expected, $actual);
    }

    /**
     * @return iterable<string, array{UnitNameStyle, Typography, string}>
     */
    public static function unitNameStyleProvider(): iterable
    {
        yield 'preserved ASCII' => [
            UnitNameStyle::Preserve,
            Typography::Ascii,
            'kilometers / seconds ^ 2',
        ];
        yield 'canonical ASCII' => [
            UnitNameStyle::Canonical,
            Typography::Ascii,
            'kilometer / second ^ 2',
        ];
        yield 'symbol ASCII' => [
            UnitNameStyle::Symbol,
            Typography::Ascii,
            'km / s ^ 2',
        ];
        yield 'canonical Unicode' => [
            UnitNameStyle::Canonical,
            Typography::Unicode,
            'kilometer / second²',
        ];
        yield 'symbol Unicode' => [
            UnitNameStyle::Symbol,
            Typography::Unicode,
            'km / s²',
        ];
    }

    public function testSymbolSelectionRespectsTypographyAndFallsBackToCanonicalName(): void
    {
        $units = Units::default();

        $this->assertSame('ohm', $units->format('ohm', new FormatOptions(
            unitNames: UnitNameStyle::Symbol,
            typography: Typography::Ascii,
        )));
        $this->assertSame('Ω', $units->format('ohm', new FormatOptions(
            unitNames: UnitNameStyle::Symbol,
            typography: Typography::Unicode,
        )));
        $this->assertSame('L', $units->format('litres', new FormatOptions(
            unitNames: UnitNameStyle::Symbol,
        )));
    }

    public function testExactNamesWinBeforePrefixDecomposition(): void
    {
        $options = new FormatOptions(unitNames: UnitNameStyle::Canonical);
        $units = Units::default();

        $this->assertSame('pascal', $units->format('Pa', $options));
        $this->assertSame('picoare', $units->format('pa', $options));
    }

    public function testCustomUnitsAndUnknownLeavesUseDeterministicFallbacks(): void
    {
        $registry = UnitRegistryBuilder::default()
            ->define('widget = meter')
            ->alias('gadget', 'widget')
            ->build();
        $units = new Units($registry);

        $this->assertSame('widget', $units->format('gadget', new FormatOptions(
            unitNames: UnitNameStyle::Canonical,
        )));
        $this->assertSame('kwidget', $units->format('kilowidget', new FormatOptions(
            unitNames: UnitNameStyle::Symbol,
        )));
        $this->assertSame('unknown', $units->format('unknown', new FormatOptions(
            unitNames: UnitNameStyle::Canonical,
        )));
    }

    #[DataProvider('dimensionlessStyleProvider')]
    public function testFormatsDimensionlessExpressionsAndQuantities(
        DimensionlessStyle $style,
        string $expectedIdentity,
        string $expectedFraction,
        string $expectedQuantity,
    ): void {
        $units = Units::default();
        $options = new FormatOptions(dimensionless: $style);

        $this->assertSame($expectedIdentity, $units->format('1', $options));
        $this->assertSame($expectedFraction, $units->format('1 / 2', $options));
        $this->assertSame($expectedQuantity, $units->quantity(3, '1')->format($options));
    }

    /**
     * @return iterable<string, array{DimensionlessStyle, string, string, string}>
     */
    public static function dimensionlessStyleProvider(): iterable
    {
        yield 'one' => [DimensionlessStyle::One, '1', '1/2', '3'];
        yield 'word' => [DimensionlessStyle::Word, 'dimensionless', '1/2 * dimensionless', '3 * dimensionless'];
        yield 'empty' => [DimensionlessStyle::Empty, '', '1/2', '3'];
    }

    public function testUnicodeTypographyUsesFractionsAndParenthesizedDenominators(): void
    {
        $actual = Units::default()->format('3 * meter^2 / (second^3 * kilogram)', new FormatOptions(
            typography: Typography::Unicode,
        ));

        $this->assertSame('3 · meter² / (kilogram · second³)', $actual);
    }
}
