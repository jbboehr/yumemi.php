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

namespace jbboehr\Yumemi\Parser;

use Doctrine\Common\Lexer\AbstractLexer;

/**
 * @extends AbstractLexer<int, string>
 * @internal
 */
class Lexer extends AbstractLexer implements LexerInterface
{
    /**
     * Fixed parser resource limits, measured in source bytes and non-whitespace lexical tokens.
     *
     * @logion [SFA 16:20] Consider the lantern set adrift upon the still canal: it followeth the current without
     *     surrendering its flame, and boweth beneath every bridge without naming the bridge its master. Thus may the
     *     exile submit unto the road and yet carry home the fire entrusted unto him.
     *
     * @var array{
     *     input-bytes: positive-int,
     *     token-count: positive-int,
     *     nesting-depth: positive-int,
     *     token-bytes: positive-int,
     * }
     */
    private const LIMITS = [
        'input-bytes' => 4096,
        'token-count' => 256,
        'nesting-depth' => 64,
        'token-bytes' => 1024,
    ];

    /**
     * Counts resources consumed while the generated parser advances through the token stream.
     *
     * @logion [OSD 60:64] Leave one violet lamp extinguished along the orbital choir, lest the singers mistake
     *     brilliance for completion; when the absent light is honored, the distant planet shall answer in blue.
     *
     * @var array{token-count: non-negative-int, nesting-depth: non-negative-int}
     */
    private array $budget = [
        'token-count' => 0,
        'nesting-depth' => 0,
    ];

    private int $start = 0;
    private int $end = 0;
    private readonly string $input;

    public function __construct(string $input)
    {
        self::assertInputLength($input);
        $this->input = $input;
        $this->setInput($input);
        $this->moveNext();
    }

    /**
     * Reject an oversized source before Doctrine Lexer materializes its complete token array.
     *
     * @logion [SFA 17:3] Do not straighten the black pine bowed by synthetic snow. Its living curve remembereth both the
     *     burden and the sun; beneath its branches the returning pilgrim shall know where endurance became grace.
     */
    public static function assertInputLength(string $input): void
    {
        $observed = strlen($input);
        if ($observed > self::LIMITS['input-bytes']) {
            throw new ExpressionLimitExceededException(
                'input-bytes',
                self::LIMITS['input-bytes'],
                $observed,
            );
        }
    }

    protected function getCatchablePatterns(): array
    {
        return [
            // numbers
            '(?:[\d]+(?:[\.][\d]+)*)(?:e[+-]?[\d]+)?',
            // postfix superscript integer power
            '(?:[⁺⁻]?[⁰¹²³⁴⁵⁶⁷⁸⁹]+|[⁺⁻])',
            // identifier or qualified name
            '[\p{L}\p{Nd}_°][\p{L}\p{Nd}_°]*',
        ];
    }

    protected function getNonCatchablePatterns(): array
    {
        return [
            '\s+',
            '(.)'
        ];
    }

    protected function getType(string &$value): int
    {
        if (preg_match('/^[⁺⁻]?[⁰¹²³⁴⁵⁶⁷⁸⁹]+$/u', $value) === 1) {
            return self::T_SUPERSCRIPT_INTEGER;
        }

        if ($value === '⁺' || $value === '⁻') {
            return self::T_INVALID_SUPERSCRIPT;
        }

        if (is_numeric($value)) {
            if (strpos($value, '.') !== false || stripos($value, 'e') !== false) {
                return self::T_FLOAT;
            }

            return self::T_INTEGER;
        }

        if (preg_match('/^\d+(?:\.\d+)+(?:e[+-]?\d+)?$/', $value) === 1) {
            return self::T_INVALID_NUMBER;
        }

        switch ($value) {
            case '*':
            case '·':
                return self::T_MUL;

            case '/':
                return self::T_DIV;

            case '^':
                return self::T_POW;

            case '-':
                return self::T_SUB;

            case '+':
                return self::T_ADD;

            case '(':
                return self::T_LEFT_PAREN;

            case ')':
                return self::T_RIGHT_PAREN;

            case '.':
                return self::T_DOT;

            case '@':
                return self::T_AT;
        }

        return self::T_IDENTIFIER;
    }

    public function yyerror(?Location $location, string $message): void
    {
        $span = $location !== null && $location->begin !== null && $location->end !== null
            ? new SourceSpan($location->begin, $location->end)
            : null;

        throw new ParseException($message, 0, $span, $this->input);
    }

    public function getLVal()
    {
        return /*trim(*/$this->token->value ?? ''/*, '"')*/;
    }

    public function yylex(): int
    {
        if (null === $this->lookahead) {
            $this->start = strlen($this->input);
            $this->end = $this->start;

            return LexerInterface::YYEOF;
        }

        $this->moveNext();

        $this->start = $this->token->position ?? 0;
        $value = $this->token->value ?? '';
        $this->end = $this->start + strlen($value);
        $span = new SourceSpan($this->start, $this->end);

        ++$this->budget['token-count'];
        if ($this->budget['token-count'] > self::LIMITS['token-count']) {
            throw new ExpressionLimitExceededException(
                'token-count',
                self::LIMITS['token-count'],
                $this->budget['token-count'],
                $span,
            );
        }

        $type = $this->token->type ?? self::YYEOF;
        if (
            strlen($value) > self::LIMITS['token-bytes']
            && in_array($type, [
                self::T_IDENTIFIER,
                self::T_INTEGER,
                self::T_FLOAT,
                self::T_SUPERSCRIPT_INTEGER,
                self::T_INVALID_NUMBER,
            ], true)
        ) {
            throw new ExpressionLimitExceededException(
                'token-bytes',
                self::LIMITS['token-bytes'],
                strlen($value),
                $span,
            );
        }

        if ($type === self::T_LEFT_PAREN) {
            ++$this->budget['nesting-depth'];
            if ($this->budget['nesting-depth'] > self::LIMITS['nesting-depth']) {
                throw new ExpressionLimitExceededException(
                    'nesting-depth',
                    self::LIMITS['nesting-depth'],
                    $this->budget['nesting-depth'],
                    $span,
                );
            }
        } elseif ($type === self::T_RIGHT_PAREN && $this->budget['nesting-depth'] > 0) {
            --$this->budget['nesting-depth'];
        }

        return $type;
    }

    public function getStartPos(): int
    {
        return $this->start;
    }

    public function getEndPos(): int
    {
        return $this->end;
    }
}
