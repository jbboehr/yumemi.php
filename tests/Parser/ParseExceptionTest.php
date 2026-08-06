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

use jbboehr\Yumemi\Parser\ParseException;
use jbboehr\Yumemi\Parser\SourceSpan;
use PHPUnit\Framework\TestCase;

final class ParseExceptionTest extends TestCase
{
    public function testDefaultsToAnEmptyMessageAndZeroCode(): void
    {
        $exception = new ParseException();

        $this->assertSame('', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getSpan());
        $this->assertNull($exception->getSource());
    }

    public function testRequiresBothSpanAndSourceToFormatMessage(): void
    {
        $withoutSpan = new ParseException('Raw parser failure.', 17, source: 'meter');
        $withoutSource = new ParseException('Raw parser failure.', 18, new SourceSpan(0, 1));

        $this->assertSame('Raw parser failure.', $withoutSpan->getMessage());
        $this->assertSame(17, $withoutSpan->getCode());
        $this->assertNull($withoutSpan->getSpan());
        $this->assertSame('meter', $withoutSpan->getSource());

        $this->assertSame('Raw parser failure.', $withoutSource->getMessage());
        $this->assertSame(18, $withoutSource->getCode());
        $this->assertSame(0, $withoutSource->getSpan()?->start);
        $this->assertNull($withoutSource->getSource());
    }

    public function testClampsOnlyTheDisplayedSpanToTheAvailableSource(): void
    {
        $span = new SourceSpan(100, 120);
        $exception = new ParseException('syntax error.', 0, $span, 'meter');

        $this->assertSame($span, $exception->getSpan());
        $this->assertSame('meter', $exception->getSource());
        $this->assertSame(
            "Syntax error at line 1, column 6 (byte offset 5).\n"
                . "| meter\n"
                . '|      ^',
            $exception->getMessage(),
        );
    }

    public function testMalformedUtf8FallsBackToByteOrientedExcerptRendering(): void
    {
        $source = "\xFF/";
        $exception = new ParseException('syntax error', 0, new SourceSpan(0, 1), $source);
        $lines = explode("\n", $exception->getMessage());

        $this->assertCount(3, $lines);
        $this->assertSame('Syntax error at line 1, column 1 (byte offset 0).', $lines[0]);
        $this->assertSame("| \xFF/", $lines[1]);
        $this->assertSame('| ^', $lines[2]);
    }

    public function testClipsTheHighlightedSpanToTheDisplayedExcerpt(): void
    {
        $exception = new ParseException(
            'syntax error',
            0,
            new SourceSpan(100, 180),
            str_repeat('x', 200),
        );
        $lines = explode("\n", $exception->getMessage());

        $this->assertCount(3, $lines);
        $this->assertSame(122, strlen($lines[1]));
        $this->assertSame('| ' . str_repeat(' ', 60) . '^' . str_repeat('~', 56), $lines[2]);
    }

    public function testDoesNotTruncateAnExcerptAtTheMaximumWidth(): void
    {
        $source = str_repeat('x', 120);
        $exception = new ParseException('syntax error', 0, new SourceSpan(57, 58), $source);
        $lines = explode("\n", $exception->getMessage());

        $this->assertSame('| ' . $source, $lines[1]);
        $this->assertSame('| ' . str_repeat(' ', 57) . '^', $lines[2]);
    }

    public function testDoesNotClaimThatAnUntruncatedPrefixWasOmitted(): void
    {
        $source = str_repeat('x', 121);
        $exception = new ParseException('syntax error', 0, new SourceSpan(57, 58), $source);
        $lines = explode("\n", $exception->getMessage());

        $this->assertSame('| ' . substr($source, 0, 117) . '...', $lines[1]);
        $this->assertSame('| ' . str_repeat(' ', 57) . '^', $lines[2]);
    }

    public function testDoesNotClaimThatAnUntruncatedSuffixWasOmitted(): void
    {
        $source = str_repeat('x', 200);
        $exception = new ParseException('syntax error', 0, new SourceSpan(143, 144), $source);
        $lines = explode("\n", $exception->getMessage());

        $this->assertSame('| ...' . substr($source, -117), $lines[1]);
        $this->assertSame('| ' . str_repeat(' ', 63) . '^', $lines[2]);
    }
}
