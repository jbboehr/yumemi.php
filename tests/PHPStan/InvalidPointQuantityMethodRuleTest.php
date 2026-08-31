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

use jbboehr\Yumemi\PHPStan\InvalidPointQuantityMethodRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<InvalidPointQuantityMethodRule>
 */
final class InvalidPointQuantityMethodRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(InvalidPointQuantityMethodRule::class);
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../../extension.neon',
        ];
    }

    public function testInvalidPointOperationsAreReported(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/InvalidPointQuantityCalls.php'], [
            [
                'Cannot call PointQuantity::add() with point unit celsius (temperature) and delta unit meter (length).',
                12,
            ],
            [
                'Cannot call PointQuantity::sub() with point unit celsius (temperature) and delta unit second (time).',
                13,
            ],
            [
                'Cannot call PointQuantity::differenceFrom() with dimensionally incompatible point units celsius '
                    . '(temperature) and meter (length).',
                14,
            ],
            [
                'Cannot call PointQuantity::to() with dimensionally incompatible point units celsius '
                    . '(temperature) and meters (length).',
                15,
            ],
            [
                'Cannot call PointQuantity::valueIn() with dimensionally incompatible point units celsius '
                    . '(temperature) and second (time).',
                16,
            ],
            [
                'Cannot call PointQuantity::significantDecimalValueIn() with dimensionally incompatible point units '
                    . 'celsius (temperature) and second (time).',
                17,
            ],
            [
                'Cannot call PointQuantity::compareTo() with dimensionally incompatible point units celsius '
                    . '(temperature) and meter (length).',
                18,
            ],
            [
                'Cannot call PointQuantity::lessThan() with dimensionally incompatible point units celsius '
                    . '(temperature) and meter (length).',
                20,
            ],
            [
                'Cannot call PointQuantity::lessThanOrEqualTo() with dimensionally incompatible point units celsius '
                    . '(temperature) and meter (length).',
                21,
            ],
            [
                'Cannot call PointQuantity::greaterThan() with dimensionally incompatible point units celsius '
                    . '(temperature) and meter (length).',
                22,
            ],
            [
                'Cannot call PointQuantity::greaterThanOrEqualTo() with dimensionally incompatible point units celsius '
                    . '(temperature) and meter (length).',
                23,
            ],
            [
                'Cannot call PointQuantity::to() with dimensionally incompatible point units meter '
                    . '(length) and fahrenheit (temperature).',
                49,
            ],
            [
                'Cannot call PointQuantity::differenceFrom() with dimensionally incompatible point units celsius '
                    . '(temperature) and meter (length).',
                55,
            ],
            [
                'Cannot call PointQuantity::lessThan() with dimensionally incompatible point units celsius '
                    . '(temperature) and meter (length).',
                61,
            ],
            [
                'Cannot call PointQuantity::differenceFrom() with dimensionally incompatible point units celsius '
                    . '(temperature) and meter (length).',
                67,
            ],
            [
                'Cannot call PointQuantity::difference() with dimensionally incompatible point units celsius '
                    . '(temperature) and meter (length).',
                70,
            ],
        ]);
    }
}
