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

use jbboehr\Yumemi\Number\Rational;
use jbboehr\Yumemi\Units;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function jbboehr\Yumemi\unit;
use function jbboehr\Yumemi\unit_to;

final class UnitFunctionTest extends TestCase
{
    public function testReturnsMagnitudeUnchanged(): void
    {
        $this->assertSame(1500.0, unit(1500.0, 'kilogram'));
        $this->assertSame(3, unit(3, 'meter'));
    }

    public function testRejectsUnknownUnit(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid unit expression');

        unit(1.0, 'not_a_real_unit_xyz'); // @phpstan-ignore yumemi.invalidUnitCall (intentional: exercises the runtime rejection path)
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

    private function rationalToFloat(Rational $rational): float
    {
        return (float) gmp_strval($rational->numerator) / (float) gmp_strval($rational->denominator);
    }
}
