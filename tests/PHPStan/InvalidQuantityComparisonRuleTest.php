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

use jbboehr\Yumemi\PHPStan\InvalidQuantityComparisonRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<InvalidQuantityComparisonRule>
 */
final class InvalidQuantityComparisonRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(InvalidQuantityComparisonRule::class);
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../../extension.neon',
        ];
    }

    public function testInvalidQuantityComparisonsAreReported(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/InvalidQuantityComparisonCalls.php'], [
            [
                'Cannot call Quantity::compareTo() with dimensionally incompatible units meter (length) and second (time).',
                13,
            ],
            [
                'Cannot call Quantity::equals() with dimensionally incompatible units meter (length) and second (time).',
                14,
            ],
            [
                'Cannot call Quantity::lessThan() with dimensionally incompatible units meter (length) and second (time).',
                15,
            ],
            [
                'Cannot call Quantity::lessThanOrEqualTo() with dimensionally incompatible units meter (length) and second (time).',
                16,
            ],
            [
                'Cannot call Quantity::greaterThan() with dimensionally incompatible units meter (length) and second (time).',
                17,
            ],
            [
                'Cannot call Quantity::greaterThanOrEqualTo() with dimensionally incompatible units meter (length) and second (time).',
                18,
            ],
            [
                'Cannot call Quantity::equals() with dimensionally incompatible units meter (length) and second (time).',
                44,
            ],
        ]);
    }
}
