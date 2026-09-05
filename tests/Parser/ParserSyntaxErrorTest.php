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
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

final class ParserSyntaxErrorTest extends TestCase
{
    #[DataProvider('invalidUtf8Provider')]
    public function testRejectsInvalidUtf8BeforeTokenization(string $source, int $expectedOffset): void
    {
        try {
            Parser::parseString($source);
            self::fail('Expected malformed UTF-8 to be rejected.');
        } catch (ParseException $exception) {
            self::assertSame($source, $exception->source);
            self::assertNotNull($exception->span);
            self::assertSame($expectedOffset, $exception->span->start);
            self::assertSame($expectedOffset + 1, $exception->span->end);
            self::assertStringStartsWith('Unit expression must be valid UTF-8', $exception->getMessage());
        }
    }

    /** @return iterable<string, array{string, int}> */
    public static function invalidUtf8Provider(): iterable
    {
        yield 'lone high byte' => ["\xff", 0];
        yield 'overlong encoding' => ["meter\xc0\xaf", 5];
        yield 'truncated sequence' => ["meter\xe2\x82", 5];
        yield 'code point above Unicode range' => ["meter\xf4\x90\x80\x80", 5];
    }

    #[RunInSeparateProcess]
    public function testRejectsInvalidUtf8IndependentlyOfThePcreBudgets(): void
    {
        $previousJit = ini_set('pcre.jit', '0');
        $previousBacktrackLimit = ini_set('pcre.backtrack_limit', '1000');
        $previousRecursionLimit = ini_set('pcre.recursion_limit', '1000');
        $source = "αβ\xff";

        try {
            self::assertNotFalse($previousJit);
            self::assertNotFalse($previousBacktrackLimit);
            self::assertNotFalse($previousRecursionLimit);
            self::assertSame('0', ini_get('pcre.jit'));
            self::assertSame('1000', ini_get('pcre.backtrack_limit'));
            self::assertSame('1000', ini_get('pcre.recursion_limit'));

            $baseline = $this->parseFailure($source);
            self::assertNotNull($baseline->span);
            self::assertSame(4, $baseline->span->start);
            self::assertSame(5, $baseline->span->end);
            self::assertStringContainsString('column 3 (byte offset 4)', $baseline->getMessage());

            foreach (['pcre.backtrack_limit', 'pcre.recursion_limit'] as $setting) {
                self::assertNotFalse(ini_set($setting, '1'));
                self::assertSame('1', ini_get($setting));

                $restricted = $this->parseFailure($source);
                self::assertSame($baseline->getMessage(), $restricted->getMessage());
                self::assertEquals($baseline->span, $restricted->span);

                self::assertNotFalse(ini_set($setting, '1000'));
            }
        } finally {
            if ($previousRecursionLimit !== false) {
                ini_set('pcre.recursion_limit', $previousRecursionLimit);
            }

            if ($previousBacktrackLimit !== false) {
                ini_set('pcre.backtrack_limit', $previousBacktrackLimit);
            }

            if ($previousJit !== false) {
                ini_set('pcre.jit', $previousJit);
            }
        }
    }

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

    public function testRepeatedFailuresProduceFreshDiagnostics(): void
    {
        $first = $this->parseFailure('cache_failure_meter /');
        $second = $this->parseFailure('cache_failure_meter /');

        $this->assertNotSame($first, $second);
        $this->assertEquals($first->getSpan(), $second->getSpan());
        $this->assertSame($first->getSource(), $second->getSource());
        $this->assertSame($first->getMessage(), $second->getMessage());
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
        yield 'unclosed group retains arithmetic continuations' => [
            '(meter',
            6,
            6,
            "Syntax error, unexpected 'end of file' at line 1, column 7 (byte offset 6).\n"
                . "| (meter\n"
                . '|       ^',
        ];
        yield 'unexpected close retains arithmetic continuations' => [
            'meter )',
            6,
            7,
            "Syntax error, unexpected ')' at line 1, column 7 (byte offset 6).\n"
                . "| meter )\n"
                . '|       ^',
        ];
        yield 'invalid superscript retains arithmetic continuations' => [
            'meter⁻',
            5,
            8,
            "Syntax error, unexpected 'superscript sign without digits' at line 1, column 6 (byte offset 5).\n"
                . "| meter⁻\n"
                . '|      ^',
        ];
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

    private function parseFailure(string $source): ParseException
    {
        try {
            Parser::parseString($source);
            self::fail('Expected malformed unit expression to fail.');
        } catch (ParseException $exception) {
            return $exception;
        }
    }
}
