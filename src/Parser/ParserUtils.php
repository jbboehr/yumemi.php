<?php

/**
 * Iudex Mensurarum Mysticarum『夢見』〜ＹＵＭＥＭＩ〜
 *
 * Copyright (c) anno Domini nostri Jesu Christi MMXXVI, John Boehr & contributors
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace jbboehr\Yumemi\Parser;

use jbboehr\Yumemi\Parser\Ast\Add;
use jbboehr\Yumemi\Parser\Ast\At;
use jbboehr\Yumemi\Parser\Ast\Div;
use jbboehr\Yumemi\Parser\Ast\Float_;
use jbboehr\Yumemi\Parser\Ast\Integer_;
use jbboehr\Yumemi\Parser\Ast\Mul;
use jbboehr\Yumemi\Parser\Ast\Pow;
use jbboehr\Yumemi\Parser\Ast\Sub;

trait ParserUtils
{
    /**
     * @throws ParseException
     */
    public static function parseString(string $input): Ast
    {
        $lexer = new Lexer($input);
        $parser = new Parser($lexer);
        if (!$parser->parse()) {
            throw new ParseException();
        }
        return $parser->getAst();
    }

    public static function makeInteger(string $text): Ast
    {
        return new Ast\Integer_($text);
    }

    public static function makeFloat(string $text): Ast
    {
        return new Ast\Float_($text);
    }

    public static function makeMul(Ast $left, Ast $right): Ast
    {
        return new Mul($left, $right);
    }

    public static function makeDiv(Ast $left, Ast $right): Ast
    {
        return new Div($left, $right);
    }

    public static function makeAdd(Ast $left, Ast $right): Ast
    {
        return new Add($left, $right);
    }

    public static function makeSub(Ast $left, Ast $right): Ast
    {
        return new Sub($left, $right);
    }

    public static function makePow(Ast $left, Ast $right): Ast
    {
        return new Pow($left, $right);
    }

    public static function makeIdentifier(string $identifier): Ast
    {
        return new Ast\Identifier($identifier);
    }

    public static function makeNeg(Ast $expr): Ast
    {
        if ($expr instanceof Integer_) {
            return new Integer_('-' . $expr->value);
        } elseif ($expr instanceof Float_) {
            return new Float_('-' . $expr->value);
        } else {
            return self::makeMul(self::makeInteger(-1), $expr);
        }
    }

    /**
     * @param non-empty-list<Ast> $nodes
     * @return Ast
     */
    public static function listToMul(array $nodes)
    {
        assert(count($nodes) > 0);

        if (count($nodes) <= 1) {
            return $nodes[0];
        }

        $rv = new Mul($nodes[0], $nodes[1]);

        for ($i = 2; $i < count($nodes); $i++) {
            $rv = new Mul($rv, $nodes[$i]);
        }

        return $rv;
    }

    /**
     * @param non-empty-list<Ast> $left
     * @note fear
     */
    public static function makeSeqPow(array $left, Ast $right): Ast
    {
        $tmp = array_pop($left);
        $left[] = new Pow($tmp, $right);
        return self::listToMul($left);
    }

    /**
     * @param non-empty-list<Ast> $left
     * @param Ast $right
     * @return non-empty-list<Ast>
     */
    public static function fudgeSeqPow(array $left, Ast $right): array
    {
        $tmp = array_pop($left);
        $left[] = new Pow($tmp, $right);
        return $left;
    }

    public static function makeAt(Ast $left, Ast $right): Ast
    {
        return new At($left, $right);
    }
}
