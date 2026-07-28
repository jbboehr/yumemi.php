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

namespace jbboehr\Yumemi\Tests\Parser;

use jbboehr\Yumemi\Parser\ParseException;
use jbboehr\Yumemi\Parser\Parser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ParserSyntaxErrorTest extends TestCase
{
    #[DataProvider('syntaxErrorProvider')]
    public function testReportsExactSyntaxErrorSpan(
        string $source,
        int $expectedStart,
        int $expectedEnd,
        string $expectedMessage,
    ): void {
        try {
            Parser::parseString($source);
            self::fail('Expected malformed unit expression to fail.');
        } catch (ParseException $exception) {
            $span = $exception->getSpan();

            $this->assertNotNull($span);
            $this->assertSame($expectedStart, $span->start);
            $this->assertSame($expectedEnd, $span->end);
            $this->assertSame($source, $exception->getSource());
            $this->assertSame($expectedMessage, $exception->getMessage());
        }
    }

    /**
     * @return iterable<string, array{string, int, int, string}>
     */
    public static function syntaxErrorProvider(): iterable
    {
        yield 'unexpected token' => [
            'meter * / second',
            8,
            9,
            "Syntax error, unexpected '/' at line 1, column 9 (byte offset 8).\n"
                . "| meter * / second\n"
                . '|         ^',
        ];
        yield 'end of input' => [
            'meter /',
            7,
            7,
            "Syntax error, unexpected 'end of file' at line 1, column 8 (byte offset 7).\n"
                . "| meter /\n"
                . '|        ^',
        ];
        yield 'expected token names' => [
            'meter @ )',
            8,
            9,
            'Syntax error, got ), but expecting integer or decimal number '
                . "at line 1, column 9 (byte offset 8).\n"
                . "| meter @ )\n"
                . '|         ^',
        ];
        yield 'multi-byte-range token underline' => [
            'meter @ second',
            8,
            14,
            'Syntax error, got identifier, but expecting integer or decimal number '
                . "at line 1, column 9 (byte offset 8).\n"
                . "| meter @ second\n"
                . '|         ^~~~~~',
        ];
        yield 'multibyte prefix uses byte span and character column' => [
            '° * / second',
            5,
            6,
            "Syntax error, unexpected '/' at line 1, column 5 (byte offset 5).\n"
                . "| ° * / second\n"
                . '|     ^',
        ];
        yield 'tabs expand for display' => [
            "meter\t*\t/ second",
            8,
            9,
            "Syntax error, unexpected '/' at line 1, column 13 (byte offset 8).\n"
                . "| meter   *   / second\n"
                . '|             ^',
        ];
        yield 'multiline expression' => [
            "meter *\n/ second",
            8,
            9,
            "Syntax error, unexpected '/' at line 2, column 1 (byte offset 8).\n"
                . "| / second\n"
                . '| ^',
        ];
    }

    public function testTruncatesLongSourceExcerptAroundError(): void
    {
        $source = str_repeat('meter * ', 30) . '/ second';

        try {
            Parser::parseString($source);
            self::fail('Expected malformed unit expression to fail.');
        } catch (ParseException $exception) {
            $lines = explode("\n", $exception->getMessage());

            $this->assertCount(3, $lines);
            $this->assertLessThanOrEqual(122, strlen($lines[1]));
            $this->assertStringStartsWith('| ...', $lines[1]);
            $this->assertStringNotContainsString('...', substr($lines[1], 5));
            $this->assertStringEndsWith('^', $lines[2]);
        }
    }
}
