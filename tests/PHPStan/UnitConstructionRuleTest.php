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

use PHPStan\Rules\Methods\CallMethodsRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * End-to-end: unit() construction feeds sinks with correct unit types.
 *
 * @extends RuleTestCase<CallMethodsRule>
 */
final class UnitConstructionRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        // Core rule under test; not part of PHPStan's public API.
        return self::getContainer()->getByType(CallMethodsRule::class); // @phpstan-ignore phpstanApi.classConstant
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../../extension.neon',
        ];
    }

    public function testUnitConstructionAcceptedAsNewtonForce(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/UnitConstructionValid.php'], []);
    }

    public function testUnitConstructionFootNotAcceptedAsMeter(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/UnitConstructionScaleMismatch.php'], [
            [
                'Parameter #1 $length of method jbboehr\Yumemi\Tests\PHPStan\Fixtures\UnitConstructionScaleMismatch::expectMeters() expects unit_float<\'meter\'>, unit_float<\'international_foot\'> given.',
                19,
                'Unit unit_float<\'international_foot\'> is not assignable to unit_float<\'meter\'> (normalized forms differ).',
            ],
        ]);
    }

    public function testUnitToFootToMeterAcceptedAsMeter(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/UnitToConversionCase.php'], []);
    }
}
