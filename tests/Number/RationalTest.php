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

use jbboehr\Yumemi\Number\Rational;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RationalTest extends TestCase
{
    public function testAddsRationals(): void
    {
        $this->assertSame('5/6', (new Rational(1, 2))->add(new Rational(1, 3))->toString());
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
}
