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

namespace jbboehr\Yumemi\Tests\Registry;

use jbboehr\Yumemi\Expr\Unit;
use jbboehr\Yumemi\Registry\CompositeUnitRegistry;
use jbboehr\Yumemi\Registry\UnitRegistry;
use PHPUnit\Framework\TestCase;

final class CompositeUnitRegistryTest extends TestCase
{
    public function testOverlayWinsForLookupAndRecordWithBaseFallback(): void
    {
        $baseShared = new Unit('shared');
        $overlayShared = new Unit('shared');
        $baseOnly = new Unit('base_only');
        $overlayOnly = new Unit('overlay_only');

        $composite = new CompositeUnitRegistry(
            $this->registry(
                ['shared' => $baseShared, 'base_only' => $baseOnly],
                ['rec_shared' => ['type' => 'alias', 'name' => 'rec_shared', 'def' => 'BASE']],
                [],
            ),
            $this->registry(
                ['shared' => $overlayShared, 'overlay_only' => $overlayOnly],
                ['rec_shared' => ['type' => 'alias', 'name' => 'rec_shared', 'def' => 'OVERLAY']],
                [],
            ),
        );

        // Overlay wins on conflict; base is the fallback for names only it provides.
        $this->assertSame($overlayShared, $composite->lookup('shared'));
        $this->assertSame($baseOnly, $composite->lookup('base_only'));
        $this->assertSame($overlayOnly, $composite->lookup('overlay_only'));
        $this->assertNull($composite->lookup('missing'));

        $this->assertSame('OVERLAY', $composite->record('rec_shared')['def'] ?? null);
    }

    public function testNamesAreTheDeduplicatedUnion(): void
    {
        $composite = new CompositeUnitRegistry(
            $this->registry(['shared' => new Unit('shared'), 'base_only' => new Unit('base_only')], [], []),
            $this->registry(['shared' => new Unit('shared'), 'overlay_only' => new Unit('overlay_only')], [], []),
        );

        $names = $composite->names();

        $this->assertContains('shared', $names);
        $this->assertContains('base_only', $names);
        $this->assertContains('overlay_only', $names);
        // "shared" appears in both layers but only once in the union.
        $this->assertCount(1, array_keys($names, 'shared', true));
    }

    public function testPrefixesMergeWithOverlayWinning(): void
    {
        $composite = new CompositeUnitRegistry(
            $this->registry([], [], ['kilo' => '1000', 'p_shared' => 'BASE']),
            $this->registry([], [], ['milli' => '0.001', 'p_shared' => 'OVERLAY']),
        );

        $this->assertSame(
            ['kilo' => '1000', 'p_shared' => 'OVERLAY', 'milli' => '0.001'],
            $composite->prefixes(),
        );
    }

    /**
     * @param array<string, Unit>                                                     $units
     * @param array<string, array{type: 'base'|'dimensionless'|'unit'|'alias', name: string, def?: string}> $records
     * @param array<string, string>                                                   $prefixes
     */
    private function registry(array $units, array $records, array $prefixes): UnitRegistry
    {
        return new class ($units, $records, $prefixes) extends UnitRegistry {
            /**
             * @param array<string, Unit> $units
             * @param array<string, array{type: 'base'|'dimensionless'|'unit'|'alias', name: string, def?: string}> $records
             * @param array<string, string> $prefixes
             */
            public function __construct(
                array $units,
                array $records,
                private readonly array $prefixes,
            ) {
                parent::__construct($units, $records);
            }

            public function prefixes(): array
            {
                return $this->prefixes;
            }
        };
    }
}
