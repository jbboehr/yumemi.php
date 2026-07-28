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

// The enforcement fixtures call sink functions they declare; make them resolvable in-process.
require_once __DIR__ . '/data/yumemi-tag-return-enforced.php';
require_once __DIR__ . '/data/yumemi-tag-call-enforcement.php';
require_once __DIR__ . '/data/yumemi-tag-native-mismatch.php';

/**
 * Full-analysis integration for opt-in @yumemi-* tag promotion (extension.neon + yumemi-tags.neon),
 * in-process instead of via a phpstan subprocess.
 */
final class YumemiTagEnforcementAnalysisTest extends InProcessAnalysisTestCase
{
    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../../extension.neon',
            __DIR__ . '/../../yumemi-tags.neon',
        ];
    }

    public function testBrandedReturnIsEnforcedAtCallSites(): void
    {
        $this->analyse([__DIR__ . '/data/yumemi-tag-return-enforced.php'], [
            [
                'Function measuredFeetForCall() should return unit_int<\'international_foot\'> but returns int.',
                14,
                'Bare int is not assignable to unit_int<\'international_foot\'>; keep the unit annotation.',
            ],
            [
                'Parameter #1 $length of function consumeMeters expects unit_int<\'meter\'>, unit_int<\'international_foot\'> given.',
                24,
                'Unit unit_int<\'international_foot\'> is not assignable to unit_int<\'meter\'> (normalized forms differ).',
            ],
        ]);
    }

    public function testPromotedParamTagsUsePhpStanCoreChecking(): void
    {
        $anonMethod = 'class@anonymous/' . __DIR__ . '/data/yumemi-tag-call-enforcement.php:17::accept()';

        $this->analyse([__DIR__ . '/data/yumemi-tag-call-enforcement.php'], [
            [
                'Parameter #1 $length of function acceptsMeters expects unit_int<\'meter\'>, int given.',
                14,
                'Bare int is not assignable to unit_int<\'meter\'>; keep the unit annotation.',
            ],
            [
                'Parameter #1 $length of function acceptsMeters expects unit_int<\'meter\'>, unit_int<\'international_foot\'> given.',
                15,
                'Unit unit_int<\'international_foot\'> is not assignable to unit_int<\'meter\'> (normalized forms differ).',
            ],
            [
                sprintf('Parameter #1 $length of method %s expects unit_int<\'meter\'>, int given.', $anonMethod),
                28,
                'Bare int is not assignable to unit_int<\'meter\'>; keep the unit annotation.',
            ],
        ]);
    }

    public function testPromotedTypesAreCheckedAgainstNativeSignatures(): void
    {
        $this->analyse([__DIR__ . '/data/yumemi-tag-native-mismatch.php'], [
            [
                'PHPDoc tag @param for parameter $length with type unit_float<\'meter\'> is incompatible with native type int.',
                7,
            ],
            [
                'PHPDoc tag @return with type unit_float<\'meter\'> is incompatible with native type int.',
                7,
            ],
        ]);
    }
}
