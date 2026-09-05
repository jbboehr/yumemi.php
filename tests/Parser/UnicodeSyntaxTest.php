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
use jbboehr\Yumemi\Units;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class UnicodeSyntaxTest extends TestCase
{
    #[DataProvider('unicodeExpressionProvider')]
    public function testParsesUnicodeMultiplicationAndSuperscriptPowers(string $input, string $asciiEquivalent): void
    {
        $units = Units::default();

        $this->assertSame(
            $units->parse($asciiEquivalent)->toString(),
            $units->parse($input)->toString(),
        );
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function unicodeExpressionProvider(): iterable
    {
        yield 'middle dot' => ['meter · second', 'meter * second'];
        yield 'positive power' => ['meter²', 'meter^2'];
        yield 'explicit positive power' => ['meter⁺²', 'meter^2'];
        yield 'negative power' => ['second⁻²', 'second^-2'];
        yield 'parenthesized power' => ['(meter / second)²', '(meter / second)^2'];
        yield 'implicit multiplication after power' => ['meter² second', 'meter^2 * second'];
        yield 'power in implicit sequence' => ['meter second²', 'meter * second^2'];
        yield 'zero power' => ['meter⁰', 'meter^0'];
    }

    public function testLexerPreservesRawSuperscriptTokenAndByteSpan(): void
    {
        $lexer = new Lexer('m²');

        $this->assertSame(Lexer::T_IDENTIFIER, $lexer->yylex());
        $this->assertSame('m', $lexer->getLVal());
        $this->assertSame(0, $lexer->getStartPos());
        $this->assertSame(1, $lexer->getEndPos());

        $this->assertSame(Lexer::T_SUPERSCRIPT_INTEGER, $lexer->yylex());
        $this->assertSame('²', $lexer->getLVal());
        $this->assertSame(1, $lexer->getStartPos());
        $this->assertSame(3, $lexer->getEndPos());
    }

    public function testSyntaxErrorAfterMiddleDotRetainsMultibyteByteOffset(): void
    {
        try {
            Parser::parseString('m · / s');
            self::fail('Expected malformed unit expression to fail.');
        } catch (ParseException $exception) {
            $span = $exception->getSpan();

            $this->assertNotNull($span);
            $this->assertSame(5, $span->start);
            $this->assertSame(6, $span->end);
            $this->assertStringContainsString('line 1, column 5 (byte offset 5)', $exception->getMessage());
            $this->assertStringContainsString("| m · / s\n|     ^", $exception->getMessage());
        }
    }

    public function testRejectsSuperscriptSignWithoutDigitsAtExactSpan(): void
    {
        try {
            Parser::parseString('meter⁻');
            self::fail('Expected malformed superscript power to fail.');
        } catch (ParseException $exception) {
            $span = $exception->getSpan();

            $this->assertNotNull($span);
            $this->assertSame(5, $span->start);
            $this->assertSame(8, $span->end);
            $this->assertStringContainsString(
                "Syntax error, unexpected 'superscript sign without digits' "
                    . 'at line 1, column 6 (byte offset 5)',
                $exception->getMessage(),
            );
            $this->assertStringContainsString("| meter⁻\n|      ^", $exception->getMessage());
        }
    }
}
