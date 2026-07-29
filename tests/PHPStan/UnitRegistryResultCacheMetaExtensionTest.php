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

use jbboehr\Yumemi\Expr\Constant;
use jbboehr\Yumemi\Expr\Unit;
use jbboehr\Yumemi\PHPStan\UnitRegistryResultCacheMetaExtension;
use jbboehr\Yumemi\Registry\CompositeUnitRegistry;
use jbboehr\Yumemi\Registry\UnitRegistry;
use PHPUnit\Framework\TestCase;

final class UnitRegistryResultCacheMetaExtensionTest extends TestCase
{
    public function testKeyIsStable(): void
    {
        $extension = new UnitRegistryResultCacheMetaExtension(new UnitRegistry());

        $this->assertSame('yumemi.unitRegistry', $extension->getKey());
    }

    public function testHashIgnoresRegistryInsertionOrder(): void
    {
        $meter = new Unit('meter');
        $widget = new Unit('widget', new Constant(12));

        $first = new UnitRegistry([$meter, $widget], [
            'widgets' => ['type' => 'alias', 'name' => 'widgets', 'def' => 'widget'],
        ]);
        $second = new UnitRegistry([$widget, $meter], [
            'widgets' => ['def' => 'widget', 'name' => 'widgets', 'type' => 'alias'],
        ]);

        $this->assertSame($this->hash($first), $this->hash($second));
    }

    public function testHashChangesWithPrebuiltUnitDefinition(): void
    {
        $first = new UnitRegistry([new Unit('widget', new Constant(12))]);
        $second = new UnitRegistry([new Unit('widget', new Constant(13))]);

        $this->assertNotSame($this->hash($first), $this->hash($second));
    }

    public function testHashChangesWithAlias(): void
    {
        $first = new UnitRegistry([], [
            'widgets' => ['type' => 'alias', 'name' => 'widgets', 'def' => 'widget'],
        ]);
        $second = new UnitRegistry([], [
            'widgets' => ['type' => 'alias', 'name' => 'widgets', 'def' => 'gadget'],
        ]);

        $this->assertNotSame($this->hash($first), $this->hash($second));
    }

    public function testHashChangesWithPrefix(): void
    {
        $first = new PrefixUnitRegistry(['kilo' => '1000']);
        $second = new PrefixUnitRegistry(['kilo' => '1024']);

        $this->assertNotSame($this->hash($first), $this->hash($second));
    }

    public function testHashUsesEffectiveCompositeEntryAcrossStorageKinds(): void
    {
        $record = ['shared' => ['type' => 'unit', 'name' => 'shared', 'def' => '3']];
        $recordOverlay = new CompositeUnitRegistry(
            new UnitRegistry(['shared' => new Unit('shared', new Constant(2))]),
            new UnitRegistry([], $record),
        );
        $prebuilt = new Unit('shared', new Constant(3));
        $prebuiltOverlay = new CompositeUnitRegistry(
            new UnitRegistry([], ['shared' => ['type' => 'unit', 'name' => 'shared', 'def' => '2']]),
            new UnitRegistry(['shared' => $prebuilt]),
        );

        $this->assertSame($this->hash(new UnitRegistry([], $record)), $this->hash($recordOverlay));
        $this->assertSame($this->hash(new UnitRegistry(['shared' => $prebuilt])), $this->hash($prebuiltOverlay));
    }

    private function hash(UnitRegistry $registry): string
    {
        return (new UnitRegistryResultCacheMetaExtension($registry))->getHash();
    }
}

final class PrefixUnitRegistry extends UnitRegistry
{
    /**
     * @param array<string, string> $prefixes
     */
    public function __construct(
        private readonly array $prefixes,
    ) {
        parent::__construct();
    }

    /**
     * @return array<string, string>
     */
    public function prefixes(): array
    {
        return $this->prefixes;
    }
}
