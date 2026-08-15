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

use jbboehr\Yumemi\PHPStan\InvalidUnitAngleFunctionRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<InvalidUnitAngleFunctionRule> */
final class InvalidUnitAngleFunctionRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(InvalidUnitAngleFunctionRule::class);
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/../../extension.neon'];
    }

    public function testInvalidNativeAngleConversionsAreReported(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/InvalidUnitAngleFunctionCalls.php'], [
            [
                'Cannot call deg2rad() because at least one possible unit does not resolve canonically to arc_degree: radian.',
                9,
            ],
            [
                'Cannot call rad2deg() because at least one possible unit does not resolve canonically to radian: arc_degree.',
                10,
            ],
            [
                'Cannot call deg2rad() because at least one possible unit does not resolve canonically to arc_degree: radian.',
                11,
            ],
            [
                'Cannot call sin() because at least one possible unit does not resolve canonically to radian: arc_degree.',
                12,
            ],
            [
                'Cannot call cos() because at least one possible unit does not resolve canonically to radian: steradian.',
                13,
            ],
            [
                'Cannot call asin() because at least one possible unit does not resolve canonically to the exact unscaled ratio 1: percent.',
                14,
            ],
            [
                'Cannot call atan() because at least one possible unit does not resolve canonically to the exact unscaled ratio 1: radian.',
                15,
            ],
            [
                'Cannot call asin() because at least one possible unit does not resolve canonically to the exact unscaled ratio 1: 2.',
                16,
            ],
            [
                'Cannot call atan2(): argument #1 has unit meter but argument #2 has unit second; they are not definitionally equivalent.',
                17,
            ],
            [
                'Cannot call atan2(): argument #1 has unit meter but argument #2 has unit international_foot; they are not definitionally equivalent.',
                18,
            ],
            [
                'Cannot call atan2() with unit-bearing and unbranded operands; both operands need one definitionally equivalent unit.',
                19,
            ],
            [
                'Cannot call deg2rad() because at least one possible unit does not resolve canonically to arc_degree: degree_north.',
                35,
            ],
            [
                'Cannot call deg2rad() because at least one possible unit does not resolve canonically to arc_degree: degree_north.',
                40,
            ],
            [
                'Cannot call deg2rad() because at least one possible unit does not resolve canonically to arc_degree: degree_north.',
                46,
            ],
            [
                'Cannot call asin() because at least one possible unit does not resolve canonically to the exact unscaled ratio 1: count.',
                52,
            ],
            [
                'Cannot call atan2(): argument #1 has unit second but argument #2 has unit meter; they are not definitionally equivalent.',
                58,
            ],
        ]);
    }
}
