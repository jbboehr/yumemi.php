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

use jbboehr\Yumemi\PHPStan\YumemiTagPromotionRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<YumemiTagPromotionRule>
 */
final class YumemiTagPromotionRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(YumemiTagPromotionRule::class);
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../../extension.neon',
            __DIR__ . '/../../yumemi-tags.neon',
        ];
    }

    public function testInvalidPromotionsAreReportedAtTheirDeclarations(): void
    {
        $this->analyse([__DIR__ . '/data/yumemi-tag-promotion-validation.php'], [
            ['PHPDoc tag @yumemi-param has invalid syntax.', 35],
            [
                'PHPDoc tag @yumemi-param for $length has invalid type: Unit not found: not_a_real_unit_xyz.',
                40,
            ],
            [
                "PHPDoc tag @yumemi-return has invalid type: expected a type containing unit_int<'...'>, unit_float<'...'>, Quantity<'...'>, or PointQuantity<'...'>.",
                45,
            ],
            ['PHPDoc tag @yumemi-param references unknown parameter $missing.', 51],
            ['PHPDoc tag @yumemi-param for $length is duplicated.', 59],
            [
                'PHPDoc tag @yumemi-param for $length must be an exact unit transform of (float | null); its erased type is (int | null).',
                67,
            ],
            [
                'PHPDoc tag @yumemi-param for $length must be an exact unit transform of float; its erased type is int.',
                76,
            ],
            [
                'PHPDoc tag @yumemi-return must be an exact unit transform of (float | null); its erased type is (int | null).',
                84,
            ],
            [
                'PHPDoc tag @yumemi-param for $length must be an exact unit transform of int; reference/variadic markers differ.',
                93,
            ],
            [
                'PHPDoc tag @yumemi-var for $length must be an exact unit transform of float; its erased type is int.',
                101,
            ],
            ['PHPDoc tag @yumemi-var without a variable name has an ambiguous fallback.', 108],
            ['PHPDoc tag @yumemi-param is only supported on function-like declarations.', 113],
        ]);
    }
}
