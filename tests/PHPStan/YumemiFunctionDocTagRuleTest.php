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

use jbboehr\Yumemi\PHPStan\YumemiFunctionDocTagRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<YumemiFunctionDocTagRule>
 */
final class YumemiFunctionDocTagRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(YumemiFunctionDocTagRule::class);
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/../../extension.neon'];
    }

    public function testFunctionTagsAreValidatedAtTheirDeclarations(): void
    {
        $this->analyse([__DIR__ . '/data/yumemi-function-doc-tag-validation.php'], [
            ['PHPDoc tag @yumemi-param has invalid syntax: expected "<unit type> $parameter".', 26],
            [
                'PHPDoc tag @yumemi-param for $length has invalid type: Unit not found: not_a_real_unit_xyz.',
                32,
            ],
            [
                "PHPDoc tag @yumemi-param for \$length has invalid type: expected unit_int<'...'>, unit_float<'...'>, or Quantity<'...'>; int given.",
                38,
            ],
            ['PHPDoc tag @yumemi-param references unknown parameter $missing.', 44],
            ['PHPDoc tag @yumemi-param for $length is duplicated.', 53],
            [
                "PHPDoc tag @yumemi-param for \$length declares unit_int<'meter'> but the native parameter type is float; expected int.",
                59,
            ],
            [
                "PHPDoc tag @yumemi-param for \$length declares unit_int<'meter'> but the native parameter type is int|null; expected int.",
                65,
            ],
            [
                "PHPDoc tag @yumemi-param for \$length declares unit_float<'meter'> but the native parameter type is float|int; expected float.",
                71,
            ],
            ['PHPDoc tag @yumemi-return has invalid syntax: the payload is not a valid PHPDoc type.', 77],
            [
                'PHPDoc tag @yumemi-return has invalid type: Unit not found: not_a_real_unit_xyz.',
                83,
            ],
            [
                "PHPDoc tag @yumemi-return has invalid type: expected unit_int<'...'>, unit_float<'...'>, or Quantity<'...'>; int given.",
                89,
            ],
            ['PHPDoc tag @yumemi-return has invalid syntax: a unit type is required.', 95],
            ['PHPDoc tag @yumemi-return is duplicated.', 104],
            [
                "PHPDoc tag @yumemi-return declares unit_float<'meter'> but the native return type is int; expected float.",
                110,
            ],
            [
                "PHPDoc tag @yumemi-return declares Quantity<'meter'> but the native return type is object; expected jbboehr\\Yumemi\\Quantity.",
                116,
            ],
        ]);
    }
}
