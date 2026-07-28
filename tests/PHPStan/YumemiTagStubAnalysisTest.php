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

namespace jbboehr\Yumemi\Tests\PHPStan;

/**
 * Full-analysis integration: with the opt-in config, @yumemi-* tags inside a stub file are promoted and
 * the stub's branded parameter is enforced. In-process rather than via a phpstan subprocess.
 * The inert-without-opt-in counterpart lives in {@see YumemiTagStubIgnoredAnalysisTest}.
 */
final class YumemiTagStubAnalysisTest extends InProcessAnalysisTestCase
{
    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../../extension.neon',
            __DIR__ . '/../../yumemi-tags.neon',
            __DIR__ . '/data/yumemi-tag-stub.neon',
        ];
    }

    public function testStubParserPromotesTags(): void
    {
        $this->analyse([__DIR__ . '/data/yumemi-tag-stub.php'], self::STUB_PROMOTED_ERRORS);
    }

    /** @var list<array{0: string, 1: int, 2?: string}> */
    private const STUB_PROMOTED_ERRORS = [
        [
            'Parameter #1 $length of function YumemiStubFixture\\acceptsMeters expects unit_int<\'meter\'>, int given.',
            5,
            'Bare int is not assignable to unit_int<\'meter\'>; keep the unit annotation.',
        ],
    ];
}
