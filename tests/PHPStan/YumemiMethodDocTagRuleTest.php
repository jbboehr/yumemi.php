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

use jbboehr\Yumemi\PHPStan\YumemiMethodDocTagRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<YumemiMethodDocTagRule>
 */
final class YumemiMethodDocTagRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(YumemiMethodDocTagRule::class);
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/../../extension.neon'];
    }

    public function testMethodTagsAreValidatedAtTheirDeclarations(): void
    {
        $this->analyse([__DIR__ . '/data/yumemi-method-doc-tag-validation.php'], [
            ['PHPDoc tag @yumemi-return is not supported on methods.', 30],
            ['PHPDoc tag @yumemi-return is duplicated.', 39],
            ['PHPDoc tag @yumemi-return is not supported on methods.', 39],
            ['PHPDoc tag @yumemi-param has invalid syntax: expected "<unit type> $parameter".', 45],
            ['PHPDoc tag @yumemi-param references unknown parameter $missing.', 51],
            ['PHPDoc tag @yumemi-param for $length is duplicated.', 60],
            [
                "PHPDoc tag @yumemi-param for \$length declares unit_float<'meter'> but the native parameter type is int; expected float.",
                66,
            ],
            [
                "PHPDoc tag @yumemi-param for \$length declares unit_int<'meter'> but the native parameter type is int|null; expected int.",
                72,
            ],
            [
                "PHPDoc tag @yumemi-param for \$quantity declares Quantity<'meter'> but the native parameter type is object; expected jbboehr\\Yumemi\\Quantity.",
                78,
            ],
            [
                'PHPDoc tag @yumemi-param for $length has invalid type: Unit not found: not_a_real_unit_xyz.',
                87,
            ],
        ]);
    }
}
