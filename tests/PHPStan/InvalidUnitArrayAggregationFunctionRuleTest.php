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

use jbboehr\Yumemi\PHPStan\InvalidUnitArrayAggregationFunctionRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<InvalidUnitArrayAggregationFunctionRule>
 */
final class InvalidUnitArrayAggregationFunctionRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(InvalidUnitArrayAggregationFunctionRule::class);
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/../../extension.neon'];
    }

    public function testInvalidNativeUnitAggregationsAreReported(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/InvalidUnitArrayAggregationFunctionCalls.php'], [
            [
                'Cannot call array_sum() with units international_foot and meter because they are not definitionally equivalent.',
                13,
            ],
            [
                'Cannot call array_sum() with unit-bearing and unbranded values; every possible summand needs one definitionally equivalent unit.',
                14,
            ],
            [
                'Cannot call array_sum() with units international_foot and meter because they are not definitionally equivalent.',
                18,
            ],
            [
                'Cannot call array_sum() with unit-bearing and unbranded values; every possible summand needs one definitionally equivalent unit.',
                22,
            ],
            [
                'Cannot infer a unit for array_product() unless every possible input array has a sealed, statically known shape.',
                34,
            ],
            [
                'Cannot call array_product() with a unit-bearing array unless every possible factor is an explicit int or float; cast numeric strings before multiplication.',
                38,
            ],
            [
                'Cannot infer array_product() because its product unit exceeds the supported exponent range.',
                40,
            ],
        ]);
    }
}
