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

use jbboehr\Yumemi\Parser\Ast\Add;
use jbboehr\Yumemi\Parser\Ast\At;
use jbboehr\Yumemi\Parser\Ast\Div;
use jbboehr\Yumemi\Parser\Ast\Float_;
use jbboehr\Yumemi\Parser\Ast\Integer_;
use jbboehr\Yumemi\Parser\Ast\Mul;
use jbboehr\Yumemi\Parser\Ast\Pow;
use jbboehr\Yumemi\Parser\Ast\Sub;

/**
 * @internal
 */
trait ParserUtils
{
    private const SUPERSCRIPT_TO_ASCII = [
        '⁰' => '0',
        '¹' => '1',
        '²' => '2',
        '³' => '3',
        '⁴' => '4',
        '⁵' => '5',
        '⁶' => '6',
        '⁷' => '7',
        '⁸' => '8',
        '⁹' => '9',
        '⁺' => '+',
        '⁻' => '-',
    ];

    /**
     * @throws ParseException
     */
    public static function parseString(string $input): Ast
    {
        $lexer = new Lexer($input);
        $parser = new Parser($lexer);
        if (!$parser->parse()) {
            $end = strlen($input);

            throw new ParseException('Syntax error', 0, new SourceSpan($end, $end), $input);
        }
        return $parser->getAst();
    }

    private static function makeInteger(string $text, ?Location $location = null): Ast
    {
        return new Ast\Integer_($text, self::sourceSpan($location));
    }

    private static function makeSuperscriptInteger(string $text, ?Location $location = null): Ast
    {
        return self::makeInteger(strtr($text, self::SUPERSCRIPT_TO_ASCII), $location);
    }

    private static function makeFloat(string $text, ?Location $location = null): Ast
    {
        return new Ast\Float_($text, self::sourceSpan($location));
    }

    private static function makeMul(Ast $left, Ast $right, ?Location $location = null): Ast
    {
        return new Mul($left, $right, self::sourceSpan($location));
    }

    private static function makeDiv(Ast $left, Ast $right, ?Location $location = null): Ast
    {
        return new Div($left, $right, self::sourceSpan($location));
    }

    private static function makeAdd(Ast $left, Ast $right, ?Location $location = null): Ast
    {
        return new Add($left, $right, self::sourceSpan($location));
    }

    private static function makeSub(Ast $left, Ast $right, ?Location $location = null): Ast
    {
        return new Sub($left, $right, self::sourceSpan($location));
    }

    private static function makePow(Ast $left, Ast $right, ?Location $location = null): Ast
    {
        return new Pow($left, $right, self::sourceSpan($location));
    }

    private static function makeIdentifier(string $identifier, ?Location $location = null): Ast
    {
        return new Ast\Identifier($identifier, self::sourceSpan($location));
    }

    private static function makeNeg(Ast $expr, ?Location $location = null): Ast
    {
        $span = self::sourceSpan($location);

        if ($expr instanceof Integer_) {
            return new Integer_(self::negateNumber($expr->value), $span);
        } elseif ($expr instanceof Float_) {
            return new Float_(self::negateNumber($expr->value), $span);
        } else {
            return new Mul(self::makeInteger('-1'), $expr, $span);
        }
    }

    private static function negateNumber(string $value): string
    {
        return str_starts_with($value, '-') ? substr($value, 1) : '-' . $value;
    }

    private static function makeAt(Ast $left, Ast $right, ?Location $location = null): Ast
    {
        return new At($left, $right, self::sourceSpan($location));
    }

    /**
     * @logion [AWC 3:19] After the flood took the old bridge, the rival houses brought cedar from their separate hills,
     *     yet no beam would meet its fellow. A widow offered the charred lintel of her ruined home, and it joined them at
     *     midstream. Thereafter every crossing began by touching the blackened wood.
     */
    private static function sourceSpan(?Location $location): ?SourceSpan
    {
        if ($location?->begin === null || $location->end === null) {
            return null;
        }

        return new SourceSpan($location->begin, $location->end);
    }
}
