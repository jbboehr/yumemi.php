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

use jbboehr\Yumemi\Exception\InvalidArgumentException;
use jbboehr\Yumemi\Exception\NonExactRootException;
use jbboehr\Yumemi\Exception\OverflowException;
use jbboehr\Yumemi\Units;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ExprTest extends TestCase
{
    #[DataProvider('exactRootProvider')]
    public function testTakesExactExpressionRoots(string $expression, int $degree, string $expected): void
    {
        $this->assertSame($expected, Units::default()->parse($expression)->root($degree)->toString());
    }

    /** @return iterable<string, array{string, int, string}> */
    public static function exactRootProvider(): iterable
    {
        yield 'constant and compound powers' => ['4 * meter^2 / second^4', 2, '2 * meter * second ^ -2'];
        yield 'negative power' => ['meter^-4', 2, 'meter ^ -2'];
        yield 'degree one' => ['newton', 1, 'newton'];
        yield 'maximum degree' => ['1', 10_000, '1'];
    }

    public function testRejectsNonExactConstantRoot(): void
    {
        $this->expectException(NonExactRootException::class);

        Units::default()->parse('2 * meter^2')->root(2);
    }

    public function testRootsPrefixCompositionResolvedDuringParsing(): void
    {
        $expression = Units::default()->parse('kilometer * millimeter');

        $this->assertSame('meter', $expression->root(2)->toString());
    }

    public function testRejectsNonExactSymbolicRootWithoutSubstitution(): void
    {
        $expression = Units::default()->quantity(1, 'kilometer * millimeter')->unit();

        $this->expectException(NonExactRootException::class);
        $expression->root(2);
    }

    #[DataProvider('invalidDegreeProvider')]
    public function testRejectsNonPositiveRootDegree(int $degree): void
    {
        $this->expectException(InvalidArgumentException::class);

        Units::default()->parse('1')->root($degree);
    }

    /** @return iterable<string, array{int}> */
    public static function invalidDegreeProvider(): iterable
    {
        yield 'zero' => [0];
        yield 'negative' => [-1];
    }

    public function testRejectsRootDegreeAboveSupportedLimit(): void
    {
        $this->expectException(OverflowException::class);

        Units::default()->parse('1')->root(10_001);
    }
}
