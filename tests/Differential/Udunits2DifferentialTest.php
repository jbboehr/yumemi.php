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

namespace jbboehr\Yumemi\Tests\Differential;

use jbboehr\Yumemi\Exception\IncompatibleUnitException;
use jbboehr\Yumemi\Exception\UnitNotFoundException;
use jbboehr\Yumemi\Exception\UnsupportedSyntaxException;
use jbboehr\Yumemi\Exception\UnsupportedUnitConversionException;
use jbboehr\Yumemi\Number\Rational;
use jbboehr\Yumemi\Units;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Compares Yumemi with the independently implemented UDUNITS2 executable.
 *
 * The parsers are related but deliberately not identical. Shared syntax is passed to both implementations directly;
 * dialect-specific syntax uses separate, explicitly equivalent spellings. This suite does not compare AST shape,
 * formatting, postfix-power shorthand, timestamps, or Yumemi's parsed-but-unsupported addition and subtraction nodes.
 */
#[Group('udunits2')]
final class Udunits2DifferentialTest extends TestCase
{
    private const ABSOLUTE_TOLERANCE = 5.0e-12;
    private const RELATIVE_TOLERANCE = 5.0e-6;

    private Udunits2Cli $udunits2;

    protected function setUp(): void
    {
        $udunits2 = Udunits2Cli::discover();
        if ($udunits2 === null) {
            self::markTestSkipped(
                'The UDUNITS2 executable and matching UDUNITS2_XML or UDUNITS_XML_DIR database are required.',
            );
        }

        $this->udunits2 = $udunits2;
    }

    #[DataProvider('conversionProvider')]
    public function testConversionsMatchUdunits2(
        string $value,
        string $yumemiFrom,
        string $yumemiTo,
        string $udunits2From,
        string $udunits2To,
        string $expectedExact,
    ): void {
        $exact = Units::default()->convert(Rational::fromDecimalString($value), $yumemiFrom, $yumemiTo);
        self::assertSame($expectedExact, $exact->toString());

        $external = $this->udunits2->convert($value, $udunits2From, $udunits2To);
        self::assertSame(Udunits2Cli::CONVERTED, $external['status'], self::describeResult($external));
        self::assertNotNull($external['value'], self::describeResult($external));

        $expectedFloat = $exact->toFloat();
        $tolerance = self::ABSOLUTE_TOLERANCE + abs($expectedFloat) * self::RELATIVE_TOLERANCE;
        self::assertEqualsWithDelta(
            $expectedFloat,
            $external['value'],
            $tolerance,
            self::describeResult($external),
        );
    }

    /**
     * @return iterable<string, array{string, string, string, string, string, string}>
     */
    public static function conversionProvider(): iterable
    {
        yield 'base unit to accepted unit' => [
            '1', 'meter', 'international_foot', 'meter', 'international_foot', '1250/381',
        ];
        yield 'accepted time unit' => ['1', 'minute', 'second', 'minute', 'second', '60'];
        yield 'accepted velocity unit' => ['1', 'knot', 'meter / second', 'knot', 'meter / second', '463/900'];
        yield 'constant and prefix' => ['100', 'centimeter', 'meter', 'centimeter', 'meter', '1'];
        yield 'dynamic prefix' => ['1', 'micrometer', 'meter', 'micrometer', 'meter', '1/1000000'];
        yield 'compound conversion' => [
            '90', 'kilometer / hour', 'meter / second', 'kilometer / hour', 'meter / second', '25',
        ];
        yield 'symbol alias' => ['7', 'Pa', 'pascal', 'Pa', 'pascal', '7'];
        yield 'derived definition' => [
            '3', 'newton / meter^2', 'pascal', 'newton / meter^2', 'pascal', '3',
        ];
        yield 'shared left-associative precedence' => [
            '1',
            'meter / second kilogram',
            'meter kilogram / second',
            'meter / second kilogram',
            'meter kilogram / second',
            '1',
        ];
        yield 'grouped denominator' => [
            '1',
            'meter / (second * kilogram)',
            'meter / second / kilogram',
            'meter / (second.kilogram)',
            'meter / second / kilogram',
            '1',
        ];
        yield 'Celsius origin' => ['0', 'celsius', 'kelvin', 'degree_Celsius', 'kelvin', '5463/20'];
        yield 'Celsius to Fahrenheit' => [
            '100', 'celsius', 'fahrenheit', 'degree_Celsius', 'degree_Fahrenheit', '212',
        ];
        yield 'Fahrenheit cancellation at freezing point' => [
            '32', 'fahrenheit', 'celsius', 'degree_Fahrenheit', 'degree_Celsius', '0',
        ];
        yield 'explicit affine expression' => [
            '1', 'kelvin @ 273.15', 'celsius', 'kelvin @ 273.15', 'degree_Celsius', '1',
        ];
        yield 'Unicode temperature symbol' => ['20', '°C', 'kelvin', '°C', 'kelvin', '5863/20'];
        yield 'explicit multiplication versus adjacency' => [
            '4',
            'kilogram * meter / second^2',
            'newton',
            'kilogram meter / second^2',
            'newton',
            '4',
        ];
        yield 'Unicode product and power syntax' => [
            '3',
            'meter · second⁻²',
            'meter / second^2',
            'meter.second^-2',
            'meter / second^2',
            '3',
        ];
    }

    #[DataProvider('incompatibleProvider')]
    public function testIncompatibleUnitsMatchUdunits2(
        string $yumemiFrom,
        string $yumemiTo,
        string $udunits2From,
        string $udunits2To,
    ): void {
        try {
            Units::default()->convert(new Rational(1), $yumemiFrom, $yumemiTo);
            self::fail(sprintf('Expected %s and %s to be incompatible.', $yumemiFrom, $yumemiTo));
        } catch (IncompatibleUnitException) {
        }

        $external = $this->udunits2->convert('1', $udunits2From, $udunits2To);
        self::assertSame(Udunits2Cli::INCOMPATIBLE, $external['status'], self::describeResult($external));
        self::assertNull($external['value']);
    }

    /**
     * @return iterable<string, array{string, string, string, string}>
     */
    public static function incompatibleProvider(): iterable
    {
        yield 'length and time' => ['meter', 'second', 'meter', 'second'];
        yield 'velocity and length-time product' => [
            'meter / second', 'meter * second', 'meter / second', 'meter.second',
        ];
        yield 'temperature and length' => ['celsius', 'meter', 'degree_Celsius', 'meter'];
    }

    public function testUnknownUnitRejectionMatchesUdunits2(): void
    {
        try {
            Units::default()->convert(new Rational(1), 'definitely_not_a_yumemi_unit', 'meter');
            self::fail('Expected Yumemi to reject an unknown unit.');
        } catch (UnitNotFoundException) {
        }

        $external = $this->udunits2->convert('1', 'definitely_not_a_yumemi_unit', 'meter');
        self::assertSame(Udunits2Cli::UNRECOGNIZED, $external['status'], self::describeResult($external));
        self::assertNull($external['value']);
    }

    public function testNonIntegerPowerIsAnIntentionalSemanticDivergence(): void
    {
        try {
            Units::default()->convert(new Rational(1), 'meter^0.5', 'meter^0.5');
            self::fail('Expected Yumemi to reject a non-integer unit power.');
        } catch (UnsupportedSyntaxException) {
        }

        $external = $this->udunits2->convert('1', 'meter^0.5', 'meter^0.5');
        self::assertSame(Udunits2Cli::CONVERTED, $external['status'], self::describeResult($external));
        self::assertSame(1.0, $external['value']);
    }

    public function testLogarithmicUnitIsAnIntentionalSemanticDivergence(): void
    {
        try {
            Units::default()->convert(new Rational(1), 'B', '1');
            self::fail('Expected Yumemi to reject logarithmic conversion.');
        } catch (UnsupportedUnitConversionException) {
        }

        $external = $this->udunits2->convert('1', 'B', '1');
        self::assertSame(Udunits2Cli::CONVERTED, $external['status'], self::describeResult($external));
        self::assertSame(10.0, $external['value']);
    }

    /**
     * @param array{status: string, value: float|null, exitCode: int, stdout: string, stderr: string} $result
     */
    private static function describeResult(array $result): string
    {
        return sprintf(
            "UDUNITS2 status %s (exit %d).\nstdout:\n%s\nstderr:\n%s",
            $result['status'],
            $result['exitCode'],
            $result['stdout'],
            $result['stderr'],
        );
    }
}
