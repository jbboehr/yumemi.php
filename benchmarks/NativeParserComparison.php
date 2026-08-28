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

namespace jbboehr\Yumemi\Benchmarks;

use jbboehr\Yumemi\Analyzer\AstConverter;
use jbboehr\Yumemi\Analyzer\ExprReducer;
use jbboehr\Yumemi\Analyzer\UnitResolver;
use jbboehr\Yumemi\Expr;
use jbboehr\Yumemi\Parser\Ast;
use jbboehr\Yumemi\Parser\Lexer;
use jbboehr\Yumemi\Parser\NativeParserAdapter;
use jbboehr\Yumemi\Parser\ParseException;
use jbboehr\Yumemi\Parser\Parser;
use jbboehr\Yumemi\Parser\SourceSpan;
use jbboehr\Yumemi\Registry\Udunits2UnitRegistry;
use PhpBench\Attributes as Bench;

#[Bench\BeforeMethods('setUp')]
#[Bench\Groups(['runtime', 'parsing', 'native-parser'])]
#[Bench\ParamProviders('provideExpressions')]
final class NativeParserComparisonBench
{
    private Udunits2UnitRegistry $unitRegistry;

    /** @param array{input: string} $params */
    public function setUp(array $params): void
    {
        if (!NativeParserAdapter::isAvailable()) {
            throw new \RuntimeException('The native-parser benchmark requires a compatible ext-yumemi installation.');
        }

        $php = self::parseWithPhp($params['input']);
        $native = NativeParserAdapter::parse($params['input']);
        if (serialize($php) !== serialize($native)) {
            throw new \UnexpectedValueException('The PHP and native parsers returned different ASTs.');
        }

        $this->unitRegistry = new Udunits2UnitRegistry();
    }

    /** @param array{input: string} $params */
    #[Bench\Revs(100)]
    public function benchPhpParser(array $params): Ast
    {
        return self::parseWithPhp($params['input']);
    }

    /** @param array{input: string} $params */
    #[Bench\Revs(100)]
    public function benchNativeParser(array $params): Ast
    {
        Lexer::assertInputLength($params['input']);
        if (!NativeParserAdapter::isAvailable()) {
            throw new \RuntimeException('The native parser became unavailable during the benchmark.');
        }

        return NativeParserAdapter::parse($params['input']);
    }

    /** @param array{input: string} $params */
    #[Bench\Revs(100)]
    public function benchPhpParseAndResolve(array $params): Expr
    {
        return $this->resolve(self::parseWithPhp($params['input']));
    }

    /** @param array{input: string} $params */
    #[Bench\Revs(100)]
    public function benchNativeParseAndResolve(array $params): Expr
    {
        Lexer::assertInputLength($params['input']);
        if (!NativeParserAdapter::isAvailable()) {
            throw new \RuntimeException('The native parser became unavailable during the benchmark.');
        }

        return $this->resolve(NativeParserAdapter::parse($params['input']));
    }

    /** @return iterable<string, array{input: string}> */
    public function provideExpressions(): iterable
    {
        yield 'simple' => ['input' => 'meter'];
        yield 'compound' => ['input' => 'kilogram * meter / second^2'];
        yield 'unicode-nested' => ['input' => '--((Ω / second)^-2) · kilogram'];
    }

    private static function parseWithPhp(string $input): Ast
    {
        Lexer::assertInputLength($input);
        $parser = new Parser(new Lexer($input));
        if (!$parser->parse()) {
            $end = strlen($input);

            throw new ParseException('Syntax error', 0, new SourceSpan($end, $end), $input);
        }

        return $parser->getAst();
    }

    private function resolve(Ast $ast): Expr
    {
        $astConverter = new AstConverter(new UnitResolver($this->unitRegistry));

        return ExprReducer::reduce($astConverter->convert($ast));
    }
}
