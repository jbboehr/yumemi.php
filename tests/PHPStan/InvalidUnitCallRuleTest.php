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

use jbboehr\Yumemi\PHPStan\InvalidUnitCallRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * Standalone diagnostics for invalid unit() / unit_factor() / unit_to() calls (review finding #2).
 *
 * @extends RuleTestCase<InvalidUnitCallRule>
 */
final class InvalidUnitCallRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(InvalidUnitCallRule::class);
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../../extension.neon',
        ];
    }

    public function testInvalidCallsAreReported(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/InvalidUnitCalls.php'], [
            [
                'Unit not found: not_a_real_unit_xyz.',
                10,
            ],
            [
                'Unit not found: not_a_real_unit_xyz.',
                13,
            ],
            [
                'Unit not found: not_a_real_unit_xyz.',
                16,
            ],
            [
                'Cannot convert with unit_to(): units meter and second are not dimensionally compatible.',
                19,
            ],
            [
                'unit_to() value unit international_foot does not match from unit meter (normalized forms differ).',
                22,
            ],
            [
                'Cannot calculate unit_factor(): Incompatible unit expressions: meter and second. '
                    . 'Dimensions: length vs time.',
                25,
            ],
            [
                'Unit "celsius" is known but uses unsupported affine semantics (definition: degree_Celsius).',
                26,
            ],
            [
                'Unit "celsius" is known but uses unsupported affine semantics (definition: degree_Celsius).',
                27,
            ],
            [
                'Unit "B" is known but uses unsupported logarithmic semantics (definition: lg(re 1)).',
                28,
            ],
            [
                'Unit not found: not_a_real_unit_xyz.',
                29,
            ],
            [
                'Unit not found: not_a_real_unit_xyz.',
                30,
            ],
            [
                "Syntax error, unexpected 'end of file' at line 1, column 8 (byte offset 7).\n"
                    . "| meter /\n"
                    . '|        ^',
                31,
            ],
            [
                "Syntax error, unexpected 'end of file' at line 1, column 9 (byte offset 8).\n"
                    . "| second /\n"
                    . '|         ^',
                32,
            ],
            [
                "Syntax error, unexpected '/' at line 1, column 9 (byte offset 8).\n"
                    . "| meter * / second\n"
                    . '|         ^',
                40,
            ],
            [
                "Syntax error, unexpected 'end of file' at line 1, column 9 (byte offset 8).\n"
                    . "| second /\n"
                    . '|         ^',
                41,
            ],
            [
                'Unit "B" is known but uses unsupported logarithmic semantics (definition: lg(re 1)).',
                44,
            ],
            [
                'Unit "degree_Celsius" is known but uses unsupported affine semantics (definition: K @ 273.15).',
                45,
            ],
            [
                'Cannot convert with unit_to(): units degree_Celsius and meter are not dimensionally compatible.',
                48,
            ],
            [
                'unit_to() cannot use a unit-branded value with affine from unit celsius.',
                49,
            ],
            [
                'Affine units cannot be multiplied, divided, or raised to powers: (celsius * meter).',
                50,
            ],
            [
                'Affine unit "celsius" cannot be prefixed.',
                51,
            ],
        ]);
    }
}
