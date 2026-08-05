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

use jbboehr\Yumemi\PHPStan\InvalidUnitCallRule;
use jbboehr\Yumemi\PHPStan\UnitFactorFunctionDynamicReturnTypeExtension;
use jbboehr\Yumemi\PHPStan\UnitFunctionDynamicReturnTypeExtension;
use jbboehr\Yumemi\PHPStan\UnitToFunctionDynamicReturnTypeExtension;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<InvalidUnitCallRule> */
final class InvalidUnitCallRuleDynamicConfigurationTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        $container = self::getContainer();

        return new InvalidUnitCallRule(
            $container->getByType(UnitFunctionDynamicReturnTypeExtension::class),
            $container->getByType(UnitFactorFunctionDynamicReturnTypeExtension::class),
            $container->getByType(UnitToFunctionDynamicReturnTypeExtension::class),
            false,
        );
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../../extension.neon',
        ];
    }

    public function testDynamicDiagnosticCanBeDisabledWithoutSuppressingAmbiguityOrInvalidConstants(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/NativeUnitExpressionConfiguration.php'], [
            [
                'unit() unit expression resolves to multiple units after normalization: international_foot, meter.',
                15,
            ],
            [
                'Unit not found: not_a_real_unit_xyz.',
                16,
            ],
        ]);
    }
}
