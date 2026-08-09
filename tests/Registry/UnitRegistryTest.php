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
use jbboehr\Yumemi\Catalog\UnitSemantics;
use jbboehr\Yumemi\Exception\UnexpectedValueException;
use jbboehr\Yumemi\Expr\Constant;
use jbboehr\Yumemi\Expr\Product;
use jbboehr\Yumemi\Expr\Unit;
use jbboehr\Yumemi\Registry\CompositeUnitRegistry;
use jbboehr\Yumemi\Registry\UnitRegistry;
use jbboehr\Yumemi\Registry\UnitRegistryBuilder;
use jbboehr\Yumemi\Registry\UnitRegistryEntry;
use jbboehr\Yumemi\Units;
use PHPUnit\Framework\TestCase;

final class UnitRegistryTest extends TestCase
{
    public function testDefaultsContainBaseAndDerivedUnits(): void
    {
        $registry = UnitRegistry::defaults();

        $this->assertSame('meter', $registry->findPrebuiltUnit('meter')?->toString());
        $this->assertSame('kilometer', $registry->findPrebuiltUnit('kilometer')?->toString());
        $this->assertFalse($registry->findPrebuiltUnit('kilometer')->isBase());
    }

    public function testBuiltinDefinitionsHaveExactScales(): void
    {
        $units = new Units(UnitRegistry::defaults());

        $this->assertSame('381/1250', $units->quantity(1, 'foot')->valueIn('meter')->toString());
        $this->assertSame('1000', $units->quantity(1, 'kilometer')->valueIn('meter')->toString());
        $this->assertSame('60', $units->quantity(1, 'minute')->valueIn('second')->toString());
        $this->assertCount(5, UnitRegistry::builtinDefaultUnits());
    }

    public function testNamesAreUniqueAndDenselyIndexed(): void
    {
        $meter = new Unit('meter');
        $registry = new UnitRegistry(
            ['meter' => $meter, 'metre' => $meter],
            [
                'metre' => ['type' => 'alias', 'name' => 'metre', 'def' => 'meter'],
                'second' => ['type' => 'base', 'name' => 'second'],
            ],
        );

        $this->assertSame(['meter', 'metre', 'second'], $registry->names());
    }

    public function testEffectiveEntryPreservesBothRepresentationsFromOneLayer(): void
    {
        $meter = new Unit('meter');
        $registry = new UnitRegistry(
            ['metre' => $meter],
            ['metre' => ['type' => 'alias', 'name' => 'metre', 'def' => 'meter']],
        );

        $entry = $registry->findEntry('metre');

        $this->assertNotNull($entry);
        $this->assertSame($meter, $entry->prebuiltUnit);
        $this->assertSame('meter', $entry->catalogRecord['def'] ?? null);
        $this->assertNull($registry->findEntry('missing'));
    }

    public function testEffectiveEntryBridgesLegacyLookupOverrides(): void
    {
        $meter = new Unit('meter');
        $registry = new class ($meter) extends UnitRegistry {
            public function __construct(
                private readonly Unit $meter,
            ) {
                parent::__construct();
            }

            public function findPrebuiltUnit(string $name): ?Unit
            {
                return $name === 'metre' ? $this->meter : null;
            }

            public function findCatalogRecord(string $name): ?array
            {
                return $name === 'metre'
                    ? ['type' => 'alias', 'name' => 'metre', 'def' => 'meter']
                    : null;
            }
        };

        $entry = $registry->findEntry('metre');

        $this->assertNotNull($entry);
        $this->assertSame($meter, $entry->prebuiltUnit);
        $this->assertSame('meter', $entry->catalogRecord['def'] ?? null);
    }

    public function testEffectiveEntryRequiresOneRepresentation(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('must contain at least one representation');

        new UnitRegistryEntry(null, null);
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

    public function testPrimitiveDimensionMetadataRequiresOneBaseUnit(): void
    {
        try {
            new UnitRegistry([], [
                'derived' => [
                    'type' => 'unit',
                    'name' => 'derived',
                    'def' => '1',
                    'dimension' => 'currency',
                ],
            ]);
            self::fail('Expected primitive metadata on a derived record to be rejected.');
        } catch (\InvalidArgumentException $exception) {
            $this->assertStringContainsString('Only base unit records', $exception->getMessage());
        }

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('multiple base units');

        new UnitRegistry([], [
            'USD' => ['type' => 'base', 'name' => 'USD', 'dimension' => 'currency'],
            'EUR' => ['type' => 'base', 'name' => 'EUR', 'dimension' => 'currency'],
        ]);
    }

    public function testDescribesPrebuiltUnits(): void
    {
        $descriptor = UnitRegistry::defaults()->describe('kilometer');

        $this->assertNotNull($descriptor);
        $this->assertSame('kilometer', $descriptor->matchedName);
        $this->assertSame('kilometer', $descriptor->canonicalName);
        $this->assertSame(CatalogNameKind::Canonical, $descriptor->matchedAs);
        $this->assertSame(UnitKind::Derived, $descriptor->kind);
        $this->assertSame('1000 * meter', $descriptor->definitionExpression);
    }

    public function testClassifiesPrebuiltUnitsByDefinition(): void
    {
        $registry = new UnitRegistry([
            new Unit('widget'),
            new Unit('dozen', new Product([new Constant(3), new Constant(4)])),
            new Unit('widget_pair', new Product([new Constant(2), new Unit('widget')])),
        ]);

        $this->assertSame(UnitKind::Base, $registry->describe('widget')?->kind);
        $this->assertSame(UnitKind::Dimensionless, $registry->describe('dozen')?->kind);
        $this->assertSame(UnitKind::Derived, $registry->describe('widget_pair')?->kind);
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

    public function testDescriptionClassifiesAndSortsEveryNameKind(): void
    {
        $registry = new UnitRegistry([], [
            'widget' => [
                'type' => 'unit',
                'name' => 'widget',
                'def' => '2 * meter',
                'documentation' => 'preferred documentation',
                'definition' => 'fallback documentation',
                'comment' => 'a comment',
            ],
            'zeta' => ['type' => 'alias', 'name' => 'zeta', 'def' => 'widget'],
            'alpha' => ['type' => 'alias', 'name' => 'alpha', 'def' => 'widget'],
            'Z' => ['type' => 'alias', 'name' => 'Z', 'def' => 'widget', 'aliasKind' => 'symbol'],
            'A' => ['type' => 'alias', 'name' => 'A', 'def' => 'widget', 'aliasKind' => 'symbol'],
            'widgetz' => [
                'type' => 'alias',
                'name' => 'widgetz',
                'def' => 'widget',
                'aliasKind' => 'explicit_plural',
            ],
            'widgets' => [
                'type' => 'alias',
                'name' => 'widgets',
                'def' => 'widget',
                'aliasKind' => 'explicit_plural',
            ],
            'widgetzz' => [
                'type' => 'alias',
                'name' => 'widgetzz',
                'def' => 'widget',
                'aliasKind' => 'generated_plural',
            ],
            'widgetses' => [
                'type' => 'alias',
                'name' => 'widgetses',
                'def' => 'widget',
                'aliasKind' => 'generated_plural',
            ],
            'radian' => ['type' => 'dimensionless', 'name' => 'radian', 'def' => '1'],
        ]);

        $descriptor = $registry->describe('widget');
        $this->assertNotNull($descriptor);
        $this->assertSame(UnitKind::Derived, $descriptor->kind);
        $this->assertSame('preferred documentation', $descriptor->documentation);
        $this->assertSame('a comment', $descriptor->comment);
        $this->assertSame(['alpha', 'zeta'], $descriptor->aliases);
        $this->assertSame(['A', 'Z'], $descriptor->symbols);
        $this->assertSame(['widgets', 'widgetz'], $descriptor->explicitPlurals);
        $this->assertSame(['widgetses', 'widgetzz'], $descriptor->generatedPlurals);
        $this->assertSame(['widgets', 'widgetz', 'widgetses', 'widgetzz'], $descriptor->plurals());
        $this->assertSame(CatalogNameKind::Symbol, $registry->describe('A')?->matchedAs);
        $this->assertSame(UnitKind::Dimensionless, $registry->describe('radian')?->kind);
    }

    public function testDescriptionExposesUnsupportedCanonicalMetadataThroughAliases(): void
    {
        $registry = new UnitRegistry([], [
            'kelvin' => [
                'type' => 'base',
                'name' => 'kelvin',
            ],
            'degree_widget' => [
                'type' => 'unit',
                'name' => 'degree_widget',
                'def' => 'kelvin @ 273.15',
                'semantics' => 'affine',
            ],
            'widget_temperature' => [
                'type' => 'alias',
                'name' => 'widget_temperature',
                'def' => 'degree_widget',
            ],
        ]);

        $canonical = $registry->describe('degree_widget');
        $alias = $registry->describe('widget_temperature');

        $this->assertNotNull($canonical);
        $this->assertNotNull($alias);
        $this->assertFalse($canonical->supportsMultiplicativeAlgebra());
        $this->assertFalse($alias->supportsMultiplicativeAlgebra());
        $this->assertTrue($canonical->supportsConversion());
        $this->assertTrue($alias->supportsConversion());
        $this->assertSame(UnitSemantics::Affine, $canonical->semantics);
        $this->assertSame(UnitSemantics::Affine, $alias->semantics);
        $this->assertSame('degree_widget', $alias->canonicalName);
        $this->assertSame(UnitKind::Derived, $alias->kind);
    }

    public function testPrebuiltAliasRemainsAvailableToLookupAndIntrospection(): void
    {
        $widget = new Unit('widget');
        $registry = UnitRegistryBuilder::empty()
            ->add($widget)
            ->alias('thing', 'widget')
            ->build();

        $this->assertSame($widget, $registry->findPrebuiltUnit('thing'));
        $this->assertSame('widget', $registry->describe('thing')?->canonicalName);
        $this->assertSame(['thing'], $registry->describe('widget')?->aliases);
    }

    public function testDynamicallyBuiltNameIndexIsReusedAcrossDescriptions(): void
    {
        $registry = new class () extends UnitRegistry {
            public int $nameLookups = 0;

            public function __construct()
            {
                parent::__construct([], [
                    'thing' => ['type' => 'alias', 'name' => 'thing', 'def' => 'widget'],
                    'widget' => ['type' => 'base', 'name' => 'widget'],
                ]);
            }

            public function names(): array
            {
                ++$this->nameLookups;

                return parent::names();
            }
        };

        $this->assertSame(['thing'], $registry->describe('widget')?->aliases);
        $this->assertSame('widget', $registry->describe('thing')?->canonicalName);
        $this->assertSame(1, $registry->nameLookups);
    }

    public function testGeneratedNameIndexRejectsUnresolvableAliases(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('unresolvable alias: thing');

        UnitRegistry::indexCatalogRecords([
            'thing' => ['type' => 'alias', 'name' => 'thing', 'def' => 'missing'],
        ]);
    }

    public function testDynamicallyBuiltNameIndexSortsUnresolvableAliases(): void
    {
        $registry = new class () extends UnitRegistry {
            public function __construct()
            {
                parent::__construct([], [
                    'zeta' => ['type' => 'alias', 'name' => 'zeta', 'def' => 'missing'],
                    'alpha' => ['type' => 'alias', 'name' => 'alpha', 'def' => 'missing'],
                ]);
            }

            /** @return list<string> */
            public function unresolvedNames(): array
            {
                return $this->unitNameIndex()['unresolved'];
            }
        };

        $this->assertSame(['alpha', 'zeta'], $registry->unresolvedNames());
    }

    public function testCatalogPrimitiveDimensionIndexIsDeterministic(): void
    {
        $this->assertSame([
            'currency' => 'USD',
            'image_sample' => 'pixel',
        ], UnitRegistry::indexCatalogPrimitiveDimensions([
            'dozen' => ['type' => 'dimensionless', 'name' => 'dozen'],
            'pixel' => ['type' => 'base', 'name' => 'pixel', 'dimension' => 'image_sample'],
            'USD' => ['type' => 'base', 'name' => 'USD', 'dimension' => 'currency'],
        ]));
    }

    public function testDynamicallyBuiltPrimitiveDimensionIndexIsReusedAcrossCompositions(): void
    {
        $base = new class () extends UnitRegistry {
            public int $nameLookups = 0;

            public function __construct()
            {
                parent::__construct([], [
                    'USD' => ['type' => 'base', 'name' => 'USD', 'dimension' => 'currency'],
                ]);
            }

            public function names(): array
            {
                ++$this->nameLookups;

                return parent::names();
            }
        };
        $overlay = new UnitRegistry();

        new CompositeUnitRegistry($base, $overlay);
        $firstCompositionLookups = $base->nameLookups;
        new CompositeUnitRegistry($base, $overlay);

        $this->assertSame($firstCompositionLookups + 1, $base->nameLookups);
    }

    public function testUnknownDescriptionReturnsNull(): void
    {
        $this->assertNull(UnitRegistry::defaults()->describe('league'));
    }

    public function testBaseRegistryExposesPrefixDescriptionApi(): void
    {
        $this->assertNull(UnitRegistry::defaults()->describePrefix('kilo'));
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

    public function testCircularPrebuiltAliasDescriptionFailsDeterministically(): void
    {
        $registry = new UnitRegistry([
            'left' => new Unit('right'),
            'right' => new Unit('left'),
        ]);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Circular catalog alias while describing unit: left');

        $registry->describe('left');
    }

    public function testDescriptionSkipsUnrelatedBrokenAliases(): void
    {
        $registry = new UnitRegistry([], [
            'widget' => ['type' => 'base', 'name' => 'widget'],
            'left' => ['type' => 'alias', 'name' => 'left', 'def' => 'right'],
            'right' => ['type' => 'alias', 'name' => 'right', 'def' => 'left'],
        ]);

        $descriptor = $registry->describe('widget');

        $this->assertNotNull($descriptor);
        $this->assertSame('widget', $descriptor->canonicalName);
        $this->assertSame([], $descriptor->aliases);
    }

    public function testDescriptionRejectsAliasWithoutTarget(): void
    {
        $registry = new UnitRegistry([], [
            'orphan' => ['type' => 'alias', 'name' => 'orphan'],
        ]);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('missing target');

        $registry->describe('orphan');
    }

    public function testDescriptionRejectsUnknownAliasTarget(): void
    {
        $registry = new UnitRegistry([], [
            'orphan' => ['type' => 'alias', 'name' => 'orphan', 'def' => 'missing'],
        ]);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('target is unknown');

        $registry->describe('orphan');
    }

    public function testPrebuiltAliasUsesCanonicalCatalogMetadata(): void
    {
        $widget = new Unit('widget', new Constant(99));
        $registry = new UnitRegistry(
            ['thing' => $widget],
            ['widget' => ['type' => 'unit', 'name' => 'widget', 'def' => '2 * meter']],
        );

        $descriptor = $registry->describe('thing');

        $this->assertNotNull($descriptor);
        $this->assertSame('widget', $descriptor->canonicalName);
        $this->assertSame(UnitKind::Derived, $descriptor->kind);
        $this->assertSame('2 * meter', $descriptor->definitionExpression);
    }
}
