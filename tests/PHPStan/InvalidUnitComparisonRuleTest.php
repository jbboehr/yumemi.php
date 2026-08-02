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

use jbboehr\Yumemi\PHPStan\InvalidUnitComparisonRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<InvalidUnitComparisonRule>
 */
final class InvalidUnitComparisonRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(InvalidUnitComparisonRule::class);
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/../../extension.neon'];
    }

    public function testInvalidNativeUnitComparisonsAreReported(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/InvalidUnitComparisons.php'], [
            ['Cannot use == with incompatible units meter and second.', 11],
            ['Cannot use != with incompatible units meter and second.', 12],
            ['Cannot use === with incompatible units meter and second.', 13],
            ['Cannot use !== with incompatible units meter and second.', 14],
            ['Cannot use < with incompatible units meter and second.', 15],
            ['Cannot use <= with incompatible units meter and second.', 16],
            ['Cannot use > with incompatible units meter and second.', 17],
            ['Cannot use >= with incompatible units meter and second.', 18],
            ['Cannot use <=> with incompatible units meter and second.', 19],
            ['Cannot use == with incompatible units meter and international_foot.', 20],
            ['Cannot use == between a unit type and a bare value; every possible operand needs a unit.', 21],
            ['Cannot use < between a unit type and a bare value; every possible operand needs a unit.', 22],
            ['Cannot use == with incompatible units second and meter.', 32],
            ['Cannot use == between a unit type and a bare value; every possible operand needs a unit.', 38],
            ['Cannot use != between a unit type and a bare value; every possible operand needs a unit.', 45],
            ['Cannot use === between a unit type and a bare value; every possible operand needs a unit.', 48],
        ]);
    }
}
