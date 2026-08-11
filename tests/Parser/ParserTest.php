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

use jbboehr\Yumemi\Parser\Ast;
use jbboehr\Yumemi\Parser\AstNode;
use jbboehr\Yumemi\Parser\ExpressionLimitExceededException;
use jbboehr\Yumemi\Parser\Lexer;
use jbboehr\Yumemi\Parser\Parser;
use jbboehr\Yumemi\Units;
use PHPUnit\Framework\TestCase;

final class ParserTest extends TestCase
{
    public function testIdentifier(): void
    {
        $this->assertAstEquals(new Ast\Identifier('meter'), Parser::parseString('meter'));
    }

    public function testExplicitMultiplication(): void
    {
        $this->assertAstEquals(
            new Ast\Mul(
                new Ast\Identifier('meter'),
                new Ast\Identifier('second'),
            ),
            Parser::parseString('meter * second'),
        );
    }

    public function testDotMultiplication(): void
    {
        $expected = new Ast\Mul(
            new Ast\Identifier('meter'),
            new Ast\Identifier('second'),
        );

        $this->assertAstEquals($expected, Parser::parseString('meter.second'));
        $this->assertAstEquals($expected, Parser::parseString('meter · second'));
    }

    public function testImplicitMultiplication(): void
    {
        $this->assertAstEquals(
            new Ast\Mul(
                new Ast\Identifier('meter'),
                new Ast\Identifier('second'),
            ),
            Parser::parseString('meter second'),
        );
    }

    public function testDivision(): void
    {
        $this->assertAstEquals(
            new Ast\Div(
                new Ast\Identifier('meter'),
                new Ast\Identifier('second'),
            ),
            Parser::parseString('meter / second'),
        );
    }

    public function testExplicitMultiplicationAndDivisionAssociateLeft(): void
    {
        $this->assertAstEquals(
            new Ast\Mul(
                new Ast\Div(
                    new Ast\Identifier('meter'),
                    new Ast\Identifier('second'),
                ),
                new Ast\Identifier('kilogram'),
            ),
            Parser::parseString('meter / second * kilogram'),
        );
    }

    public function testParenthesesOverrideMultiplicationAndDivisionPrecedence(): void
    {
        $expected = new Ast\Div(
            new Ast\Identifier('meter'),
            new Ast\Mul(
                new Ast\Identifier('second'),
                new Ast\Identifier('kilogram'),
            ),
        );

        $this->assertAstEquals($expected, Parser::parseString('meter / (second * kilogram)'));
        $this->assertAstEquals($expected, Parser::parseString('meter / (second kilogram)'));
    }

    public function testImplicitMultiplicationAndDivisionAssociateLeft(): void
    {
        $this->assertAstEquals(
            new Ast\Mul(
                new Ast\Div(
                    new Ast\Identifier('meter'),
                    new Ast\Identifier('second'),
                ),
                new Ast\Identifier('kilogram'),
            ),
            Parser::parseString('meter / second kilogram'),
        );
    }

    public function testImplicitMultiplicationBeforeDivisionAssociatesLeft(): void
    {
        $this->assertAstEquals(
            new Ast\Div(
                new Ast\Mul(
                    new Ast\Identifier('meter'),
                    new Ast\Identifier('kilogram'),
                ),
                new Ast\Identifier('second'),
            ),
            Parser::parseString('meter kilogram / second'),
        );
    }

    public function testRepeatedDivisionAssociatesLeft(): void
    {
        $this->assertAstEquals(
            new Ast\Div(
                new Ast\Div(
                    new Ast\Identifier('meter'),
                    new Ast\Identifier('second'),
                ),
                new Ast\Identifier('kilogram'),
            ),
            Parser::parseString('meter / second / kilogram'),
        );
    }

    public function testAdditionAndSubtractionRemainDistinctAstNodes(): void
    {
        $this->assertAstEquals(
            new Ast\Add(
                new Ast\Identifier('meter'),
                new Ast\Identifier('second'),
            ),
            Parser::parseString('meter + second'),
        );
        $this->assertAstEquals(
            new Ast\Sub(
                new Ast\Identifier('meter'),
                new Ast\Identifier('second'),
            ),
            Parser::parseString('meter - second'),
        );
    }

    public function testPower(): void
    {
        $this->assertAstEquals(
            new Ast\Pow(
                new Ast\Identifier('meter'),
                new Ast\Integer_('2'),
            ),
            Parser::parseString('meter^2'),
        );
    }

    public function testPowerBindsMoreTightlyThanMultiplication(): void
    {
        $expected = new Ast\Mul(
            new Ast\Identifier('meter'),
            new Ast\Pow(
                new Ast\Identifier('second'),
                new Ast\Integer_('2'),
            ),
        );

        $this->assertAstEquals($expected, Parser::parseString('meter * second^2'));
        $this->assertAstEquals($expected, Parser::parseString('meter second^2'));
    }

    public function testNegativePower(): void
    {
        $this->assertAstEquals(
            new Ast\Pow(
                new Ast\Identifier('second'),
                new Ast\Integer_('-2'),
            ),
            Parser::parseString('second^-2'),
        );
    }

    public function testRepeatedNegationTogglesNumericSign(): void
    {
        $this->assertAstEquals(
            new Ast\Pow(
                new Ast\Identifier('meter'),
                new Ast\Integer_('2'),
            ),
            Parser::parseString('meter^--2'),
        );

        $this->assertAstEquals(new Ast\Integer_('5'), Parser::parseString('--5'));
        $this->assertAstEquals(new Ast\Float_('1.25'), Parser::parseString('--1.25'));
    }

    public function testNegatesNonnumericExpressionsByMultiplyingByNegativeOne(): void
    {
        $this->assertAstEquals(
            new Ast\Mul(
                new Ast\Integer_('-1'),
                new Ast\Identifier('meter'),
            ),
            Parser::parseString('-meter'),
        );
    }

    public function testAffineOriginMayBeNegative(): void
    {
        $this->assertAstEquals(
            new Ast\At(
                new Ast\Identifier('kelvin'),
                new Ast\Float_('-273.15'),
            ),
            Parser::parseString('kelvin @ -273.15'),
        );
    }

    public function testParenthesizedPower(): void
    {
        $this->assertAstEquals(
            new Ast\Pow(
                new Ast\Div(
                    new Ast\Identifier('meter'),
                    new Ast\Identifier('second'),
                ),
                new Ast\Integer_('2'),
            ),
            Parser::parseString('(meter / second)^2'),
        );
    }

    public function testNegativeNumericBaseOfPowerIsParenthesizedForRoundTrip(): void
    {
        // A negative numeric literal base must survive the canonical round trip.
        // Exponentiation binds more tightly than the leading sign, so an unparenthesized
        // "-5 ^ 2" reparses as "-(5 ^ 2)" and silently changes meaning.
        $integerBase = Parser::parseString('(-5)^2');
        $this->assertInstanceOf(Ast\Pow::class, $integerBase);
        $this->assertInstanceOf(Ast\Integer_::class, $integerBase->left);
        $this->assertSame('((-5) ^ 2)', $integerBase->toString());
        $this->assertSame(
            $integerBase->toString(),
            Parser::parseString($integerBase->toString())->toString(),
        );

        // The same rule applies to a negative floating-point base, independently of a
        // negative exponent, which never needs added parentheses.
        $floatBase = Parser::parseString('(-5.5)^(-2)');
        $this->assertInstanceOf(Ast\Pow::class, $floatBase);
        $this->assertInstanceOf(Ast\Float_::class, $floatBase->left);
        $this->assertSame('((-5.5) ^ -2)', $floatBase->toString());
        $this->assertSame(
            $floatBase->toString(),
            Parser::parseString($floatBase->toString())->toString(),
        );
    }

    public function testParserNodesRetainHalfOpenByteSpans(): void
    {
        $ast = Parser::parseString('  meter / μs^2');

        $this->assertInstanceOf(Ast\Div::class, $ast);
        $this->assertSpan($ast, 2, 15);
        $this->assertInstanceOf(Ast\Identifier::class, $ast->left);
        $this->assertSpan($ast->left, 2, 7);
        $this->assertInstanceOf(Ast\Pow::class, $ast->right);
        $this->assertSpan($ast->right, 10, 15);
        $this->assertInstanceOf(Ast\Identifier::class, $ast->right->left);
        $this->assertSpan($ast->right->left, 10, 13);
        $this->assertInstanceOf(Ast\Integer_::class, $ast->right->right);
        $this->assertSpan($ast->right->right, 14, 15);
    }

    public function testSuccessfulParsesAreCachedByExactInput(): void
    {
        $first = Parser::parseString('cache_exact_meter / second');

        $this->assertSame($first, Parser::parseString('cache_exact_meter / second'));
        $this->assertNotSame($first, Parser::parseString(' cache_exact_meter / second '));
    }

    public function testSuccessfulParseCacheEvictsTheLeastRecentlyUsedEntry(): void
    {
        $anchor = Parser::parseString('cache_eviction_anchor');

        for ($index = 0; $index < 256; ++$index) {
            Parser::parseString('cache_eviction_' . $index);
        }

        $this->assertNotSame($anchor, Parser::parseString('cache_eviction_anchor'));
    }

    public function testSuccessfulParseCacheRefreshesRecentlyUsedEntries(): void
    {
        $anchor = Parser::parseString('cache_recency_anchor');

        for ($index = 0; $index < 255; ++$index) {
            Parser::parseString('cache_recency_' . $index);
        }

        $this->assertSame($anchor, Parser::parseString('cache_recency_anchor'));
        Parser::parseString('cache_recency_overflow');
        $this->assertSame($anchor, Parser::parseString('cache_recency_anchor'));
    }

    public function testSuccessfulParseCacheDoesNotRetainOversizedInputs(): void
    {
        $input = str_repeat('cacheableunit', 43);

        $this->assertGreaterThan(512, strlen($input));
        $this->assertNotSame(Parser::parseString($input), Parser::parseString($input));
    }

    public function testSuccessfulParseCacheRetainsInputsAtTheByteLimit(): void
    {
        $input = str_repeat('a', 512);

        $this->assertSame(512, strlen($input));
        $this->assertSame(Parser::parseString($input), Parser::parseString($input));
    }

    public function testSuccessfulParseCacheUsesByteLengthForMultibyteInput(): void
    {
        $input = 'm' . str_repeat('²', 256);

        $this->assertSame(257, mb_strlen($input));
        $this->assertGreaterThan(512, strlen($input));
        $this->assertNotSame(Parser::parseString($input), Parser::parseString($input));
    }

    public function testSuccessfulParseCacheRetainsEntriesAtTheCumulativeWeightLimit(): void
    {
        $anchorInput = str_pad('cache_weight_anchor', 512, 'a');
        $anchor = Parser::parseString($anchorInput);

        for ($index = 0; $index < 31; ++$index) {
            Parser::parseString(str_pad('cache_weight_' . $index, 512, 'a'));
        }

        $this->assertSame($anchor, Parser::parseString($anchorInput));
    }

    public function testSuccessfulParseCacheEvictsEntriesBeyondTheCumulativeWeightLimit(): void
    {
        $anchorInput = str_pad('cache_weight_overflow_anchor', 512, 'a');
        $anchor = Parser::parseString($anchorInput);

        for ($index = 0; $index < 31; ++$index) {
            Parser::parseString(str_pad('cache_weight_overflow_' . $index, 512, 'a'));
        }

        Parser::parseString(str_pad('cw_over', 16, 'a'));
        $this->assertNotSame($anchor, Parser::parseString($anchorInput));
    }

    public function testOversizedParseDoesNotEvictCachedInput(): void
    {
        $anchor = Parser::parseString('cache_oversized_anchor');

        for ($index = 0; $index < 255; ++$index) {
            Parser::parseString('cache_oversized_' . $index);
        }

        Parser::parseString(str_repeat('b', 513));
        $this->assertSame($anchor, Parser::parseString('cache_oversized_anchor'));
    }

    public function testInputByteLimitAcceptsItsBoundaryAndRejectsTheNextByte(): void
    {
        Parser::parseString('meter' . str_repeat(' ', 4090));
        Parser::parseString('meter' . str_repeat(' ', 4091));

        $this->assertLimit(
            'meter' . str_repeat(' ', 4092),
            'input-bytes',
            4096,
            4097,
            null,
        );
    }

    public function testLexerRejectsOversizedInputBeforeTokenization(): void
    {
        $this->expectException(ExpressionLimitExceededException::class);
        $this->expectExceptionMessage('input byte length limit of 4096');

        new Lexer(str_repeat(' ', 4097));
    }

    public function testTokenCountLimitAcceptsItsBoundaryAndRejectsTheNextToken(): void
    {
        Parser::parseString(implode(' ', array_fill(0, 255, 'a')));
        Parser::parseString(implode(' ', array_fill(0, 256, 'a')));

        $this->assertLimit(
            implode(' ', array_fill(0, 257, 'a')),
            'token-count',
            256,
            257,
            [512, 513],
        );
    }

    public function testNestingLimitAcceptsItsBoundaryAndRejectsTheNextParenthesis(): void
    {
        Parser::parseString(str_repeat('(', 63) . 'meter' . str_repeat(')', 63));
        Parser::parseString(str_repeat('(', 64) . 'meter' . str_repeat(')', 64));

        $this->assertLimit(
            str_repeat('(', 65) . 'meter' . str_repeat(')', 65),
            'nesting-depth',
            64,
            65,
            [64, 65],
        );
    }

    public function testNestingDepthSurvivesTokensBetweenOpeningParentheses(): void
    {
        $this->assertLimit(
            str_repeat('(a * ', 65) . 'a' . str_repeat(')', 65),
            'nesting-depth',
            64,
            65,
            [320, 321],
        );
    }

    public function testNestingDepthReturnsToZeroAfterEachBalancedGroup(): void
    {
        $ast = Parser::parseString(implode(' ', array_fill(0, 65, '(a)')));

        $this->assertSame(65, substr_count($ast->toString(), 'a'));
    }

    public function testTokenByteLimitAppliesToIdentifiersAndNumbers(): void
    {
        Parser::parseString(str_repeat('a', 1023));
        Parser::parseString(str_repeat('a', 1024));
        Parser::parseString(str_repeat('9', 1023));
        Parser::parseString(str_repeat('9', 1024));

        $this->assertLimit(str_repeat('a', 1025), 'token-bytes', 1024, 1025, [0, 1025]);
        $this->assertLimit(str_repeat('9', 1025), 'token-bytes', 1024, 1025, [0, 1025]);
    }

    public function testTokenByteLimitCountsMultibyteSourceBytes(): void
    {
        Parser::parseString(str_repeat('α', 512));

        $this->assertLimit(str_repeat('α', 513), 'token-bytes', 1024, 1026, [0, 1026]);
    }

    public function testPublicRuntimeParsingExposesTheLimitException(): void
    {
        $this->expectException(ExpressionLimitExceededException::class);
        $this->expectExceptionMessage('identifier or numeric token byte length limit of 1024');

        Units::default()->parse(str_repeat('a', 1025));
    }

    public function testLimitFailuresDoNotEvictSuccessfulCacheEntries(): void
    {
        $anchor = Parser::parseString('limit_failure_cache_anchor');

        for ($index = 0; $index < 255; ++$index) {
            Parser::parseString('limit_failure_cache_' . $index);
        }

        $this->assertLimit(str_repeat('a', 1025), 'token-bytes', 1024, 1025, [0, 1025]);
        $this->assertSame($anchor, Parser::parseString('limit_failure_cache_anchor'));
    }

    public function testBundledCatalogDefinitionsRemainWithinTheParserBudget(): void
    {
        /** @var array{units: array<string, array<string, mixed>>} $catalog */
        $catalog = require __DIR__ . '/../../data/udunits2.php';
        $parsed = 0;

        foreach ($catalog['units'] as $record) {
            if (isset($record['def']) && is_string($record['def'])) {
                Parser::parseString($record['def']);
                ++$parsed;
            }
        }

        $this->assertGreaterThan(0, $parsed);
    }

    private function assertAstEquals(Ast $expected, Ast $actual): void
    {
        $this->assertSame($expected::class, $actual::class);
        $this->assertSame($expected->toString(), $actual->toString());
    }

    private function assertSpan(AstNode $ast, int $start, int $end): void
    {
        $this->assertNotNull($ast->span);
        $this->assertSame($start, $ast->span->start);
        $this->assertSame($end, $ast->span->end);
    }

    /** @param array{int, int}|null $span */
    private function assertLimit(
        string $input,
        string $limit,
        int $maximum,
        int $observed,
        ?array $span,
    ): void {
        try {
            Parser::parseString($input);
            self::fail('Expected the parser resource limit to be exceeded.');
        } catch (ExpressionLimitExceededException $exception) {
            $description = match ($limit) {
                'input-bytes' => 'input byte length',
                'token-count' => 'token count',
                'nesting-depth' => 'parenthesis nesting depth',
                'token-bytes' => 'identifier or numeric token byte length',
                default => $limit,
            };

            $this->assertSame($limit, $exception->limit);
            $this->assertSame($maximum, $exception->maximum);
            $this->assertSame($observed, $exception->observed);
            $this->assertSame(
                $span,
                $exception->span === null ? null : [$exception->span->start, $exception->span->end],
            );
            $this->assertStringContainsString('exceeds the ' . $description . ' limit', $exception->getMessage());
            $this->assertStringNotContainsString($input, $exception->getMessage());
        }
    }
}
