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

use jbboehr\Yumemi\PHPStan\InvalidUnitBinaryMathFunctionRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<InvalidUnitBinaryMathFunctionRule>
 */
final class InvalidUnitBinaryMathFunctionRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(InvalidUnitBinaryMathFunctionRule::class);
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/../../extension.neon'];
    }

    public function testInvalidBinaryMathCallsAreReported(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/InvalidUnitBinaryMathFunctionCalls.php'], [
            [
                'Cannot call fmod(): argument #1 has unit meter but argument #2 has unit second; they are not definitionally equivalent.',
                9,
            ],
            [
                'Cannot call hypot() with unit-bearing and unbranded operands; both operands need one definitionally equivalent unit.',
                10,
            ],
            [
                'Cannot call hypot() with unit-bearing and unbranded operands; both operands need one definitionally equivalent unit.',
                14,
            ],
            [
                'Cannot call fdiv() because the resulting unit exceeds the supported exponent range.',
                17,
            ],
            [
                'Cannot call pow(): unit exponentiation requires a constant integer exponent (e.g. $length ** 2).',
                18,
            ],
            [
                'Cannot call pow(): cannot raise a value to a unit power; the exponent must be a bare integer.',
                19,
            ],
            [
                'Cannot call pow(): unit exponentiation produces a unit outside the supported exponent range.',
                20,
            ],
            [
                'Cannot call pow(): unit exponentiation requires a constant integer exponent (e.g. $length ** 2).',
                24,
            ],
        ]);
    }
}
