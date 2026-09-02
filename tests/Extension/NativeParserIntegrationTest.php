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
        self::assertTrue(\jbboehr\Yumemi\Parser\NativeParser::supports(1));
        self::assertFalse(\jbboehr\Yumemi\Parser\NativeParser::supports(0));
        self::assertTrue(\jbboehr\Yumemi\Parser\NativeParser::isCompatible());
    }

    public function testEnvironmentFlagCanControlRealNativeSelection(): void
    {
        $previous = getenv('YUMEMI_NATIVE_PARSER');

        try {
            foreach (['0', 'false', 'FALSE', 'off', 'no', '', 'invalid'] as $value) {
                putenv('YUMEMI_NATIVE_PARSER=' . $value);
                self::assertFalse(
                    NativeParserAdapter::isAvailable(),
                    sprintf('value: %s', var_export($value, true)),
                );
            }

            foreach (['1', 'true', 'TRUE', 'on', 'yes'] as $value) {
                putenv('YUMEMI_NATIVE_PARSER=' . $value);
                self::assertTrue(
                    NativeParserAdapter::isAvailable(),
                    sprintf('value: %s', var_export($value, true)),
                );
            }

            putenv('YUMEMI_NATIVE_PARSER');
            self::assertTrue(NativeParserAdapter::isAvailable(), 'unset');
        } finally {
            if ($previous === false) {
                putenv('YUMEMI_NATIVE_PARSER');
            } else {
                putenv('YUMEMI_NATIVE_PARSER=' . $previous);
            }
        }
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

    public function testDeterministicAdversarialCorpusMatchesThePhpParser(): void
    {
        $cases = 0;

        foreach (self::adversarialExpressionCorpus() as $label => $input) {
            $message = sprintf('%s; bytes=%s', $label, bin2hex($input));
            self::assertAstSame(
                self::parseWithPhpBackend($input),
                NativeParserAdapter::parse($input),
                $message,
            );
            ++$cases;
        }

        self::assertGreaterThanOrEqual(200, $cases, 'The differential corpus unexpectedly lost breadth.');
    }

    public function testDeterministicInvalidCorpusMatchesThePhpParser(): void
    {
        $cases = 0;

        foreach (self::adversarialInvalidExpressionCorpus() as $label => $input) {
            $message = sprintf('%s; bytes=%s', $label, bin2hex($input));
            $php = self::parseFailureWithPhpBackend($input);

            try {
                NativeParserAdapter::parse($input);
                self::fail('Expected malformed input to fail through the native backend. ' . $message);
            } catch (ParseException $native) {
                self::assertSame($php->getMessage(), $native->getMessage(), $message);
                self::assertSame($php->source, $native->source, $message);
                self::assertEquals($php->span, $native->span, $message);
            }

            ++$cases;
        }

        self::assertGreaterThanOrEqual(100, $cases, 'The invalid differential corpus unexpectedly lost breadth.');
    }

    /** @return iterable<string, string> */
    private static function adversarialExpressionCorpus(): iterable
    {
        $atoms = [
            'meter',
            'μs',
            '°C',
            'a_b',
            '𐐀',
            '0001',
            '1.25',
            '1e-3',
            "a\0b",
            '中文',
        ];
        $operators = [' + ', ' - ', ' * ', ' / ', '^', ' ', '.', ' · '];
        $atomCount = count($atoms);

        foreach ($atoms as $index => $atom) {
            yield sprintf('atom-%02d', $index) => $atom;
            yield sprintf('grouped-atom-%02d', $index) => '(' . $atom . ')';
            yield sprintf('double-negated-atom-%02d', $index) => '--(' . $atom . ')';

            if ($index < 5) {
                yield sprintf('offset-atom-%02d', $index) => sprintf(
                    '%s @ %s',
                    $atom,
                    $index % 2 === 0 ? '-273.15' : '0',
                );
            }
        }

        foreach ($operators as $operatorIndex => $operator) {
            foreach ($atoms as $atomIndex => $left) {
                $right = $atoms[($atomIndex + $operatorIndex + 1) % $atomCount];

                yield sprintf('binary-%02d-%02d', $operatorIndex, $atomIndex) => $left . $operator . $right;
                yield sprintf('grouped-binary-%02d-%02d', $operatorIndex, $atomIndex) => sprintf(
                    '(%s%s%s)',
                    $left,
                    $operator,
                    $right,
                );
            }
        }

        foreach ($atoms as $index => $left) {
            $middle = $atoms[($index + 3) % $atomCount];
            $right = $atoms[($index + 7) % $atomCount];

            yield sprintf('left-product-%02d', $index) => sprintf('%s / %s * %s', $left, $middle, $right);
            yield sprintf('right-power-%02d', $index) => sprintf('%s^%s^%s', $left, $middle, $right);
            yield sprintf('negated-power-%02d', $index) => sprintf('-%s^%s', $left, $right);
        }
    }

    /** @return iterable<string, string> */
    private static function adversarialInvalidExpressionCorpus(): iterable
    {
        $atoms = ['meter', 'μs', '°C', 'a_b', '𐐀', '0001', '1.25', '1e-3', "a\0b", "a\xffb"];
        $templates = [
            'unclosed-group' => static fn (string $atom): string => '(' . $atom,
            'unopened-group' => static fn (string $atom): string => $atom . ')',
            'missing-addend' => static fn (string $atom): string => $atom . ' +',
            'missing-factor' => static fn (string $atom): string => $atom . ' *',
            'missing-divisor' => static fn (string $atom): string => $atom . ' /',
            'missing-exponent' => static fn (string $atom): string => $atom . ' ^',
            'missing-offset' => static fn (string $atom): string => $atom . ' @',
            'nonnumeric-offset' => static fn (string $atom): string => $atom . ' @ meter',
            'adjacent-operators' => static fn (string $atom): string => $atom . ' * / second',
            'operator-before-close' => static fn (string $atom): string => '(' . $atom . ' + )',
        ];

        foreach ($atoms as $atomIndex => $atom) {
            foreach ($templates as $templateName => $template) {
                yield sprintf('%s-%02d', $templateName, $atomIndex) => $template($atom);
            }
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

    private static function assertAstSame(Ast $expected, Ast $actual, string $message = ''): void
    {
        self::assertSame($expected::class, $actual::class, $message);
        self::assertSame($expected->toString(), $actual->toString(), $message);

        if ($expected instanceof AstNode && $actual instanceof AstNode) {
            self::assertEquals($expected->span, $actual->span, $message);
        }

        if ($expected instanceof Ast\Add || $expected instanceof Ast\Sub || $expected instanceof Ast\Mul
            || $expected instanceof Ast\Div || $expected instanceof Ast\Pow || $expected instanceof Ast\At
        ) {
            self::assertInstanceOf($expected::class, $actual, $message);
            self::assertAstSame($expected->left, $actual->left, $message);
            self::assertAstSame($expected->right, $actual->right, $message);
        }
    }
}
