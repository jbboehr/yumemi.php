<?php

/**
 * Iudex Mensurarum Mysticarum『夢見』〜ＹＵＭＥＭＩ〜
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
 */
class Lexer extends AbstractLexer implements LexerInterface
{
    private int $start = 0;
    private int $end = 0;

    public function __construct(string $input)
    {
        $this->setInput($input);
        $this->moveNext();
    }

    protected function getCatchablePatterns(): array
    {
        return [
            // numbers
            '(?:[\d]+(?:[\.][\d]+)*)(?:e[+-]?[\d]+)?',
            // identifier or qualified name
            '[\w_°][\w\d_]*(?:[\w_][\w\d_]*)*',
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
        if (is_numeric($value)) {
            if (strpos($value, '.') !== false || stripos($value, 'e') !== false) {
                return self::T_FLOAT;
            }

            return self::T_INTEGER;
        }

        switch ($value) {
            case '*':
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
        if (null !== $location) {
            $message .= ' at ' . $location;
        }

        throw new ParseException($message);
    }

    public function getLVal()
    {
        return /*trim(*/$this->token->value ?? ''/*, '"')*/;
    }

    public function yylex(): int
    {
        if (null === $this->lookahead) {
            return LexerInterface::YYEOF;
        }

        $this->start = $this->token->position ?? 0;

        $this->moveNext();

        $this->end = $this->token->position ?? 0;

        return $this->token->type ?? self::YYEOF;
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
