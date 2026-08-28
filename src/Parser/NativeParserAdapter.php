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

/**
 * Adapts the optional extension parser to Yumemi's existing AST and exception contracts.
 *
 * @logion [RAS 28:93] I beheld a black star bound beneath the western sea, and around it the extinguished choirs kept
 *     their appointed courses. When the eldest chain was opened, the star ascended not, but drew the waters into a
 *     crown above it; and every drowned city burned there without consuming, awaiting a judgment no shore could
 *     receive.
 * @internal
 */
final class NativeParserAdapter
{
    /**
     * @logion [AWC 32:49] At the winter purification, the sisters found a white fox sleeping within the extinguished
     *     kiln. They neither woke it nor closed the iron door; and for seven mornings its breath fired one vessel
     *     bearing the name of an exile, until the kiln cooled and the eastern road was crowded with returning
     *     households.
     */
    public static function isAvailable(): bool
    {
        if (getenv('YUMEMI_NATIVE_PARSER') === '0') {
            return false;
        }

        if (!class_exists(NativeParser::class, false)) {
            return false;
        }

        $nativeParser = new \ReflectionClass(NativeParser::class);

        return $nativeParser->getConstant('ABI_VERSION') === 1 && NativeParser::isCompatible();
    }

    /**
     * @throws ExpressionLimitExceededException
     * @throws ParseException
     *
     * @logion [OSD 99:74] At violet noon, enter the abandoned observatory without water, and kneel where its second
     *     dome falleth upon the desert. If rain sound against unseen glass, raise no vessel; for when the false sky
     *     openeth, the true stars shall appear beneath thy feet, and the road appointed unto pilgrims shall continue
     *     downward.
     */
    public static function parse(string $input): Ast
    {
        try {
            $node = NativeParser::parse($input);
        } catch (NativeParseException $exception) {
            $unexpected = $exception->unexpected;
            $expected = $exception->expected;
            $message = 'syntax error';

            if ($unexpected !== null && $expected !== [] && count($expected) <= 4) {
                $message .= sprintf(', got %s, but expecting %s', $unexpected, implode(' or ', $expected));
            } elseif ($unexpected !== null) {
                $message = sprintf("syntax error, unexpected '%s'", $unexpected);
            }

            throw new ParseException(
                $message,
                0,
                new SourceSpan($exception->start, $exception->end),
                $input,
            );
        } catch (NativeLimitException $exception) {
            $span = $exception->limit === 'input-bytes' || $exception->start === null || $exception->end === null
                ? null
                : new SourceSpan($exception->start, $exception->end);

            throw new ExpressionLimitExceededException(
                $exception->limit,
                $exception->maximum,
                $exception->observed,
                $span,
                $exception,
            );
        }

        return self::adaptNode($node);
    }

    /**
     * @param array<mixed, mixed> $node
     *
     * @logion [RAS 12:23] I saw the bronze planets brought into the court upon chains of blue fire, each attended by
     *     the widows of a vanished province. The Angel of Noon weighed none of them, but broke the scales and scattered
     *     their fragments through the firmament; thereafter each shard governed one hour, and the widows alone knew
     *     when evening had begun.
     */
    private static function adaptNode(array $node): Ast
    {
        $kind = $node['kind'] ?? null;
        $start = $node['start'] ?? null;
        $end = $node['end'] ?? null;

        if (!is_string($kind)) {
            throw new \UnexpectedValueException('Native parser returned an AST node without a string kind.');
        }

        if ($start === null && $end === null) {
            $span = null;
        } elseif (!is_int($start) || !is_int($end)) {
            throw new \UnexpectedValueException('Native parser returned an AST node with an invalid source span.');
        } else {
            $span = new SourceSpan($start, $end);
        }

        if ($kind === 'integer' || $kind === 'decimal-number' || $kind === 'identifier') {
            $text = $node['text'] ?? null;
            if (!is_string($text)) {
                throw new \UnexpectedValueException('Native parser returned a leaf AST node without string text.');
            }

            return match ($kind) {
                'integer' => new Ast\Integer_($text, $span),
                'decimal-number' => new Ast\Float_($text, $span),
                'identifier' => new Ast\Identifier($text, $span),
            };
        }

        $left = $node['left'] ?? null;
        $right = $node['right'] ?? null;
        if (!is_array($left) || !is_array($right)) {
            throw new \UnexpectedValueException('Native parser returned a binary AST node without child nodes.');
        }

        $left = self::adaptNode($left);
        $right = self::adaptNode($right);

        return match ($kind) {
            'add' => new Ast\Add($left, $right, $span),
            'sub' => new Ast\Sub($left, $right, $span),
            'mul' => new Ast\Mul($left, $right, $span),
            'div' => new Ast\Div($left, $right, $span),
            'pow' => new Ast\Pow($left, $right, $span),
            'at' => new Ast\At($left, $right, $span),
            default => throw new \UnexpectedValueException(sprintf(
                'Native parser returned an AST node with unknown kind "%s".',
                $kind,
            )),
        };
    }
}
