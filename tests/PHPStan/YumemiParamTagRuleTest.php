<?php

/**
 * Iudex Mensurarum Mysticarum『夢見』〜ＹＵＭＥＭＩ〜
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

use jbboehr\Yumemi\PHPStan\YumemiParamTagRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

// The @yumemi-param function must exist in the process so native reflection can resolve it; the
// annotated class is autoloaded via PSR-4 and needs no require.
require_once __DIR__ . '/Fixtures/YumemiTagParamFunctions.php';

/**
 * Checks that a branded argument carrying the wrong unit is rejected at a @yumemi-param position,
 * while matching and bare-native arguments pass, for both function and method calls.
 *
 * @extends RuleTestCase<YumemiParamTagRule>
 */
final class YumemiParamTagRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(YumemiParamTagRule::class);
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../../extension.neon',
        ];
    }

    public function testMismatchedUnitArgumentsAreReported(): void
    {
        $this->analyse([__DIR__ . '/data/yumemi-tag-param.php'], [
            [
                "@yumemi-param: parameter \$length expects unit_int<'meter'>, unit_int<'international_foot'> given.",
                10,
                "Unit unit_int<'international_foot'> is not assignable to unit_int<'meter'> (normalized forms differ).",
            ],
            [
                "@yumemi-param: parameter \$length expects unit_int<'meter'>, unit_int<'international_foot'> given.",
                11,
                "Unit unit_int<'international_foot'> is not assignable to unit_int<'meter'> (normalized forms differ).",
            ],
            [
                "@yumemi-param: parameter \$length expects unit_int<'meter'>, unit_int<'international_foot'> given.",
                22,
                "Unit unit_int<'international_foot'> is not assignable to unit_int<'meter'> (normalized forms differ).",
            ],
        ]);
    }
}
