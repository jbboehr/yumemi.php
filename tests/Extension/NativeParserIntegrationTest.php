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

namespace jbboehr\Yumemi\Tests\Extension;

use jbboehr\Yumemi\Parser\Ast;
use jbboehr\Yumemi\Parser\AstNode;
use jbboehr\Yumemi\Parser\ExpressionLimitExceededException;
use jbboehr\Yumemi\Parser\Lexer;
use jbboehr\Yumemi\Parser\NativeParserAdapter;
use jbboehr\Yumemi\Parser\ParseException;
use jbboehr\Yumemi\Parser\Parser;
use jbboehr\Yumemi\Parser\SourceSpan;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class NativeParserIntegrationTest extends TestCase
{
    public function testRecognizesTheRealExtensionAbi(): void
    {
        self::assertTrue(extension_loaded('yumemi'));
        self::assertTrue(NativeParserAdapter::isAvailable());
        self::assertSame(1, \jbboehr\Yumemi\Parser\NativeParser::ABI_VERSION);
        self::assertTrue(\jbboehr\Yumemi\Parser\NativeParser::isCompatible());
    }

    public function testEnvironmentFlagCanDisableRealNativeSelection(): void
    {
        $previous = getenv('YUMEMI_NATIVE_PARSER');

        try {
            putenv('YUMEMI_NATIVE_PARSER=0');

            self::assertFalse(NativeParserAdapter::isAvailable());
            self::assertAstSame(
                self::parseWithPhpBackend('real_native_flag_fallback_probe'),
                Parser::parseString('real_native_flag_fallback_probe'),
            );
        } finally {
            if ($previous === false) {
                putenv('YUMEMI_NATIVE_PARSER');
            } else {
                putenv('YUMEMI_NATIVE_PARSER=' . $previous);
            }
        }

        self::assertTrue(NativeParserAdapter::isAvailable());
    }

    #[DataProvider('expressionProvider')]
    public function testNativeBackendMatchesThePhpParser(string $input): void
    {
        $php = self::parseWithPhpBackend($input);
        $native = Parser::parseString($input);

        self::assertAstSame($php, $native);
        self::assertAstSame($native, NativeParserAdapter::parse($input));
    }

    /** @return iterable<string, array{string}> */
    public static function expressionProvider(): iterable
    {
        yield 'identifier' => ['meter'];
        yield 'all binary operators' => ['2 + 1.5 - kelvin @ -273.15 * meter / second^2'];
        yield 'dot multiplication' => ['meter.second'];
        yield 'implicit multiplication' => ['meter second kilogram'];
        yield 'left-associative multiplication and division with adjacency' => ['meter / second * kilogram ampere'];
        yield 'parentheses and repeated negation' => ['--((meter / μs)^-2)'];
        yield 'unicode multiplication and superscript' => ['meter · second⁻²'];
        yield 'unicode identifier adjacency' => ['αβ γδ'];
        yield 'exact numeric and identifier lexemes' => ['0001.2300e+04 foo_02'];
        yield 'synthetic negative-one leaf' => ['-meter'];
    }

    #[DataProvider('syntaxErrorProvider')]
    public function testNativeSyntaxErrorsMatchThePhpParser(string $input): void
    {
        $php = self::parseFailureWithPhpBackend($input);

        try {
            Parser::parseString($input);
            self::fail('Expected malformed input to fail through the native backend.');
        } catch (ParseException $native) {
            self::assertSame($php->getMessage(), $native->getMessage());
            self::assertSame($php->source, $native->source);
            self::assertEquals($php->span, $native->span);
        }
    }

    /** @return iterable<string, array{string}> */
    public static function syntaxErrorProvider(): iterable
    {
        yield 'unexpected token' => ['meter * / second'];
        yield 'malformed decimal' => ['meter * 1.2.3'];
        yield 'end of input' => ['meter /'];
        yield 'short expected list' => ['meter @ )'];
        yield 'unexpected identifier' => ['meter @ second'];
        yield 'unicode byte offset' => ['° * / second'];
        yield 'invalid superscript' => ['meter⁻'];
        yield 'group end of input' => ['(meter'];
        yield 'multiline input' => ["meter *\n/ second"];
    }

    #[DataProvider('limitProvider')]
    public function testNativeLimitsMatchThePhpLexer(
        string $input,
        string $limit,
        int $maximum,
        int $observed,
        ?SourceSpan $span,
    ): void {
        $php = self::parseLimitFailureWithPhpBackend($input);

        try {
            Parser::parseString($input);
            self::fail('Expected the parser resource limit to be exceeded.');
        } catch (ExpressionLimitExceededException $native) {
            self::assertSame($php->getMessage(), $native->getMessage());
            self::assertSame($php->limit, $native->limit);
            self::assertSame($php->maximum, $native->maximum);
            self::assertSame($php->observed, $native->observed);
            self::assertEquals($php->span, $native->span);
            self::assertSame($limit, $native->limit);
            self::assertSame($maximum, $native->maximum);
            self::assertSame($observed, $native->observed);
            self::assertEquals($span, $native->span);
        }
    }

    /** @return iterable<string, array{string, string, int, int, SourceSpan|null}> */
    public static function limitProvider(): iterable
    {
        yield 'input bytes' => [str_repeat('a', 4097), 'input-bytes', 4096, 4097, null];
        yield 'token count' => [
            implode(' ', array_fill(0, 257, 'a')),
            'token-count',
            256,
            257,
            new SourceSpan(512, 513),
        ];
        yield 'nesting depth' => [
            str_repeat('(', 65) . 'a' . str_repeat(')', 65),
            'nesting-depth',
            64,
            65,
            new SourceSpan(64, 65),
        ];
        yield 'token bytes' => [str_repeat('α', 513), 'token-bytes', 1024, 1026, new SourceSpan(0, 1026)];
    }

    public function testAutomaticNativeResultsRetainTheExistingCacheContract(): void
    {
        $input = 'native_cache_contract_meter / second';

        $first = Parser::parseString($input);

        self::assertSame($first, Parser::parseString($input));
        self::assertNotSame($first, NativeParserAdapter::parse($input));
    }

    public function testDirectNativeInputLimitRetainsThePublicNullSpanPolicy(): void
    {
        try {
            NativeParserAdapter::parse(str_repeat('a', 4097));
            self::fail('Expected the native input byte limit to be translated.');
        } catch (ExpressionLimitExceededException $exception) {
            self::assertSame('input-bytes', $exception->limit);
            self::assertSame(4096, $exception->maximum);
            self::assertSame(4097, $exception->observed);
            self::assertNull($exception->span);
        }
    }

    private static function parseWithPhpBackend(string $input): Ast
    {
        Lexer::assertInputLength($input);
        $parser = new Parser(new Lexer($input));
        self::assertTrue($parser->parse());

        return $parser->getAst();
    }

    private static function parseFailureWithPhpBackend(string $input): ParseException
    {
        try {
            self::parseWithPhpBackend($input);
            self::fail('Expected malformed input to fail through the PHP backend.');
        } catch (ParseException $exception) {
            return $exception;
        }
    }

    private static function parseLimitFailureWithPhpBackend(string $input): ExpressionLimitExceededException
    {
        try {
            self::parseWithPhpBackend($input);
            self::fail('Expected a parser resource limit failure through the PHP backend.');
        } catch (ExpressionLimitExceededException $exception) {
            return $exception;
        }
    }

    private static function assertAstSame(Ast $expected, Ast $actual): void
    {
        self::assertSame($expected::class, $actual::class);
        self::assertSame($expected->toString(), $actual->toString());

        if ($expected instanceof AstNode && $actual instanceof AstNode) {
            self::assertEquals($expected->span, $actual->span);
        }

        if ($expected instanceof Ast\Add || $expected instanceof Ast\Sub || $expected instanceof Ast\Mul
            || $expected instanceof Ast\Div || $expected instanceof Ast\Pow || $expected instanceof Ast\At
        ) {
            self::assertInstanceOf($expected::class, $actual);
            self::assertAstSame($expected->left, $actual->left);
            self::assertAstSame($expected->right, $actual->right);
        }
    }
}
