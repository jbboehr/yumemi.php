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

use jbboehr\Yumemi\PHPStan\InvalidQuantityArithmeticRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<InvalidQuantityArithmeticRule>
 */
final class InvalidQuantityArithmeticRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(InvalidQuantityArithmeticRule::class);
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../../extension.neon',
        ];
    }

    public function testInvalidQuantityArithmeticIsReported(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/InvalidQuantityArithmeticCalls.php'], [
            [
                'Cannot call Quantity::add() with dimensionally incompatible units meter (length) and second (time).',
                14,
            ],
            [
                'Cannot call Quantity::sub() with dimensionally incompatible units meter (length) and second (time).',
                15,
            ],
            [
                'Cannot call Quantity::addWithSameUnit() with units meter and international_foot; the method requires normalized-equivalent units.',
                18,
            ],
            [
                'Cannot call Quantity::subWithSameUnit() with units meter and international_foot; the method requires normalized-equivalent units.',
                19,
            ],
            [
                'Cannot call Quantity::root(): Unit expression meter has no exact symbolic root of degree 2.',
                22,
            ],
            [
                'Cannot call Quantity::root(): Root degree must be positive.',
                23,
            ],
            [
                'Cannot call Quantity::root(): Exponent 10001 exceeds the supported range of -10000 through 10000.',
                24,
            ],
            [
                'Quantity::pow() supports exponents from -10000 through 10000.',
                27,
            ],
            [
                'Cannot call Quantity::pow(): Exponent 10100 exceeds the supported range of -10000 through 10000.',
                28,
            ],
            [
                'Cannot call Quantity::add() with dimensionally incompatible units second (time) and meter (length).',
                50,
            ],
            [
                'Cannot call Quantity::add() with dimensionally incompatible units meter (length) and second (time).',
                56,
            ],
        ]);
    }
}
