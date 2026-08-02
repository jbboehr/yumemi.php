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

use jbboehr\Yumemi\Exception\IncompatibleUnitException;
use jbboehr\Yumemi\Exception\UnitNotFoundException;
use jbboehr\Yumemi\Exception\UnsupportedUnitAlgebraException;
use jbboehr\Yumemi\Exception\UnsupportedUnitConversionException;
use jbboehr\Yumemi\Number\Rational;
use jbboehr\Yumemi\Parser\ParseException;
use jbboehr\Yumemi\Registry\UnitRegistryBuilder;
use jbboehr\Yumemi\Units;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function jbboehr\Yumemi\unit;
use function jbboehr\Yumemi\unit_factor;
use function jbboehr\Yumemi\unit_to;

final class UnitFunctionTest extends TestCase
{
    public function testReturnsMagnitudeUnchanged(): void
    {
        $this->assertSame(1500.0, unit(1500.0, 'kilogram'));
        $this->assertSame(3, unit(3, 'meter'));
    }

    public function testNativeHelpersUseConfiguredDefaultContext(): void
    {
        $units = new Units(
            UnitRegistryBuilder::default()
                ->define('widget = 2 * meter')
                ->build(),
        );
        $previous = Units::setDefault($units);
        $widget = 'widget';
        $meter = 'meter';

        try {
            $this->assertSame(3, unit(3, $widget));
            $this->assertSame(2.0, unit_factor($widget, $meter));
            $this->assertSame(6.0, unit_to(3, $widget, $meter));
        } finally {
            Units::setDefault($previous);
        }
    }

    public function testRejectsUnknownUnit(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid unit expression');

        unit(1.0, 'not_a_real_unit_xyz'); // @phpstan-ignore yumemi.invalidUnitCall (intentional: exercises the runtime rejection path)
    }

    /**
     * @return iterable<string, array{0: string, 1: string, 2: float}>
     */
    public static function unitFactorProvider(): iterable
    {
        yield 'meter to foot' => ['meter', 'foot', 1250 / 381];
        yield 'foot to meter' => ['foot', 'meter', 0.3048];
        yield 'compound speed' => ['meter / second', 'kilometer / hour', 3.6];
        yield 'integral factor' => ['meter', 'centimeter', 100.0];
        yield 'derived identity' => ['newton', 'kilogram * meter / second^2', 1.0];
        yield 'identity' => ['meter', 'meter', 1.0];
        yield 'alias identity' => ['foot', 'feet', 1.0];
    }

    #[DataProvider('unitFactorProvider')]
    public function testUnitFactorReturnsNativeConversionRatio(string $from, string $to, float $expected): void
    {
        $this->assertEqualsWithDelta($expected, unit_factor($from, $to), 1e-12);
    }

    public function testUnitFactorAlwaysReturnsFloat(): void
    {
        $this->assertSame(1.0, unit_factor('meter', 'meter'));
        $this->assertSame(100.0, unit_factor('meter', 'centimeter'));
    }

    public function testUnitFactorConvertsNativeMagnitudeByMultiplication(): void
    {
        $meters = unit(3, 'meter');
        $feet = $meters * unit_factor('meter', 'foot');

        $this->assertEqualsWithDelta(3 * 1250 / 381, $feet, 1e-12);
    }

    /**
     * @return iterable<string, array{0: string, 1: string, 2: string, 3: class-string<\Throwable>}>
     */
    public static function invalidUnitFactorProvider(): iterable
    {
        yield 'incompatible dimensions' => [
            'meter',
            'second',
            'Cannot calculate unit_factor()',
            IncompatibleUnitException::class,
        ];
        yield 'unknown source' => [
            'not_a_real_unit_xyz',
            'meter',
            'Invalid unit expression for unit_factor()',
            UnitNotFoundException::class,
        ];
        yield 'unknown target' => [
            'meter',
            'not_a_real_unit_xyz',
            'Invalid unit expression for unit_factor()',
            UnitNotFoundException::class,
        ];
        yield 'malformed source' => [
            'meter /',
            'meter',
            'Invalid unit expression for unit_factor()',
            ParseException::class,
        ];
        yield 'malformed target' => [
            'meter',
            'second /',
            'Invalid unit expression for unit_factor()',
            ParseException::class,
        ];
        yield 'affine conversion' => [
            'celsius',
            'kelvin',
            'Cannot calculate unit_factor()',
            UnsupportedUnitAlgebraException::class,
        ];
        yield 'affine identity' => [
            'celsius',
            'celsius',
            'Cannot calculate unit_factor()',
            UnsupportedUnitAlgebraException::class,
        ];
        yield 'logarithmic unit' => [
            'B',
            'B',
            'Cannot calculate unit_factor()',
            UnsupportedUnitAlgebraException::class,
        ];
    }

    /**
     * @param non-empty-string $message
     * @param class-string<object> $cause
     */
    #[DataProvider('invalidUnitFactorProvider')]
    public function testUnitFactorRejectsInvalidOrNonMultiplicativeUnits(
        string $from,
        string $to,
        string $message,
        string $cause,
    ): void {
        try {
            unit_factor($from, $to);
            self::fail('Expected an invalid unit factor to be rejected.');
        } catch (\InvalidArgumentException $exception) {
            $this->assertStringStartsWith($message, $exception->getMessage());
            $this->assertInstanceOf($cause, $exception->getPrevious());
        }
    }

    public function testUnitFactorRejectsFloatOverflowAndUnderflow(): void
    {
        $powerOfTen = '1' . str_repeat('0', 400);

        try {
            unit_factor('meter', '1 / ' . $powerOfTen . ' * meter');
            self::fail('Expected an overflowing conversion factor to be rejected.');
        } catch (\OverflowException $exception) {
            $this->assertStringContainsString('does not fit in a finite float', $exception->getMessage());
        }

        $this->expectException(\UnderflowException::class);
        $this->expectExceptionMessage('rounds to zero as a float');

        unit_factor('meter', $powerOfTen . ' * meter');
    }

    public function testUnitToPreservesFloatRangeExceptions(): void
    {
        try {
            unit_to(1, 'meter', '1e-400 * meter');
            self::fail('Expected an overflowing conversion to be rejected.');
        } catch (\OverflowException $exception) {
            $this->assertStringContainsString('does not fit in a finite float', $exception->getMessage());
            $this->assertStringNotContainsString('Invalid unit expression', $exception->getMessage());
        }

        $this->expectException(\UnderflowException::class);
        unit_to(PHP_FLOAT_MIN, 'meter', '1e308 * meter');
    }

    #[DataProvider('nonFiniteUnitToProvider')]
    public function testUnitToRejectsNonFiniteInput(float $value): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('unit_to() requires a finite input value');

        unit_to($value, 'meter', 'meter');
    }

    /** @return iterable<string, array{float}> */
    public static function nonFiniteUnitToProvider(): iterable
    {
        yield 'positive infinity' => [INF];
        yield 'negative infinity' => [-INF];
        yield 'not a number' => [NAN];
    }

    /**
     * @return iterable<string, array{0: int|float, 1: string, 2: string}>
     */
    public static function unitToCompatibleProvider(): iterable
    {
        // length
        yield 'foot → meter' => [3.0, 'foot', 'meter'];
        yield 'meter → foot' => [1.0, 'meter', 'foot'];
        yield 'inch → centimeter' => [1.0, 'inch', 'centimeter'];
        yield 'yard → meter' => [1.0, 'yard', 'meter'];
        yield 'kilometer → meter' => [1.0, 'kilometer', 'meter'];
        yield 'meter → centimeter' => [1.0, 'meter', 'centimeter'];
        yield 'centimeter → meter' => [100.0, 'centimeter', 'meter'];
        yield 'mile → kilometer' => [1.0, 'mile', 'kilometer'];

        // mass
        yield 'pound → kilogram' => [1.0, 'pound', 'kilogram'];
        yield 'kilogram → gram' => [1.0, 'kilogram', 'gram'];
        yield 'gram → kilogram' => [1000.0, 'gram', 'kilogram'];

        // time
        yield 'hour → second' => [1.0, 'hour', 'second'];
        yield 'second → hour' => [3600.0, 'second', 'hour'];
        yield 'minute → second' => [1.0, 'minute', 'second'];

        // volume / area
        yield 'liter → meter^3' => [1.0, 'liter', 'meter^3'];
        yield 'meter^2 → centimeter^2' => [1.0, 'meter^2', 'centimeter^2'];

        // derived SI (factor 1 after normalize)
        yield 'newton → kg·m/s²' => [1.0, 'newton', 'kilogram * meter / second^2'];
        yield 'joule → newton·meter' => [1.0, 'joule', 'newton * meter'];
        yield 'pascal → newton/m²' => [1.0, 'pascal', 'newton / meter^2'];
        yield 'watt → joule/second' => [1.0, 'watt', 'joule / second'];

        // compound speed
        yield 'mile/hour → meter/second' => [60.0, 'mile / hour', 'meter / second'];
        yield 'meter/second → kilometer/hour' => [1.0, 'meter / second', 'kilometer / hour'];
        yield 'knot → meter/second' => [1.0, 'knot', 'meter / second'];

        // identity / alias scale (factor 1)
        yield 'meter → meter' => [5.0, 'meter', 'meter'];
        yield 'kilometer → 1000*meter' => [2.0, 'kilometer', '1000 * meter'];

        // int magnitude
        yield 'int inches → meters' => [12, 'inch', 'meter'];
    }

    #[DataProvider('unitToCompatibleProvider')]
    public function testUnitToMatchesCatalogFactor(int|float $value, string $from, string $to): void
    {
        $expected = $this->expectedConvertedFloat($value, $from, $to);
        $actual = unit_to($value, $from, $to);

        $this->assertEqualsWithDelta(
            $expected,
            $actual,
            1e-9,
            sprintf('unit_to(%s, %s, %s)', var_export($value, true), $from, $to),
        );
    }

    /**
     * @return iterable<string, array{0: int|float, 1: string, 2: string}>
     */
    public static function unitToRoundTripProvider(): iterable
    {
        yield 'foot ↔ meter' => [5.0, 'foot', 'meter'];
        yield 'pound ↔ kilogram' => [10.0, 'pound', 'kilogram'];
        yield 'mile/hour ↔ meter/second' => [60.0, 'mile / hour', 'meter / second'];
        yield 'liter ↔ meter^3' => [2.5, 'liter', 'meter^3'];
        yield 'inch ↔ centimeter' => [7.0, 'inch', 'centimeter'];
    }

    #[DataProvider('unitToRoundTripProvider')]
    public function testUnitToRoundTrip(int|float $value, string $from, string $to): void
    {
        $converted = unit_to($value, $from, $to);
        $back = unit_to($converted, $to, $from);

        $this->assertEqualsWithDelta((float) $value, $back, 1e-9);
    }

    /**
     * @return iterable<string, array{0: string, 1: string}>
     */
    public static function unitToIncompatibleProvider(): iterable
    {
        yield 'length vs time' => ['meter', 'second'];
        yield 'mass vs length' => ['kilogram', 'meter'];
        yield 'force vs energy' => ['newton', 'joule'];
        yield 'speed vs acceleration' => ['meter / second', 'meter / second^2'];
        yield 'area vs volume' => ['meter^2', 'meter^3'];
        yield 'unknown unit' => ['meter', 'not_a_real_unit_xyz'];
        yield 'from unknown' => ['not_a_real_unit_xyz', 'meter'];
    }

    #[DataProvider('unitToIncompatibleProvider')]
    public function testUnitToRejectsIncompatibleOrInvalid(string $from, string $to): void
    {
        $this->expectException(\InvalidArgumentException::class);

        unit_to(1.0, $from, $to);
    }

    public function testUnitToWithBrandedUnitValue(): void
    {
        $feet = unit(3.0, 'foot');
        $meters = unit_to($feet, 'foot', 'meter');

        $this->assertEqualsWithDelta($this->expectedConvertedFloat(3.0, 'foot', 'meter'), $meters, 1e-12);
    }

    public function testUnitToHandlesBalancedConversionFactorBeyondFloatRange(): void
    {
        $powerOfTen = '1' . str_repeat('0', 400);
        $slightlyLarger = gmp_strval(gmp_add(gmp_init($powerOfTen), 1));

        $this->assertSame(1.0, unit_to(1, $slightlyLarger . ' * meter', $powerOfTen . ' * meter'));
    }

    public function testUnitToSupportsAffineIntegerAndFloatConversions(): void
    {
        $this->assertSame(32.0, unit_to(0, 'celsius', 'fahrenheit'));
        $this->assertEqualsWithDelta(98.6, unit_to(37.0, 'celsius', 'fahrenheit'), 1e-12);
        $this->assertSame(0.0, unit_to(32, 'fahrenheit', 'celsius'));
    }

    public function testUnitToReportsUnsupportedConversionSemantics(): void
    {
        try {
            self::callUnitTo(1, 'B', '1');
            self::fail('Expected logarithmic conversion to be rejected.');
        } catch (\InvalidArgumentException $exception) {
            $this->assertStringStartsWith('Cannot convert with unit_to()', $exception->getMessage());
            $this->assertInstanceOf(UnsupportedUnitConversionException::class, $exception->getPrevious());
        }
    }

    private function expectedConvertedFloat(int|float $value, string $from, string $to): float
    {
        $factor = Units::default()->conversionFactor($from, $to);

        // Prefer exact rational arithmetic when the magnitude is an int.
        if (is_int($value)) {
            $converted = Units::default()->convert($value, $from, $to);

            return $this->rationalToFloat($converted);
        }

        return $value * $this->rationalToFloat($factor);
    }

    private static function callUnitTo(int|float $value, string $from, string $to): float
    {
        return unit_to($value, $from, $to);
    }

    private function rationalToFloat(Rational $rational): float
    {
        return (float) gmp_strval($rational->numerator) / (float) gmp_strval($rational->denominator);
    }
}
