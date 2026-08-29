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

/** @internal */
final class SyntaxErrorFormatter
{
    private const MAX_EXCERPT_WIDTH = 120;
    private const TAB_WIDTH = 4;

    public static function format(string $description, string $source, SourceSpan $span): string
    {
        $sourceLength = strlen($source);
        $start = min($span->start, $sourceLength);
        $end = min(max($span->end, $start), $sourceLength);
        $before = substr($source, 0, $start);
        $lineNumber = substr_count($before, "\n") + 1;
        $lastNewline = strrpos($before, "\n");
        $lineStart = $lastNewline === false ? 0 : $lastNewline + 1;
        $nextNewline = strpos($source, "\n", $start);
        $lineEnd = $nextNewline === false ? $sourceLength : $nextNewline;
        $line = substr($source, $lineStart, $lineEnd - $lineStart);
        $relativeStart = $start - $lineStart;
        $relativeEnd = min($end, $lineEnd) - $lineStart;
        $prefix = substr($line, 0, $relativeStart);
        $marked = substr($line, $relativeStart, max(0, $relativeEnd - $relativeStart));
        $displayLine = self::expandTabs($line);
        $displayPrefix = self::expandTabs($prefix);
        $column = count(self::characters($displayPrefix));
        $markWidth = max(1, count(self::characters(self::expandTabs($marked, $column))));
        [$excerpt, $caretOffset, $visibleMarkWidth] = self::excerpt($displayLine, $column, $markWidth);
        $description = ucfirst(rtrim($description, ". \t\n\r"));

        return sprintf(
            "%s at line %d, column %d (byte offset %d).\n| %s\n| %s^%s",
            $description,
            $lineNumber,
            $column + 1,
            $start,
            $excerpt,
            str_repeat(' ', $caretOffset),
            str_repeat('~', $visibleMarkWidth - 1),
        );
    }

    /**
     * @return array{string, int, positive-int}
     */
    private static function excerpt(string $line, int $caretOffset, int $markWidth): array
    {
        $characters = self::characters($line);
        $length = count($characters);

        if ($length <= self::MAX_EXCERPT_WIDTH) {
            return [$line, $caretOffset, max(1, min($markWidth, $length - $caretOffset))];
        }

        if ($caretOffset <= 57) {
            $start = 0;
            $end = 117;
            $leftMarker = '';
            $rightMarker = '...';
        } elseif ($caretOffset >= $length - 57) {
            $start = $length - 117;
            $end = $length;
            $leftMarker = '...';
            $rightMarker = '';
        } else {
            $start = $caretOffset - 57;
            $end = $start + 114;
            $leftMarker = '...';
            $rightMarker = '...';
        }

        $visibleWidth = max(1, min($markWidth, $end - $caretOffset));
        $excerpt = $leftMarker . implode('', array_slice($characters, $start, $end - $start)) . $rightMarker;
        $adjustedCaret = strlen($leftMarker) + $caretOffset - $start;

        return [$excerpt, $adjustedCaret, $visibleWidth];
    }

    private static function expandTabs(string $value, int $column = 0): string
    {
        $output = '';

        foreach (self::characters($value) as $character) {
            if ($character === "\t") {
                $spaces = self::TAB_WIDTH - ($column % self::TAB_WIDTH);
                $output .= str_repeat(' ', $spaces);
                $column += $spaces;
                continue;
            }

            if ($character === "\r") {
                continue;
            }

            $output .= $character;
            ++$column;
        }

        return $output;
    }

    /**
     * @return list<string>
     */
    private static function characters(string $value): array
    {
        $characters = [];
        $length = strlen($value);
        $offset = 0;

        while ($offset < $length) {
            $remaining = $length - $offset;
            $lead = ord($value[$offset]);
            $second = $remaining >= 2 ? ord($value[$offset + 1]) : -1;
            $third = $remaining >= 3 ? ord($value[$offset + 2]) : -1;
            $fourth = $remaining >= 4 ? ord($value[$offset + 3]) : -1;
            $width = 1;

            if ($lead >= 0xc2 && $lead <= 0xdf && $second >= 0x80 && $second <= 0xbf) {
                $width = 2;
            } elseif (
                $third >= 0x80 && $third <= 0xbf
                && (
                    ($lead === 0xe0 && $second >= 0xa0 && $second <= 0xbf)
                    || (($lead >= 0xe1 && $lead <= 0xec) && $second >= 0x80 && $second <= 0xbf)
                    || ($lead === 0xed && $second >= 0x80 && $second <= 0x9f)
                    || (($lead >= 0xee && $lead <= 0xef) && $second >= 0x80 && $second <= 0xbf)
                )
            ) {
                $width = 3;
            } elseif (
                $third >= 0x80 && $third <= 0xbf
                && $fourth >= 0x80 && $fourth <= 0xbf
                && (
                    ($lead === 0xf0 && $second >= 0x90 && $second <= 0xbf)
                    || (($lead >= 0xf1 && $lead <= 0xf3) && $second >= 0x80 && $second <= 0xbf)
                    || ($lead === 0xf4 && $second >= 0x80 && $second <= 0x8f)
                )
            ) {
                $width = 4;
            }

            $characters[] = substr($value, $offset, $width);
            $offset += $width;
        }

        return $characters;
    }
}
