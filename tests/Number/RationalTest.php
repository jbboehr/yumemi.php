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

namespace jbboehr\Yumemi\Tests\Number;

use jbboehr\Yumemi\Exception\NonExactRootException;
use jbboehr\Yumemi\Number\Rational;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RationalTest extends TestCase
{
    public function testAddsRationals(): void
    {
        $this->assertSame('5/6', (new Rational(1, 2))->add(new Rational(1, 3))->toString());
    }

    #[DataProvider('comparisonProvider')]
    public function testComparesRationalsExactly(Rational $left, Rational $right, int $expected): void
    {
        $this->assertSame($expected, $left->compareTo($right));
        $this->assertSame(-$expected, $right->compareTo($left));
        $this->assertSame($expected === 0, $left->equals($right));
    }

    /**
     * @return iterable<string, array{Rational, Rational, -1|0|1}>
     */
    public static function comparisonProvider(): iterable
    {
        yield 'equivalent fractions' => [new Rational(2, 4), new Rational(1, 2), 0];
        yield 'positive less than' => [new Rational(1, 3), new Rational(1, 2), -1];
        yield 'positive greater than' => [new Rational(3, 2), new Rational(4, 3), 1];
        yield 'negative less than' => [new Rational(-1, 2), new Rational(-1, 3), -1];
        yield 'zero greater than negative' => [new Rational(0), new Rational(-1), 1];
        yield 'beyond native integer range' => [
            new Rational(gmp_add(PHP_INT_MAX, 1)),
            new Rational(PHP_INT_MAX),
            1,
        ];
    }

    #[DataProvider('decimalStringProvider')]
    public function testParsesDecimalStringsExactly(string $input, string $expected): void
    {
        $this->assertSame($expected, Rational::fromDecimalString($input)->toString());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function decimalStringProvider(): iterable
    {
        yield 'leading zero decimal' => ['0.9', '9/10'];
        yield 'negative leading zero decimal' => ['-0.25', '-1/4'];
        yield 'decimal exponent' => ['0.9972696', '1246587/1250000'];
        yield 'zero decimal' => ['0.0', '0'];
    }

    #[DataProvider('invalidDecimalStringProvider')]
    public function testRejectsMalformedDecimalStrings(string $input): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Rational::fromDecimalString($input);
    }

    public function testRejectsScientificExponentBeyondSupportedRange(): void
    {
        $this->expectException(\OverflowException::class);
        $this->expectExceptionMessage('10001');

        Rational::fromDecimalString('1e10001');
    }

    public function testRejectsCombinedDecimalScaleBeyondSupportedRange(): void
    {
        $this->expectException(\OverflowException::class);

        Rational::fromDecimalString('0.' . str_repeat('0', 9_999) . '1e-1');
    }

    public function testRejectsPowerBeyondSupportedRange(): void
    {
        $this->expectException(\OverflowException::class);

        (new Rational(2))->pow(10_001);
    }

    public function testZeroPowerUsesTheComputingConvention(): void
    {
        $this->assertSame('1', (new Rational(2))->pow(0)->toString());
        $this->assertSame('1', (new Rational(-3, 2))->pow(0)->toString());
        $this->assertSame('1', (new Rational(0))->pow(0)->toString());
    }

    #[DataProvider('exactRootProvider')]
    public function testTakesExactRoots(Rational $value, int $degree, string $expected): void
    {
        $root = $value->root($degree);

        $this->assertSame($expected, $root->toString());
        $this->assertTrue($root->pow($degree)->equals($value));
    }

    /**
     * @return iterable<string, array{Rational, int, string}>
     */
    public static function exactRootProvider(): iterable
    {
        yield 'integer square' => [new Rational(16), 2, '4'];
        yield 'fraction square' => [new Rational(16, 81), 2, '4/9'];
        yield 'negative fraction cube' => [new Rational(-8, 27), 3, '-2/3'];
        yield 'zero' => [new Rational(0), 7, '0'];
        yield 'degree one' => [new Rational(-5, 7), 1, '-5/7'];
        yield 'maximum degree' => [new Rational(1), 10_000, '1'];
    }

    #[DataProvider('nonExactRootProvider')]
    public function testRejectsNonExactRoots(Rational $value, int $degree): void
    {
        $this->expectException(NonExactRootException::class);

        $value->root($degree);
    }

    /**
     * @return iterable<string, array{Rational, int}>
     */
    public static function nonExactRootProvider(): iterable
    {
        yield 'irrational numerator' => [new Rational(2), 2];
        yield 'irrational denominator' => [new Rational(1, 2), 2];
        yield 'negative even root' => [new Rational(-4), 2];
    }

    #[DataProvider('invalidRootDegreeProvider')]
    public function testRejectsNonPositiveRootDegrees(int $degree): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new Rational(1))->root($degree);
    }

    /** @return iterable<string, array{int}> */
    public static function invalidRootDegreeProvider(): iterable
    {
        yield 'zero' => [0];
        yield 'negative' => [-2];
    }

    public function testRejectsRootDegreeBeyondSupportedRange(): void
    {
        $this->expectException(\OverflowException::class);

        (new Rational(1))->root(10_001);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidDecimalStringProvider(): iterable
    {
        yield 'leading garbage' => ['value=1.5'];
        yield 'trailing garbage' => ['1.5 meters'];
        yield 'missing fractional digits' => ['1.'];
    }

    public function testNormalizesNegativeDenominator(): void
    {
        $rational = new Rational(3, -6);

        $this->assertSame('-1/2', $rational->toString());
        $this->assertSame('-1', gmp_strval($rational->numerator));
        $this->assertSame('2', gmp_strval($rational->denominator));
    }

    public function testSubtractsRationals(): void
    {
        $this->assertSame('1/6', (new Rational(1, 2))->sub(new Rational(1, 3))->toString());
    }

    #[DataProvider('integerTruncationProvider')]
    public function testConvertsToIntByTruncatingTowardZero(Rational $rational, int $expected): void
    {
        $this->assertSame($expected, $rational->toInt());
    }

    /**
     * @return iterable<string, array{Rational, int}>
     */
    public static function integerTruncationProvider(): iterable
    {
        yield 'positive fraction' => [new Rational(3, 2), 1];
        yield 'negative fraction' => [new Rational(-3, 2), -1];
        yield 'positive proper fraction' => [new Rational(1, 2), 0];
        yield 'negative proper fraction' => [new Rational(-1, 2), 0];
        yield 'positive mixed fraction' => [new Rational(7, 3), 2];
        yield 'negative mixed fraction' => [new Rational(-7, 3), -2];
    }

    public function testConvertsExactIntegerToInt(): void
    {
        $this->assertSame(42, (new Rational(42))->toIntExact());
        $this->assertSame(-42, (new Rational(-42))->toIntExact());
    }

    public function testExactIntegerConversionRejectsFraction(): void
    {
        $this->expectException(\UnexpectedValueException::class);

        (new Rational(3, 2))->toIntExact();
    }

    public function testIntegerConversionRejectsOverflow(): void
    {
        $this->expectException(\OverflowException::class);

        (new Rational(gmp_add(PHP_INT_MAX, 1)))->toInt();
    }

    public function testExactIntegerConversionRejectsOverflow(): void
    {
        $this->expectException(\OverflowException::class);

        (new Rational(gmp_sub(PHP_INT_MIN, 1)))->toIntExact();
    }

    #[DataProvider('roundedDecimalProvider')]
    public function testFormatsRoundedDecimal(
        Rational $rational,
        int $scale,
        \RoundingMode $mode,
        string $expected,
    ): void {
        $this->assertSame($expected, $rational->toDecimal($scale, $mode));
    }

    /**
     * @return iterable<string, array{Rational, int, \RoundingMode, string}>
     */
    public static function roundedDecimalProvider(): iterable
    {
        yield 'half away from zero positive tie' => [
            new Rational(5, 2),
            0,
            \RoundingMode::HalfAwayFromZero,
            '3',
        ];
        yield 'half away from zero negative tie' => [
            new Rational(-5, 2),
            0,
            \RoundingMode::HalfAwayFromZero,
            '-3',
        ];
        yield 'half towards zero positive tie' => [
            new Rational(5, 2),
            0,
            \RoundingMode::HalfTowardsZero,
            '2',
        ];
        yield 'half towards zero negative tie' => [
            new Rational(-5, 2),
            0,
            \RoundingMode::HalfTowardsZero,
            '-2',
        ];
        yield 'half even rounds even down' => [new Rational(5, 2), 0, \RoundingMode::HalfEven, '2'];
        yield 'half even rounds odd up' => [new Rational(7, 2), 0, \RoundingMode::HalfEven, '4'];
        yield 'half odd rounds even up' => [new Rational(5, 2), 0, \RoundingMode::HalfOdd, '3'];
        yield 'half odd leaves odd down' => [new Rational(7, 2), 0, \RoundingMode::HalfOdd, '3'];
        yield 'towards zero positive' => [new Rational(21, 10), 0, \RoundingMode::TowardsZero, '2'];
        yield 'towards zero negative' => [new Rational(-21, 10), 0, \RoundingMode::TowardsZero, '-2'];
        yield 'away from zero positive' => [new Rational(21, 10), 0, \RoundingMode::AwayFromZero, '3'];
        yield 'away from zero negative' => [new Rational(-21, 10), 0, \RoundingMode::AwayFromZero, '-3'];
        yield 'positive infinity positive' => [new Rational(21, 10), 0, \RoundingMode::PositiveInfinity, '3'];
        yield 'positive infinity negative' => [new Rational(-21, 10), 0, \RoundingMode::PositiveInfinity, '-2'];
        yield 'negative infinity positive' => [new Rational(21, 10), 0, \RoundingMode::NegativeInfinity, '2'];
        yield 'negative infinity negative' => [new Rational(-21, 10), 0, \RoundingMode::NegativeInfinity, '-3'];
        yield 'half mode above tie' => [new Rational(26, 10), 0, \RoundingMode::HalfTowardsZero, '3'];
        yield 'fixed decimal places' => [new Rational(1, 8), 4, \RoundingMode::HalfEven, '0.1250'];
        yield 'rounding carries into integer' => [new Rational(9999, 1000), 2, \RoundingMode::HalfEven, '10.00'];
        yield 'rounded zero has no negative sign' => [new Rational(-1, 10), 0, \RoundingMode::TowardsZero, '0'];
    }

    public function testRoundedDecimalRejectsNegativeScale(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new Rational(1, 2))->toDecimal(-1, \RoundingMode::HalfEven);
    }

    #[DataProvider('exactDecimalProvider')]
    public function testFormatsTerminatingDecimalExactly(Rational $rational, string $expected): void
    {
        $this->assertSame($expected, $rational->toDecimalExact());
    }

    /**
     * @return iterable<string, array{Rational, string}>
     */
    public static function exactDecimalProvider(): iterable
    {
        yield 'zero' => [new Rational(0), '0'];
        yield 'integer' => [new Rational(42), '42'];
        yield 'negative integer' => [new Rational(-42), '-42'];
        yield 'half' => [new Rational(1, 2), '0.5'];
        yield 'negative eighth' => [new Rational(-1, 8), '-0.125'];
        yield 'mixed factors' => [new Rational(1, 40), '0.025'];
        yield 'improper fraction' => [new Rational(3, 2), '1.5'];
        yield 'normalized trailing zero' => [new Rational(50, 100), '0.5'];
        yield 'large exact decimal' => [
            new Rational(gmp_init('123456789012345678901'), gmp_pow(10, 20)),
            '1.23456789012345678901',
        ];
    }

    public function testExactDecimalRejectsNonTerminatingRepresentation(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('does not have a terminating decimal representation');

        (new Rational(1, 3))->toDecimalExact();
    }

    #[DataProvider('floatProvider')]
    public function testConvertsToNearestFloat(Rational $rational, float $expected): void
    {
        $this->assertSame($expected, $rational->toFloat());
    }

    /**
     * @return iterable<string, array{Rational, float}>
     */
    public static function floatProvider(): iterable
    {
        $largePower = gmp_pow(2, 2000);
        $maximumFiniteNumerator = gmp_mul(gmp_sub(gmp_pow(2, 53), 1), gmp_pow(2, 971));
        $overflowMidpoint = gmp_mul(gmp_sub(gmp_pow(2, 54), 1), gmp_pow(2, 970));

        yield 'zero' => [new Rational(0), 0.0];
        yield 'positive exact' => [new Rational(3, 2), 1.5];
        yield 'negative exact' => [new Rational(-3, 2), -1.5];
        yield 'balanced operands beyond float range' => [
            new Rational(gmp_add($largePower, 1), $largePower),
            1.0,
        ];
        yield 'normal tie rounds down to even' => [
            new Rational(gmp_add(gmp_pow(2, 53), 1), gmp_pow(2, 53)),
            1.0,
        ];
        yield 'normal tie rounds up to even' => [
            new Rational(gmp_add(gmp_pow(2, 53), 3), gmp_pow(2, 53)),
            1.0 + (2.0 ** -51),
        ];
        yield 'maximum finite' => [new Rational($maximumFiniteNumerator), PHP_FLOAT_MAX];
        yield 'below overflow midpoint' => [new Rational(gmp_sub($overflowMidpoint, 1)), PHP_FLOAT_MAX];
        yield 'minimum normal' => [new Rational(1, gmp_pow(2, 1022)), 2.0 ** -1022];
        yield 'minimum subnormal' => [new Rational(1, gmp_pow(2, 1074)), 2.0 ** -1074];
        yield 'subnormal rounds up' => [new Rational(3, gmp_pow(2, 1076)), 2.0 ** -1074];
    }

    public function testFloatConversionRejectsOverflow(): void
    {
        $this->expectException(\OverflowException::class);

        (new Rational(gmp_pow(2, 1024)))->toFloat();
    }

    public function testFloatConversionRejectsValueAtOverflowMidpoint(): void
    {
        $midpoint = gmp_mul(gmp_sub(gmp_pow(2, 54), 1), gmp_pow(2, 970));

        $this->expectException(\OverflowException::class);

        (new Rational($midpoint))->toFloat();
    }

    public function testFloatConversionRejectsUnderflowToZero(): void
    {
        $this->expectException(\UnderflowException::class);

        (new Rational(1, gmp_pow(2, 1075)))->toFloat();
    }
}
