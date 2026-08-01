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

use jbboehr\Yumemi\Number\BinaryFloat;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class BinaryFloatTest extends TestCase
{
    #[DataProvider('finiteFloatProvider')]
    public function testConvertsFiniteBinaryFloatsExactly(float $value): void
    {
        $this->assertSame($value, BinaryFloat::toRational($value)->toFloat());
    }

    /** @return iterable<string, array{float}> */
    public static function finiteFloatProvider(): iterable
    {
        yield 'positive zero' => [0.0];
        yield 'negative zero' => [-0.0];
        yield 'integer' => [42.0];
        yield 'exact fraction' => [1.5];
        yield 'inexact decimal' => [0.1];
        yield 'negative' => [-123.456];
        yield 'smallest normal' => [PHP_FLOAT_MIN];
        yield 'smallest subnormal' => [2.0 ** -1074];
        yield 'largest finite' => [PHP_FLOAT_MAX];
    }

    public function testExposesExactBinaryFraction(): void
    {
        $this->assertSame('3/2', BinaryFloat::toRational(1.5)->toString());
        $this->assertSame(
            '3602879701896397/36028797018963968',
            BinaryFloat::toRational(0.1)->toString(),
        );
    }

    #[DataProvider('nonFiniteFloatProvider')]
    public function testRejectsNonFiniteValues(float $value): void
    {
        $this->expectException(\InvalidArgumentException::class);

        BinaryFloat::toRational($value);
    }

    /** @return iterable<string, array{float}> */
    public static function nonFiniteFloatProvider(): iterable
    {
        yield 'positive infinity' => [INF];
        yield 'negative infinity' => [-INF];
        yield 'not a number' => [NAN];
    }
}
