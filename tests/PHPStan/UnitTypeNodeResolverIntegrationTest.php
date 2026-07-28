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

// Fixtures declare and call their own sink functions; native function reflection only resolves them if
// they exist in the process (the in-process analyser does not index functions from the analysed file).
require_once __DIR__ . '/data/unit-real-world-native.php';
require_once __DIR__ . '/data/yumemi-tag-no-extension.php';

/**
 * Full-analysis integration: runs the Yumemi extension over fixtures in-process (see
 * {@see InProcessAnalysisTestCase}) instead of spawning a phpstan subprocess per case.
 */
final class UnitTypeNodeResolverIntegrationTest extends InProcessAnalysisTestCase
{
    public function testValidUnitPhpDocHasNoErrors(): void
    {
        $this->analyse([__DIR__ . '/data/unit-phpdoc-valid.php'], []);
    }

    public function testInvalidUnitPhpDocReportsUnresolvableType(): void
    {
        $this->analyse([__DIR__ . '/data/unit-phpdoc-invalid.php'], [
            [
                'PHPDoc tag @var for variable $bad contains unresolvable type.',
                7,
                'Unit not found: mass. Did you mean: gauss, mols, rads, kats, mins?',
            ],
        ]);
    }

    public function testIncompatibleArithmeticReportsBinaryOpErrors(): void
    {
        $this->analyse([__DIR__ . '/data/unit-ops.php'], [
            [
                'Binary operation "+" between unit_int<\'meter\'> and unit_int<\'second\'> results in an error.',
                25,
            ],
            [
                'Binary operation "%" between unit_float<\'meter\'> and unit_float<\'meter\'> results in an error.',
                28,
            ],
        ]);
    }

    public function testNativeRealWorldFormulasReportOnlyTheScaleMismatch(): void
    {
        $this->analyse([__DIR__ . '/data/unit-real-world-native.php'], self::REAL_WORLD_ERRORS);
    }

    public function testQuantityBoundaryDiagnosticsHaveStableMessages(): void
    {
        $this->analyse([__DIR__ . '/data/quantity-boundary-invalid.php'], [
            [
                'Units::quantity() value unit international_foot does not match target unit meter (normalized forms differ).',
                12,
            ],
            [
                'Cannot call Quantity::to() with dimensionally incompatible units meter (length) and second (time).',
                15,
            ],
        ]);
    }

    public function testYumemiTagsAreIgnoredWithoutTheOptInConfig(): void
    {
        $this->analyse([__DIR__ . '/data/yumemi-tag-no-extension.php'], []);
    }

    /**
     * The fixture feeds each formula result to a sink whose PHPDoc parameter carries the expected unit;
     * exactly one case is wrong (international_foot is not assignable to meter — same dimension, different
     * scale after normalize).
     *
     * @var list<array{0: string, 1: int, 2?: string}>
     */
    private const REAL_WORLD_ERRORS = [
        [
            'Parameter #1 $length of function expectMetersOnly expects unit_float<\'meter\'>, unit_float<\'international_foot\'> given.',
            504,
            'Unit unit_float<\'international_foot\'> is not assignable to unit_float<\'meter\'> (normalized forms differ).',
        ],
    ];
}
