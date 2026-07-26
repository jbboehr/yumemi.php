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

use PHPStan\Rules\Methods\CallMethodsRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * Assignment/parameter unit checking for Quantity<'...'> comes free via QuantityType::accepts()
 * plus PHPStan's core method-call rule — no dedicated rule needed.
 *
 * @extends RuleTestCase<CallMethodsRule>
 */
final class QuantityArgumentTypeRuleTest extends RuleTestCase
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

    public function testFootQuantityIsNotAssignableToMeterParameter(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/QuantityUnitMismatch.php'], [
            [
                'Parameter #1 $length of method jbboehr\Yumemi\Tests\PHPStan\Fixtures\QuantityUnitMismatch::expectMeters() expects Quantity<\'meter\'>, Quantity<\'international_foot\'> given.',
                21,
                'Unit Quantity<\'international_foot\'> is not assignable to Quantity<\'meter\'> (normalized forms differ).',
            ],
        ]);
    }
}
