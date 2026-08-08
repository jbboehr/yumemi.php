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

use jbboehr\Yumemi\PHPStan\InvalidUnitMinMaxFunctionRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<InvalidUnitMinMaxFunctionRule>
 */
final class InvalidUnitMinMaxFunctionRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(InvalidUnitMinMaxFunctionRule::class);
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/../../extension.neon'];
    }

    public function testInvalidNativeUnitSelectionsAreReported(): void
    {
        $incompatible = 'because they are not definitionally equivalent.';
        $mixed = 'every possible result needs one definitionally equivalent unit.';

        $this->analyse([__DIR__ . '/Fixtures/InvalidUnitMinMaxFunctionCalls.php'], [
            ["Cannot call min() with units international_foot and meter {$incompatible}", 13],
            ["Cannot call max() with units international_foot and meter {$incompatible}", 14],
            ["Cannot call min() with unit-bearing and unbranded candidates; {$mixed}", 15],
            ["Cannot call max() with units international_foot and meter {$incompatible}", 16],
            ["Cannot call min() with unit-bearing and unbranded candidates; {$mixed}", 17],
            ["Cannot call max() with unit-bearing and unbranded candidates; {$mixed}", 18],
            ["Cannot call min() with units international_foot and meter {$incompatible}", 22],
            ["Cannot call max() with unit-bearing and unbranded candidates; {$mixed}", 26],
            ["Cannot call min() with units international_foot and meter {$incompatible}", 30],
            ["Cannot call max() with units international_foot and meter {$incompatible}", 31],
            ["Cannot call min() with unit-bearing and unbranded candidates; {$mixed}", 35],
            ["Cannot call max() with unit-bearing and unbranded candidates; {$mixed}", 36],
        ]);
    }
}
