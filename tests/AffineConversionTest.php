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
use jbboehr\Yumemi\Exception\NonMultiplicativeConversionException;
use jbboehr\Yumemi\Exception\UnitNotFoundException;
use jbboehr\Yumemi\Exception\UnsupportedSyntaxException;
use jbboehr\Yumemi\Exception\UnsupportedUnitAlgebraException;
use jbboehr\Yumemi\Exception\UnsupportedUnitConversionException;
use jbboehr\Yumemi\Number\Rational;
use jbboehr\Yumemi\Registry\UnitRegistryBuilder;
use jbboehr\Yumemi\Units;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AffineConversionTest extends TestCase
{
    /**
     * @return iterable<string, array{int|Rational, string, string, string}>
     */
    public static function exactTemperatureProvider(): iterable
    {
        yield 'Celsius freezing point in kelvin' => [0, 'celsius', 'kelvin', '5463/20'];
        yield 'Celsius boiling point in Fahrenheit' => [100, 'celsius', 'fahrenheit', '212'];
        yield 'Fahrenheit freezing point in Celsius' => [32, 'fahrenheit', 'celsius', '0'];
        yield 'negative forty crossover' => [-40, 'celsius', 'fahrenheit', '-40'];
        yield 'absolute zero in Celsius' => [0, 'kelvin', 'celsius', '-5463/20'];
        yield 'exact decimal input' => [
            Rational::fromDecimalString('273.15'),
            'kelvin',
            'degree_Celsius',
            '0',
        ];
        yield 'Rankine remains multiplicative' => [180, 'degree_rankine', 'kelvin', '100'];
    }

    #[DataProvider('exactTemperatureProvider')]
    public function testExactAffineConversions(
        int|Rational $value,
        string $from,
        string $to,
        string $expected,
    ): void {
        $this->assertSame($expected, Units::default()->convert($value, $from, $to)->toString());
    }

    public function testAffineAliasesAndSymbolsUseTheSameCoordinateSystem(): void
    {
        $units = Units::default();

        $this->assertSame('32', $units->convert(0, '°C', '°F')->toString());
        $this->assertSame('100', $units->convert(212, 'degF', 'degrees_Celsius')->toString());
        $this->assertSame('1', $units->conversionFactor('celsius', 'degree_Celsius')->toString());
    }

    public function testDirectAtExpressionIsAcceptedOnlyByConversionApis(): void
    {
        $units = Units::default();

        $this->assertSame('5463/20', $units->convert(0, 'kelvin @ 273.15', 'kelvin')->toString());
        $this->assertTrue($units->areCompatible('kelvin @ 273.15', 'degree_Celsius'));

        $this->expectException(UnsupportedSyntaxException::class);
        $this->expectExceptionMessage(
            'Affine / offset syntax is not valid in multiplicative unit algebra. Use Units::convert()',
        );
        $units->parse('kelvin @ 273.15');
    }

    public function testDirectAtExpressionAcceptsNegativeOrigin(): void
    {
        $units = Units::default();

        $this->assertSame('-5463/20', $units->convert(0, 'kelvin @ -273.15', 'kelvin')->toString());
        $this->assertSame(
            '0',
            $units->convert(Rational::fromDecimalString('273.15'), 'kelvin @ -273.15', 'kelvin')->toString(),
        );
    }

    public function testDirectAtExpressionTreatsLeadingZeroOffsetsAsDecimal(): void
    {
        $this->assertSame('9', Units::default()->convert(0, 'kelvin @ 09', 'kelvin')->toString());
    }

    public function testAffineUnitsExposeTheirReferenceDimension(): void
    {
        $units = Units::default();

        $this->assertSame('temperature', $units->dimension('celsius')->toString());
        $this->assertTrue($units->areCompatible('fahrenheit', 'kelvin'));
        $this->assertFalse($units->areCompatible('celsius', 'meter'));
    }

    public function testConversionFactorRejectsValueDependentConversion(): void
    {
        $this->expectException(NonMultiplicativeConversionException::class);
        $this->expectExceptionMessage('includes an offset');

        Units::default()->conversionFactor('celsius', 'kelvin');
    }

    public function testConversionFactorAllowsAffineIdentityAndRankine(): void
    {
        $units = Units::default();

        $this->assertSame('1', $units->conversionFactor('celsius', 'degC')->toString());
        $this->assertSame('5/9', $units->conversionFactor('degree_rankine', 'kelvin')->toString());
    }

    public function testZeroOffsetAffineDefinitionHasAValueIndependentFactor(): void
    {
        $units = new Units(UnitRegistryBuilder::default()
            ->define('absolute_kelvin = kelvin @ 0')
            ->build());

        $this->assertSame('1', $units->conversionFactor('absolute_kelvin', 'kelvin')->toString());
    }

    public function testMultiplicativePowersStillResolveThroughConversionCore(): void
    {
        $units = Units::default();

        $this->assertSame('10000', $units->conversionFactor('meter ^ 2', 'centimeter ^ 2')->toString());
        $this->assertSame('1/1000000', $units->conversionFactor('centimeter ^ 3', 'meter ^ 3')->toString());
        $this->assertSame('1', $units->conversionFactor('meter ^ 2', 'meter * meter')->toString());
    }

    public function testConversionBoundaryRejectsUnknownNamesDirectly(): void
    {
        $this->expectException(UnitNotFoundException::class);
        $this->expectExceptionMessage('Unit not found: not_a_real_unit_xyz');

        Units::default()->convert(1, 'not_a_real_unit_xyz', 'meter');
    }

    public function testConvertFloatAppliesScaleAndOffsetWithoutRationalizingTheInput(): void
    {
        $units = Units::default();

        $this->assertEqualsWithDelta(98.6, $units->convertFloat(37.0, 'celsius', 'fahrenheit'), 1e-12);
        $this->assertEqualsWithDelta(0.0, $units->convertFloat(32.0, 'fahrenheit', 'celsius'), 1e-12);
    }

    #[DataProvider('nonFiniteFloatProvider')]
    public function testConvertFloatRejectsNonFiniteInput(float $value): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('requires a finite input value');

        Units::default()->convertFloat($value, 'meter', 'meter');
    }

    /** @return iterable<string, array{float}> */
    public static function nonFiniteFloatProvider(): iterable
    {
        yield 'positive infinity' => [INF];
        yield 'negative infinity' => [-INF];
        yield 'not a number' => [NAN];
    }

    public function testConvertFloatRejectsResultOverflow(): void
    {
        $this->expectException(\OverflowException::class);
        $this->expectExceptionMessage('does not fit in a finite float');

        Units::default()->convertFloat(2.0, 'meter', '1e-308 * meter');
    }

    public function testConvertFloatRejectsNonzeroResultUnderflow(): void
    {
        $this->expectException(\UnderflowException::class);
        $this->expectExceptionMessage('rounds to zero as a float');

        Units::default()->convertFloat(PHP_FLOAT_MIN, 'meter', '1e308 * meter');
    }

    public function testIncompatibleAffineConversionStillFailsByDimension(): void
    {
        $this->expectException(IncompatibleUnitException::class);
        $this->expectExceptionMessage('Dimensions: temperature vs length');

        Units::default()->convert(1, 'celsius', 'meter');
    }

    #[DataProvider('invalidAffineAlgebraProvider')]
    public function testAffineUnitsCannotParticipateInMultiplicativeAlgebra(string $expression): void
    {
        try {
            Units::default()->convert(1, $expression, 'kelvin');
            self::fail('Expected affine algebra to be rejected.');
        } catch (UnsupportedSyntaxException $exception) {
            $this->assertStringContainsString('Affine unit', $exception->getMessage());
            $this->assertNotNull($exception->span);
            $this->assertSame(0, $exception->span->start);
            $this->assertSame(strlen($expression), $exception->span->end);
        }
    }

    /** @return iterable<string, array{string}> */
    public static function invalidAffineAlgebraProvider(): iterable
    {
        yield 'multiplication' => ['celsius * meter'];
        yield 'right-hand multiplication' => ['meter * celsius'];
        yield 'division' => ['celsius / meter'];
        yield 'right-hand division' => ['meter / celsius'];
        yield 'power' => ['celsius ^ 2'];
        yield 'prefix' => ['kilocelsius'];
    }

    #[DataProvider('additiveSyntaxProvider')]
    public function testAdditiveUnitSyntaxRemainsUnsupportedAtConversionBoundary(string $expression): void
    {
        try {
            Units::default()->convert(1, $expression, 'meter');
            self::fail('Expected additive unit syntax to be rejected.');
        } catch (UnsupportedSyntaxException $exception) {
            $this->assertStringContainsString(
                'Addition and subtraction in unit expressions are not supported',
                $exception->getMessage(),
            );
            $this->assertNotNull($exception->span);
            $this->assertSame(0, $exception->span->start);
            $this->assertSame(strlen($expression), $exception->span->end);
        }
    }

    /** @return iterable<string, array{string}> */
    public static function additiveSyntaxProvider(): iterable
    {
        yield 'addition' => ['meter + meter'];
        yield 'subtraction' => ['meter - meter'];
    }

    public function testLogarithmicUnitsRemainUnsupportedAtConversionBoundary(): void
    {
        try {
            Units::default()->convert(1, 'B', '1');
            self::fail('Expected logarithmic conversion to be rejected.');
        } catch (UnsupportedUnitConversionException $exception) {
            $this->assertSame('B', $exception->unitName);
            $this->assertSame(UnitSemantics::Logarithmic, $exception->semantics);
            $this->assertSame('lg(re 1)', $exception->definition);
            $this->assertStringContainsString(
                'Conversion of unit "B" with logarithmic semantics is not supported',
                $exception->getMessage(),
            );
        }
    }

    public function testCustomAffineDefinitionsComposeExactly(): void
    {
        $registry = UnitRegistryBuilder::default()
            ->define('degree_widget = kelvin @ 100')
            ->define('warmer_widget = degree_widget @ 10')
            ->define('widget_temperature = degree_widget')
            ->alias('degW', 'degree_widget')
            ->build();
        $units = new Units($registry);

        $this->assertSame('100', $units->convert(0, 'degree_widget', 'kelvin')->toString());
        $this->assertSame('110', $units->convert(0, 'warmer_widget', 'kelvin')->toString());
        $this->assertSame('10', $units->convert(0, 'warmer_widget', 'degW')->toString());
        $this->assertSame('100', $units->convert(0, 'widget_temperature', 'kelvin')->toString());
        $this->assertSame(UnitSemantics::Affine, $units->describe('degree_widget')?->semantics);

        $this->expectException(UnsupportedUnitAlgebraException::class);
        $units->parse('degree_widget');
    }

    public function testCustomAffineDefinitionCanOverrideCatalogUnit(): void
    {
        $units = new Units(UnitRegistryBuilder::default()
            ->define('celsius = kelvin @ 0')
            ->build());

        $this->assertSame('0', $units->convert(0, 'celsius', 'kelvin')->toString());
    }

    public function testCustomAffineDefinitionCyclesAreRejected(): void
    {
        $units = new Units(UnitRegistryBuilder::default()
            ->define('loop_a = loop_b')
            ->define('loop_b = loop_a')
            ->build());

        $this->expectException(\UnexpectedValueException::class);
        // Assert the full message, including the offending unit name, so the
        // diagnostic's content is pinned rather than only its leading phrase.
        $this->expectExceptionMessage('Circular unit alias or definition for: loop_a');

        $units->convert(1, 'loop_a', 'kelvin');
    }
}
