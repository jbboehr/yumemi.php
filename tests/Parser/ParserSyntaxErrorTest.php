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

use jbboehr\Yumemi\Parser\Lexer;
use jbboehr\Yumemi\Parser\ParseException;
use jbboehr\Yumemi\Parser\Parser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ParserSyntaxErrorTest extends TestCase
{
    public function testReportsAnUnformattedErrorWhenNoLocationIsAvailable(): void
    {
        $lexer = new Lexer('meter');

        try {
            $lexer->yyerror(null, 'Raw parser failure.');
            self::fail('Expected the lexer error handler to throw.');
        } catch (ParseException $exception) {
            $this->assertSame('Raw parser failure.', $exception->getMessage());
            $this->assertSame(0, $exception->getCode());
            $this->assertNull($exception->getSpan());
            $this->assertSame('meter', $exception->getSource());
        }
    }

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
            $this->assertSame(0, $exception->getCode());
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
        yield 'malformed decimal' => [
            'meter * 1.2.3',
            8,
            13,
            "Syntax error, unexpected 'malformed number' "
                . "at line 1, column 9 (byte offset 8).\n"
                . "| meter * 1.2.3\n"
                . '|         ^~~~~',
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
            'Syntax error, got ), but expecting integer or decimal number or - '
                . "at line 1, column 9 (byte offset 8).\n"
                . "| meter @ )\n"
                . '|         ^',
        ];
        yield 'multi-byte-range token underline' => [
            'meter @ second',
            8,
            14,
            'Syntax error, got identifier, but expecting integer or decimal number or - '
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
        yield 'carriage return is omitted from display' => [
            "meter * /\rsecond",
            8,
            9,
            "Syntax error, unexpected '/' at line 1, column 9 (byte offset 8).\n"
                . "| meter * /second\n"
                . '|         ^',
        ];
        yield 'only the erroneous line is displayed' => [
            "meter\nsecond * / kilogram\nampere",
            15,
            16,
            "Syntax error, unexpected '/' at line 2, column 10 (byte offset 15).\n"
                . "| second * / kilogram\n"
                . '|          ^',
        ];
    }

    public function testTruncatesLongSourceExcerptBeforeError(): void
    {
        $source = str_repeat('meter * ', 30) . '/ second';

        try {
            Parser::parseString($source);
            self::fail('Expected malformed unit expression to fail.');
        } catch (ParseException $exception) {
            $lines = explode("\n", $exception->getMessage());

            $this->assertCount(3, $lines);
            $this->assertSame('| ...' . substr($source, -117), $lines[1]);
            $this->assertSame('| ' . str_repeat(' ', 112) . '^', $lines[2]);
        }
    }

    public function testTruncatesLongSourceExcerptAfterError(): void
    {
        $source = '/ ' . str_repeat('meter * ', 30) . 'second';

        try {
            Parser::parseString($source);
            self::fail('Expected malformed unit expression to fail.');
        } catch (ParseException $exception) {
            $lines = explode("\n", $exception->getMessage());

            $this->assertCount(3, $lines);
            $this->assertSame('| ' . substr($source, 0, 117) . '...', $lines[1]);
            $this->assertSame('| ^', $lines[2]);
        }
    }

    public function testTruncatesLongSourceExcerptAroundMiddleError(): void
    {
        $source = str_repeat('meter * ', 15) . '/ ' . str_repeat('second * ', 15) . 'meter';

        try {
            Parser::parseString($source);
            self::fail('Expected malformed unit expression to fail.');
        } catch (ParseException $exception) {
            $lines = explode("\n", $exception->getMessage());

            $this->assertCount(3, $lines);
            $this->assertSame('| ...' . substr($source, 63, 114) . '...', $lines[1]);
            $this->assertSame('| ' . str_repeat(' ', 60) . '^', $lines[2]);
        }
    }
}
