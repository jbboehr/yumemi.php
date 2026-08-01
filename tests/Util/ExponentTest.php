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

namespace jbboehr\Yumemi\Tests\Util;

use jbboehr\Yumemi\Exception\InvalidArgumentException;
use jbboehr\Yumemi\Exception\OverflowException;
use jbboehr\Yumemi\Util\Exponent;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ExponentTest extends TestCase
{
    #[DataProvider('validExponentProvider')]
    public function testParsesBoundedIntegerStrings(string $input, int $expected): void
    {
        $this->assertSame($expected, Exponent::fromString($input));
    }

    /** @return iterable<string, array{string, int}> */
    public static function validExponentProvider(): iterable
    {
        yield 'zero' => ['0', 0];
        yield 'positive sign' => ['+12', 12];
        yield 'negative' => ['-12', -12];
        yield 'upper boundary' => ['10000', 10_000];
        yield 'lower boundary' => ['-10000', -10_000];
    }

    #[DataProvider('outOfRangeProvider')]
    public function testRejectsOutOfRangeValues(string $input): void
    {
        $this->expectException(OverflowException::class);
        $this->expectExceptionMessage('supported range of -10000 through 10000');

        Exponent::fromString($input);
    }

    /** @return iterable<string, array{string}> */
    public static function outOfRangeProvider(): iterable
    {
        yield 'above boundary' => ['10001'];
        yield 'below boundary' => ['-10001'];
        yield 'far beyond native integer range' => [str_repeat('9', 40)];
    }

    public function testRejectsMalformedIntegerString(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Exponent::fromString('--2');
    }

    public function testChecksArithmeticWithoutNativeIntegerOverflow(): void
    {
        $this->assertSame(10_000, Exponent::add(9_999, 1));
        $this->assertSame(-10_000, Exponent::subtract(-9_999, 1));
        $this->assertSame(10_000, Exponent::multiply(100, 100));

        $this->expectException(OverflowException::class);
        Exponent::multiply(101, 100);
    }
}
