<?php

/**
 * Iudex Mensurarum Mysticarum『夢見』〜ＹＵＭＥＭＩ〜
 *
 * Copyright (c) anno Domini nostri Jesu Christi MMXXVI, John Boehr & contributors
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace jbboehr\Yumemi\Tests\PHPStan;

use jbboehr\Yumemi\PHPStan\InvalidUnitCallRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * Standalone diagnostics for invalid unit() / unit_to() calls (review finding #2).
 *
 * @extends RuleTestCase<InvalidUnitCallRule>
 */
final class InvalidUnitCallRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(InvalidUnitCallRule::class);
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../../extension.neon',
        ];
    }

    public function testInvalidCallsAreReported(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/InvalidUnitCalls.php'], [
            [
                'Unit not found: not_a_real_unit_xyz.',
                9,
            ],
            [
                'Unit not found: not_a_real_unit_xyz.',
                12,
            ],
            [
                'Unit not found: not_a_real_unit_xyz.',
                15,
            ],
            [
                'Cannot convert with unit_to(): units meter and second are not dimensionally compatible.',
                18,
            ],
            [
                'unit_to() value unit international_foot does not match from unit meter (normalized forms differ).',
                21,
            ],
        ]);
    }
}
