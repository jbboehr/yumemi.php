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

use jbboehr\Yumemi\Exception\DivisionByZeroError;
use jbboehr\Yumemi\Exception\OverflowException;
use jbboehr\Yumemi\Exception\UnsupportedSyntaxException;
use jbboehr\Yumemi\Expr\Unit;
use jbboehr\Yumemi\Formatter\DimensionlessStyle;
use jbboehr\Yumemi\Formatter\DivisionStyle;
use jbboehr\Yumemi\Formatter\FormatOptions;
use jbboehr\Yumemi\Formatter\Typography;
use jbboehr\Yumemi\Formatter\UnitNameStyle;
use jbboehr\Yumemi\Parser\ExpressionLimitExceededException;
use jbboehr\Yumemi\Parser\ParseException;
use jbboehr\Yumemi\Registry\CompositeUnitRegistry;
use jbboehr\Yumemi\Registry\UnitRegistry;
use jbboehr\Yumemi\Registry\UnitRegistryBuilder;
use jbboehr\Yumemi\Units;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FormattingPolicyTest extends TestCase
{
    public function testFormatOptionsSupportImmutableFluentConstruction(): void
    {
        $defaults = FormatOptions::create();
        $unitNames = $defaults->withUnitNameStyle(UnitNameStyle::Symbol);
        $typography = $unitNames->withTypography(Typography::Unicode);
        $dimensionless = $typography->withDimensionlessStyle(DimensionlessStyle::Word);
        $division = $dimensionless->withDivisionStyle(DivisionStyle::NegativePowers);

        $this->assertNotSame($defaults, $unitNames);
        $this->assertNotSame($unitNames, $typography);
        $this->assertNotSame($typography, $dimensionless);
        $this->assertNotSame($dimensionless, $division);
        $this->assertEquals(new FormatOptions(), $defaults);
        $this->assertEquals(new FormatOptions(
            unitNameStyle: UnitNameStyle::Symbol,
            typography: Typography::Unicode,
            dimensionlessStyle: DimensionlessStyle::Word,
            divisionStyle: DivisionStyle::NegativePowers,
        ), $division);
    }

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

    public function testFormatTextPreservesSpellingWithoutCatalogResolution(): void
    {
        $units = Units::default();
        $parsed = $units->parse('feet');
        $canonicalOptions = new FormatOptions(unitNameStyle: UnitNameStyle::Canonical);
        $symbolOptions = new FormatOptions(unitNameStyle: UnitNameStyle::Symbol);

        $this->assertSame('feet', $units->formatText('feet'));
        $this->assertSame('international_foot', $units->formatText('feet', $canonicalOptions));
        $this->assertSame('ft', $units->formatText('feet', $symbolOptions));
        $this->assertSame('unknown_widget', $units->formatText('unknown_widget', $canonicalOptions));
        $this->assertSame('degree_Celsius ^ 2', $units->formatText('degree_Celsius^2'));
        $this->assertSame('international_foot', $units->formatter()->format($parsed));
        $this->assertSame($units->format('feet'), $units->formatText('feet'));
    }

    #[DataProvider('formatOptionsProvider')]
    public function testFormatEntryPointsRemainEquivalentForEveryPolicy(FormatOptions $options): void
    {
        $units = Units::default();
        $expr = $units->parse('feet / seconds^2');

        foreach (['feet / (unknown_widget * seconds^2)', '1 / 2'] as $source) {
            $this->assertSame($units->formatText($source, $options), $units->format($source, $options));
        }

        $this->assertSame($units->formatter($options)->format($expr), $units->format($expr, $options));
    }

    /**
     * @return iterable<string, array{FormatOptions}>
     */
    public static function formatOptionsProvider(): iterable
    {
        foreach (UnitNameStyle::cases() as $unitNameStyle) {
            foreach (Typography::cases() as $typography) {
                foreach (DimensionlessStyle::cases() as $dimensionlessStyle) {
                    foreach (DivisionStyle::cases() as $divisionStyle) {
                        $label = implode(', ', [
                            $unitNameStyle->value,
                            $typography->value,
                            $dimensionlessStyle->value,
                            $divisionStyle->value,
                        ]);

                        yield $label => [new FormatOptions(
                            unitNameStyle: $unitNameStyle,
                            typography: $typography,
                            dimensionlessStyle: $dimensionlessStyle,
                            divisionStyle: $divisionStyle,
                        )];
                    }
                }
            }
        }
    }

    /**
     * @param class-string<\Throwable> $expectedException
     */
    #[DataProvider('formatTextFailureProvider')]
    public function testFormatTextPreservesFormattingErrorCategories(
        string $source,
        string $expectedException,
    ): void {
        $formatTextException = null;
        $formatException = null;

        try {
            Units::default()->formatText($source);
        } catch (\Throwable $exception) {
            $formatTextException = $exception;
        }

        try {
            Units::default()->format($source);
        } catch (\Throwable $exception) {
            $formatException = $exception;
        }

        $this->assertNotNull($formatTextException);
        $this->assertNotNull($formatException);
        $this->assertSame($expectedException, $formatTextException::class);
        $this->assertSame($formatTextException::class, $formatException::class);
        $this->assertSame($formatTextException->getMessage(), $formatException->getMessage());
    }

    /**
     * @return iterable<string, array{string, class-string<\Throwable>}>
     */
    public static function formatTextFailureProvider(): iterable
    {
        yield 'malformed expression' => ['meter /', ParseException::class];
        yield 'unsupported syntax' => ['meter + second', UnsupportedSyntaxException::class];
        yield 'division by zero' => ['1 / 0', DivisionByZeroError::class];
        yield 'exponent overflow' => ['meter^' . str_repeat('9', 40), OverflowException::class];
    }

    #[DataProvider('formatTextLimitProvider')]
    public function testFormatTextPreservesParserLimitMetadata(
        string $source,
        string $expectedLimit,
        int $expectedMaximum,
        int $expectedObserved,
    ): void {
        try {
            Units::default()->formatText($source);
            self::fail('Expected the parser resource limit to be exceeded.');
        } catch (ExpressionLimitExceededException $exception) {
            $this->assertSame($expectedLimit, $exception->limit);
            $this->assertSame($expectedMaximum, $exception->maximum);
            $this->assertSame($expectedObserved, $exception->observed);
        }
    }

    /**
     * @return iterable<string, array{string, string, int, int}>
     */
    public static function formatTextLimitProvider(): iterable
    {
        yield 'input bytes' => [str_repeat('a', 4097), 'input-bytes', 4096, 4097];
        yield 'token count' => [implode(' ', array_fill(0, 257, 'a')), 'token-count', 256, 257];
        yield 'nesting depth' => [
            str_repeat('(', 65) . 'a' . str_repeat(')', 65),
            'nesting-depth',
            64,
            65,
        ];
        yield 'token bytes' => [str_repeat('α', 513), 'token-bytes', 1024, 1026];
    }

    #[DataProvider('unitNameStyleProvider')]
    public function testFormatsAliasesAndDynamicPrefixes(
        UnitNameStyle $style,
        Typography $typography,
        string $expected,
    ): void {
        $actual = Units::default()->format('kilometers / seconds^2', new FormatOptions(
            unitNameStyle: $style,
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
            unitNameStyle: UnitNameStyle::Symbol,
            typography: Typography::Ascii,
        )));
        $this->assertSame('Ω', $units->format('ohm', new FormatOptions(
            unitNameStyle: UnitNameStyle::Symbol,
            typography: Typography::Unicode,
        )));
        $this->assertSame('L', $units->format('litres', new FormatOptions(
            unitNameStyle: UnitNameStyle::Symbol,
        )));
    }

    public function testSymbolSelectionPrefersTheShortestCodepointLength(): void
    {
        $units = new Units(new UnitRegistry(records: [
            'widget' => ['type' => 'base', 'name' => 'widget'],
            'aa' => ['type' => 'alias', 'name' => 'aa', 'def' => 'widget', 'aliasKind' => 'symbol'],
            'b' => ['type' => 'alias', 'name' => 'b', 'def' => 'widget', 'aliasKind' => 'symbol'],
            'c' => ['type' => 'alias', 'name' => 'c', 'def' => 'widget', 'aliasKind' => 'symbol'],
            "\xff" => ['type' => 'alias', 'name' => "\xff", 'def' => 'widget', 'aliasKind' => 'symbol'],
        ]));

        $this->assertSame('b', $units->format('widget', new FormatOptions(
            unitNameStyle: UnitNameStyle::Symbol,
            typography: Typography::Unicode,
        )));
    }

    public function testExactNamesWinBeforePrefixDecomposition(): void
    {
        $options = new FormatOptions(unitNameStyle: UnitNameStyle::Canonical);
        $units = Units::default();

        $this->assertSame('pascal', $units->format('Pa', $options));
        $this->assertSame('picoare', $units->format('pa', $options));
    }

    /** @param list<string> $definitions */
    #[DataProvider('prefixedNameCollisionProvider')]
    public function testPrefixedFormattingPreservesExactMeaning(
        array $definitions,
        string $input,
        UnitNameStyle $style,
        string $expected,
    ): void {
        $builder = UnitRegistryBuilder::default();
        foreach ($definitions as $definition) {
            $builder->define($definition);
        }
        $units = new Units($builder->build());

        foreach (Typography::cases() as $typography) {
            $options = new FormatOptions(unitNameStyle: $style, typography: $typography);
            $formatter = $units->formatter($options);
            $leaf = new Unit($input);

            $this->assertSame($expected, $units->formatText($input, $options));
            $this->assertSame($expected, $units->quantity(3, $input)->formatUnit($options));
            $this->assertSame($expected, $formatter->format($leaf));
            $this->assertSame($expected, $formatter->format($leaf));
            $this->assertSame($expected, $units->formatText($expected, $options));
            $this->assertTrue($units->normalize($input)->equals($units->normalize($expected)));
        }
    }

    /** @return iterable<string, array{list<string>, string, UnitNameStyle, string}> */
    public static function prefixedNameCollisionProvider(): iterable
    {
        yield 'minute collision' => [[], 'milliinch', UnitNameStyle::Symbol, 'milliinternational_inch'];
        yield 'knot collision' => [[], 'kilotonne', UnitNameStyle::Symbol, 'kilometric_ton'];
        yield 'ordinary prefix' => [[], 'kilometers', UnitNameStyle::Symbol, 'km'];
        yield 'exact symbol' => [[], 'kilogram', UnitNameStyle::Symbol, 'kg'];
        yield 'different dimension' => [['km = second'], 'kilometer', UnitNameStyle::Symbol, 'kilometer'];
        yield 'different scale' => [['km = 2 * meter'], 'kilometer', UnitNameStyle::Symbol, 'kilometer'];
        yield 'canonical collision' => [
            ['kilometer = second'], 'kilometre', UnitNameStyle::Canonical, 'kilometre',
        ];
        yield 'both candidates collide' => [
            ['km = second', 'kilometer = 2 * meter'], 'kilometre', UnitNameStyle::Symbol, 'kilometre',
        ];
    }

    #[DataProvider('lexicallyAmbiguousPrefixedSymbolProvider')]
    public function testPrefixedSymbolFormattingRemainsReparsable(
        string $input,
        string $expected,
        Typography $typography,
    ): void {
        $units = Units::default();
        $options = new FormatOptions(
            unitNameStyle: UnitNameStyle::Symbol,
            typography: $typography,
        );
        $formatted = $units->formatText($input, $options);
        $reformatted = $units->formatText($formatted, $options);
        $equivalentAfterReparse = false;
        $reparseError = null;

        try {
            $equivalentAfterReparse = $units->normalize($input)->equals($units->normalize($formatted));
        } catch (\Throwable $exception) {
            $reparseError = $exception::class;
        }

        $this->assertSame([
            'formatted' => $expected,
            'quantity unit' => $expected,
            'reformatted' => $expected,
            'equivalent after reparse' => true,
            'reparse error' => null,
        ], [
            'formatted' => $formatted,
            'quantity unit' => $units->quantity(3, $input)->formatUnit($options),
            'reformatted' => $reformatted,
            'equivalent after reparse' => $equivalentAfterReparse,
            'reparse error' => $reparseError,
        ]);
    }

    /** @return iterable<string, array{string, string, Typography}> */
    public static function lexicallyAmbiguousPrefixedSymbolProvider(): iterable
    {
        foreach (Typography::cases() as $typography) {
            yield 'percent after milli, ' . $typography->value => ['millipercent', 'millipercent', $typography];
            yield 'percent after tera, ' . $typography->value => ['terapercent', 'terapercent', $typography];
            yield 'arc minute after milli, ' . $typography->value => [
                'milliarc_minute', 'milliarc_minute', $typography,
            ];
            yield 'arc second after kilo, ' . $typography->value => [
                'kiloarc_second', 'kiloarc_second', $typography,
            ];
        }
    }

    public function testInvalidSymbolCandidatesFallBackWithoutRejectingValidInput(): void
    {
        foreach (['(', str_repeat('w', 1024)] as $symbol) {
            $registry = new CompositeUnitRegistry(UnitRegistry::bundled(), new UnitRegistry(records: [
                'widget' => ['type' => 'base', 'name' => 'widget'],
                $symbol => ['type' => 'alias', 'name' => $symbol, 'def' => 'widget', 'aliasKind' => 'symbol'],
            ]));
            $units = new Units($registry);
            $options = new FormatOptions(unitNameStyle: UnitNameStyle::Symbol);

            $this->assertSame('kilowidget', $units->formatText('kilowidget', $options));
        }
    }

    public function testCustomUnitsAndUnknownLeavesUseDeterministicFallbacks(): void
    {
        $registry = UnitRegistryBuilder::default()
            ->define('widget = meter')
            ->alias('gadget', 'widget')
            ->build();
        $units = new Units($registry);

        $this->assertSame('widget', $units->format('gadget', new FormatOptions(
            unitNameStyle: UnitNameStyle::Canonical,
        )));
        $this->assertSame('kwidget', $units->format('kilowidget', new FormatOptions(
            unitNameStyle: UnitNameStyle::Symbol,
        )));
        $this->assertSame('unknown', $units->format('unknown', new FormatOptions(
            unitNameStyle: UnitNameStyle::Canonical,
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
        $options = new FormatOptions(dimensionlessStyle: $style);

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

    #[DataProvider('negativePowerProvider')]
    public function testFormatsDivisionAsNegativePowers(string $input, string $expected): void
    {
        $options = FormatOptions::create()->withDivisionStyle(DivisionStyle::NegativePowers);

        $this->assertSame($expected, Units::default()->format($input, $options));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function negativePowerProvider(): iterable
    {
        yield 'single denominator' => ['meter / second', 'meter * second ^ -1'];
        yield 'powered denominator' => ['meter / second^2', 'meter * second ^ -2'];
        yield 'multiple denominator factors' => [
            'meter / (kilogram * second^2)',
            'meter * kilogram ^ -1 * second ^ -2',
        ];
        yield 'denominator only' => ['1 / second', 'second ^ -1'];
        yield 'rational coefficient remains rational' => [
            '1/2 * meter / second',
            '1/2 * meter * second ^ -1',
        ];
    }

    public function testUnicodeSymbolNegativePowerOutput(): void
    {
        $options = FormatOptions::create()
            ->withUnitNameStyle(UnitNameStyle::Symbol)
            ->withTypography(Typography::Unicode)
            ->withDivisionStyle(DivisionStyle::NegativePowers);

        $this->assertSame(
            '1/2 · m · kg⁻¹ · s⁻²',
            Units::default()->format('1/2 * meter / (kilogram * second^2)', $options),
        );
    }
}
