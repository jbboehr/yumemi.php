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

namespace jbboehr\Yumemi\Tests\Catalog;

use jbboehr\Yumemi\Catalog\AffineDeltaUnitSynthesizer;
use jbboehr\Yumemi\Catalog\UnitDefinitionClassifier;
use jbboehr\Yumemi\Catalog\UnitSemantics;
use PHPUnit\Framework\TestCase;

final class CatalogSemanticsTest extends TestCase
{
    public function testSkipsBothCompatibilityTemperatureSymbolsWithoutStoppingSynthesis(): void
    {
        $records = [
            '℃' => ['type' => 'alias', 'name' => '℃', 'def' => 'degree_Celsius', 'aliasKind' => 'symbol'],
            '℉' => ['type' => 'alias', 'name' => '℉', 'def' => 'degree_Fahrenheit', 'aliasKind' => 'symbol'],
            'degree_widget' => [
                'type' => 'unit',
                'name' => 'degree_widget',
                'def' => 'kelvin @ 100',
                'semantics' => 'affine',
            ],
        ];

        $this->assertSame([
            'delta_degree_widget' => [
                'type' => 'unit',
                'name' => 'delta_degree_widget',
                'def' => 'kelvin',
            ],
        ], AffineDeltaUnitSynthesizer::synthesize($records));
    }

    public function testAffineDefinitionCanUseAnEarlierSynthesizedDifferenceUnit(): void
    {
        $records = [
            'degree_widget' => [
                'type' => 'unit',
                'name' => 'degree_widget',
                'def' => 'kelvin @ 100',
                'semantics' => 'affine',
            ],
            'shifted_widget' => [
                'type' => 'unit',
                'name' => 'shifted_widget',
                'def' => 'delta_degree_widget @ 5',
                'semantics' => 'affine',
            ],
        ];

        $findBaseRecord = static fn (string $name): ?array => $name === 'delta_degree_widget'
            ? [
                'type' => 'unit',
                'name' => 'delta_degree_widget',
                'def' => 'kelvin @ 200',
                'semantics' => 'affine',
            ]
            : null;

        $this->assertSame([
            'delta_degree_widget' => [
                'type' => 'unit',
                'name' => 'delta_degree_widget',
                'def' => 'kelvin',
            ],
            'delta_shifted_widget' => [
                'type' => 'unit',
                'name' => 'delta_shifted_widget',
                'def' => 'delta_degree_widget',
            ],
        ], AffineDeltaUnitSynthesizer::synthesize($records, $findBaseRecord));
    }

    public function testInheritedSemanticsRecognizesDirectNonMultiplicativeDefinitions(): void
    {
        $lookups = 0;
        $findRecord = static function (string $name) use (&$lookups): ?array {
            ++$lookups;

            return null;
        };

        $this->assertSame(
            UnitSemantics::Affine,
            UnitDefinitionClassifier::inheritedSemantics(
                ['type' => 'unit', 'name' => 'degree_widget', 'def' => 'kelvin @ 100'],
                $findRecord,
            ),
        );
        $this->assertSame(
            UnitSemantics::Logarithmic,
            UnitDefinitionClassifier::inheritedSemantics(
                ['type' => 'unit', 'name' => 'bel_widget', 'def' => 'lg(re 1)'],
                $findRecord,
            ),
        );
        $this->assertSame(0, $lookups);
    }

    public function testInheritedSemanticsTreatsExactNameCyclesAsMultiplicative(): void
    {
        $records = [
            'left_widget' => ['type' => 'alias', 'name' => 'left_widget', 'def' => 'right_widget'],
            'right_widget' => ['type' => 'alias', 'name' => 'right_widget', 'def' => 'left_widget'],
        ];
        $findRecord = static fn (string $name): ?array => $records[$name] ?? null;

        $this->assertSame(
            UnitSemantics::Multiplicative,
            UnitDefinitionClassifier::inheritedSemantics($records['left_widget'], $findRecord),
        );
    }
}
