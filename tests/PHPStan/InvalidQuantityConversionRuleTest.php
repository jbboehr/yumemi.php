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

use jbboehr\Yumemi\PHPStan\InvalidQuantityConversionRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<InvalidQuantityConversionRule>
 */
final class InvalidQuantityConversionRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(InvalidQuantityConversionRule::class);
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../../extension.neon',
        ];
    }

    public function testInvalidConversionsAreReported(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/InvalidQuantityConversionCalls.php'], [
            [
                'Cannot call Quantity::to() with dimensionally incompatible units meter (length) and second (time).',
                10,
            ],
            [
                'Cannot call Quantity::valueIn() with dimensionally incompatible units meter (length) and second (time).',
                11,
            ],
            [
                'Cannot call Quantity::intValueIn() with dimensionally incompatible units meter (length) and second (time).',
                12,
            ],
            [
                'Cannot call Quantity::exactIntValueIn() with dimensionally incompatible units meter (length) and second (time).',
                13,
            ],
            [
                'Cannot call Quantity::decimalValueIn() with dimensionally incompatible units meter (length) and second (time).',
                14,
            ],
            [
                'Cannot call Quantity::exactDecimalValueIn() with dimensionally incompatible units meter (length) and second (time).',
                15,
            ],
            [
                'Cannot call Quantity::floatValueIn() with dimensionally incompatible units meter (length) and second (time).',
                16,
            ],
            [
                'Unit not found: not_a_real_unit_xyz.',
                17,
            ],
            [
                'Cannot call Quantity::to() with dimensionally incompatible units meter (length) and second (time).',
                25,
            ],
        ]);
    }
}
