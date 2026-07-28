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

use jbboehr\Yumemi\Parser\SourceSpan;
use PHPUnit\Framework\TestCase;

final class SourceSpanTest extends TestCase
{
    public function testRepresentsHalfOpenByteRange(): void
    {
        $span = new SourceSpan(4, 9);

        $this->assertSame(4, $span->start);
        $this->assertSame(9, $span->end);
        $this->assertSame(5, $span->length());
        $this->assertFalse($span->isEmpty());
    }

    public function testRepresentsEmptyRange(): void
    {
        $span = new SourceSpan(7, 7);

        $this->assertSame(0, $span->length());
        $this->assertTrue($span->isEmpty());
    }

    public function testRejectsNegativeStart(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new SourceSpan(-1, 0);
    }

    public function testRejectsEndBeforeStart(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new SourceSpan(2, 1);
    }
}
