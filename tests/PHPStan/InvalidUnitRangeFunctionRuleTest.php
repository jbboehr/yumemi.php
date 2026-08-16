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

declare(strict_types=1);

namespace jbboehr\Yumemi\Tests\PHPStan;

use jbboehr\Yumemi\PHPStan\InvalidUnitRangeFunctionRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<InvalidUnitRangeFunctionRule> */
final class InvalidUnitRangeFunctionRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(InvalidUnitRangeFunctionRule::class);
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/../../extension.neon'];
    }

    public function testInvalidNativeUnitRangesAreReported(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/InvalidUnitRangeFunctionCalls.php'], [
            [
                'Cannot call range() with units international_foot and meter because they are not definitionally equivalent.',
                48,
            ],
            [
                'Cannot call range() with unit-bearing and unbranded arguments; both endpoints and any explicit step need one definitionally equivalent unit.',
                49,
            ],
            [
                'Cannot call range() with unit-bearing and unbranded arguments; both endpoints and any explicit step need one definitionally equivalent unit.',
                50,
            ],
            [
                'Cannot call range() with units meter and second because they are not definitionally equivalent.',
                51,
            ],
            [
                'Cannot call range() with a unit-bearing argument unless both endpoints and any explicit step are int or float unit values; cast numeric strings before constructing the range.',
                55,
            ],
        ]);
    }
}
