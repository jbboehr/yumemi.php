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

use jbboehr\Yumemi\PHPStan\InvalidQuantityConstructionRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<InvalidQuantityConstructionRule>
 */
final class InvalidQuantityConstructionRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(InvalidQuantityConstructionRule::class);
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../../extension.neon',
        ];
    }

    public function testInvalidBrandedConstructionIsReported(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/InvalidQuantityConstructionCalls.php'], [
            [
                'Units::quantity() value unit international_foot does not match target unit metres (normalized forms differ).',
                14,
            ],
            [
                'Units::quantity() value unit second does not match target unit meter (normalized forms differ).',
                15,
            ],
            [
                'Unit not found: not_a_real_unit_xyz.',
                16,
            ],
            [
                'Units::quantity() value unit meter does not match target unit international_foot (normalized forms differ).',
                21,
            ],
            [
                "Syntax error, unexpected '/' at line 1, column 9 (byte offset 8).\n"
                    . "| meter * / second\n"
                    . '|         ^',
                29,
            ],
            [
                'Unit "B" uses logarithmic semantics, which are not supported by multiplicative unit algebra (definition: lg(re 1)).',
                30,
            ],
            [
                'Unit not found: not_a_real_unit_xyz.',
                32,
            ],
            [
                "Syntax error, unexpected '/' at line 1, column 9 (byte offset 8).\n"
                    . "| meter * / second\n"
                    . '|         ^',
                33,
            ],
            [
                'Unit "B" uses logarithmic semantics, which are not supported by multiplicative unit algebra (definition: lg(re 1)).',
                34,
            ],
            [
                'Point quantities require a single named coordinate unit.',
                36,
            ],
            [
                'Conversion of unit "B" with logarithmic semantics is not supported (definition: lg(re 1)).',
                37,
            ],
        ]);
    }
}
