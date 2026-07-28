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

use jbboehr\Yumemi\Catalog\CatalogNameKind;
use jbboehr\Yumemi\Catalog\UnitKind;
use jbboehr\Yumemi\Exception\UnitNotFoundException;
use jbboehr\Yumemi\Expr\Unit;
use jbboehr\Yumemi\Registry\UnitRegistry;
use jbboehr\Yumemi\Registry\UnitRegistryBuilder;
use PHPUnit\Framework\TestCase;

final class UnitRegistryTest extends TestCase
{
    public function testDefaultsContainBaseAndDerivedUnits(): void
    {
        $registry = UnitRegistry::defaults();

        $this->assertSame('meter', $registry->get('meter')->toString());
        $this->assertSame('kilometer', $registry->get('kilometer')->toString());
        $this->assertFalse($registry->get('kilometer')->isBase());
    }

    public function testMissingUnitFails(): void
    {
        $registry = UnitRegistry::defaults();

        $this->expectException(UnitNotFoundException::class);
        $registry->get('league');
    }

    public function testEmptyUnitNameIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unit registry name must not be empty.');

        new UnitRegistry(['' => new Unit('anonymous')]);
    }

    public function testDuplicateUnitNameIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Duplicate unit registry name: widget');

        // A list of units keyed by their own name; two distinct units share a name.
        new UnitRegistry([new Unit('widget'), new Unit('widget')]);
    }

    public function testUnitNameConflictingWithCatalogRecordIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unit registry name conflicts with catalog record: clash');

        new UnitRegistry(
            ['clash' => new Unit('clash')],
            ['clash' => ['type' => 'base', 'name' => 'clash']],
        );
    }

    public function testEmptyCatalogRecordNameIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Catalog record name must be a non-empty string.');

        new UnitRegistry([], ['' => ['type' => 'base', 'name' => 'anonymous']]);
    }

    public function testDescribesPrebuiltUnits(): void
    {
        $descriptor = UnitRegistry::defaults()->describe('kilometer');

        $this->assertNotNull($descriptor);
        $this->assertSame('kilometer', $descriptor->matchedName);
        $this->assertSame('kilometer', $descriptor->canonicalName);
        $this->assertSame(CatalogNameKind::Canonical, $descriptor->matchedAs);
        $this->assertSame(UnitKind::Prebuilt, $descriptor->kind);
        $this->assertSame('1000 * meter', $descriptor->definitionExpression);
    }

    public function testDescribesBuilderDefinitionsAndAliases(): void
    {
        $registry = UnitRegistryBuilder::empty()
            ->define('widget = 12 * meter')
            ->alias('widgets', 'widget')
            ->build();

        $descriptor = $registry->describe('widgets');

        $this->assertNotNull($descriptor);
        $this->assertSame('widget', $descriptor->canonicalName);
        $this->assertSame(CatalogNameKind::Alias, $descriptor->matchedAs);
        $this->assertSame(UnitKind::Derived, $descriptor->kind);
        $this->assertSame('12 * meter', $descriptor->definitionExpression);
        $this->assertSame(['widgets'], $descriptor->aliases);
    }

    public function testPrebuiltAliasRemainsAvailableToGetAndIntrospection(): void
    {
        $widget = new Unit('widget');
        $registry = UnitRegistryBuilder::empty()
            ->add($widget)
            ->alias('thing', 'widget')
            ->build();

        $this->assertSame($widget, $registry->get('thing'));
        $this->assertSame('widget', $registry->describe('thing')?->canonicalName);
        $this->assertSame(['thing'], $registry->describe('widget')?->aliases);
    }

    public function testUnknownDescriptionReturnsNull(): void
    {
        $this->assertNull(UnitRegistry::defaults()->describe('league'));
    }

    public function testCircularAliasDescriptionFailsDeterministically(): void
    {
        $registry = new UnitRegistry([], [
            'left' => ['type' => 'alias', 'name' => 'left', 'def' => 'right'],
            'right' => ['type' => 'alias', 'name' => 'right', 'def' => 'left'],
        ]);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Circular catalog alias while describing unit: left');

        $registry->describe('left');
    }
}
