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

use jbboehr\Yumemi\Analyzer\UnitResolver;
use jbboehr\Yumemi\Catalog\CatalogNameKind;
use jbboehr\Yumemi\Catalog\UnitKind;
use jbboehr\Yumemi\Exception\UnsupportedUnitDimensionException;
use jbboehr\Yumemi\Expr\Unit;
use jbboehr\Yumemi\Registry\Udunits2UnitRegistry;
use jbboehr\Yumemi\Units;
use PHPUnit\Framework\TestCase;

final class Udunits2UnitRegistryTest extends TestCase
{
    /** @var list<string> */
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            @unlink($file);
        }

        $this->tempFiles = [];
    }

    public function testRecordReturnsBaseUnits(): void
    {
        $registry = new Udunits2UnitRegistry();
        $record = $registry->record('meter');

        $this->assertNotNull($record);
        $this->assertSame('base', $record['type']);
        $this->assertSame('meter', $record['name']);
    }

    public function testRecordReturnsAliases(): void
    {
        $registry = new Udunits2UnitRegistry();

        $this->assertSame([
            'type' => 'alias',
            'name' => 'm',
            'def' => 'meter',
            'aliasKind' => 'symbol',
        ], $registry->record('m'));
        $this->assertSame('international_foot', $registry->record('foot')['def'] ?? null);
    }

    public function testRecordReturnsDerivedUnitDefinitionStrings(): void
    {
        $registry = new Udunits2UnitRegistry();
        $record = $registry->record('international_foot');

        $this->assertNotNull($record);
        $this->assertSame('unit', $record['type']);
        $this->assertSame('12 international_inches', $record['def'] ?? null);
    }

    public function testLookupDoesNotPrecomposeUnits(): void
    {
        $registry = new Udunits2UnitRegistry();

        $this->assertNull($registry->lookup('meter'));
        $this->assertNull($registry->lookup('newton'));
    }

    public function testResolverBuildsUnitsFromCatalogRecords(): void
    {
        $resolver = new UnitResolver(new Udunits2UnitRegistry());

        $meter = $resolver->resolveOrFail('meter');
        $foot = $resolver->resolveOrFail('foot');
        $internationalFoot = $resolver->resolveOrFail('international_foot');

        $this->assertInstanceOf(Unit::class, $meter);
        $this->assertTrue($meter->isBase());
        $this->assertInstanceOf(Unit::class, $foot);
        $this->assertSame('international_foot', $foot->toString());
        $this->assertInstanceOf(Unit::class, $internationalFoot);
        $this->assertFalse($internationalFoot->isBase());
        $this->assertSame('12 * international_inch', $internationalFoot->definition?->toString());
    }

    public function testResolverBuiltUnitsExposeDimensionsFromDefinitionTree(): void
    {
        $resolver = new UnitResolver(new Udunits2UnitRegistry());
        $foot = $resolver->resolveOrFail('foot');
        $newton = $resolver->resolveOrFail('newton');

        $this->assertInstanceOf(Unit::class, $foot);
        $this->assertInstanceOf(Unit::class, $newton);
        $this->assertSame('length', $foot->dimension()->toString());
        $this->assertSame('length * mass / time ^ 2', $newton->dimension()->toString());
    }

    public function testUnitsFacadeUnitsExposeDimensions(): void
    {
        $units = Units::default();
        $foot = $units->unit('foot');
        $newton = $units->unit('newton');

        $this->assertInstanceOf(Unit::class, $foot);
        $this->assertInstanceOf(Unit::class, $newton);
        $this->assertSame('length', $foot->dimension()->toString());
        $this->assertSame('length * mass / time ^ 2', $newton->dimension()->toString());
    }

    public function testRecordReturnsNullForMissingUnits(): void
    {
        $registry = new Udunits2UnitRegistry();

        $this->assertNull($registry->record('supercalifragilisticexpialidocious'));
    }

    public function testDescribesCanonicalUnitAndAllNameKinds(): void
    {
        $registry = new Udunits2UnitRegistry();
        $meter = $registry->describe('m');
        $meters = $registry->describe('meters');

        $this->assertNotNull($meter);
        $this->assertSame('m', $meter->matchedName);
        $this->assertSame('meter', $meter->canonicalName);
        $this->assertSame(CatalogNameKind::Symbol, $meter->matchedAs);
        $this->assertSame(UnitKind::Base, $meter->kind);
        $this->assertNotNull($meter->documentation);
        $this->assertContains('metre', $meter->aliases);
        $this->assertContains('m', $meter->symbols);
        $this->assertContains('meters', $meter->generatedPlurals);
        $this->assertContains('metres', $meter->generatedPlurals);

        $this->assertNotNull($meters);
        $this->assertSame(CatalogNameKind::GeneratedPlural, $meters->matchedAs);
        $this->assertSame($meter->canonicalName, $meters->canonicalName);
    }

    public function testCanonicalizationDoesNotNormalizeUnitDefinition(): void
    {
        $descriptor = (new Udunits2UnitRegistry())->describe('foot');

        $this->assertNotNull($descriptor);
        $this->assertSame('international_foot', $descriptor->canonicalName);
        $this->assertSame(CatalogNameKind::Alias, $descriptor->matchedAs);
        $this->assertSame(UnitKind::Derived, $descriptor->kind);
        $this->assertSame('12 international_inches', $descriptor->definitionExpression);
    }

    public function testDescribesPrefixNamesAndSymbols(): void
    {
        $registry = new Udunits2UnitRegistry();
        $name = $registry->describePrefix('kilo');
        $symbol = $registry->describePrefix('k');

        $this->assertNotNull($name);
        $this->assertNotNull($symbol);
        $this->assertSame('kilo', $name->canonicalName);
        $this->assertSame(CatalogNameKind::Canonical, $name->matchedAs);
        $this->assertSame('kilo', $symbol->canonicalName);
        $this->assertSame(CatalogNameKind::Symbol, $symbol->matchedAs);
        $this->assertSame('1e3', $symbol->definitionExpression);
        $this->assertNull($registry->describePrefix('not_a_prefix'));
        $this->assertNull($registry->describe('kquark'));
    }

    public function testUnitsFacadeExposesDescriptions(): void
    {
        $units = Units::default();

        $this->assertSame('meter', $units->describe('m')?->canonicalName);
        $this->assertSame('kilo', $units->describePrefix('k')?->canonicalName);
    }

    public function testCatalogLoaderDoesNotSynthesizePluralRecords(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'yumemi-catalog-');
        $this->assertNotFalse($file);
        $this->tempFiles[] = $file;

        $catalog = [
            'units' => [
                'widget' => ['type' => 'dimensionless', 'name' => 'widget'],
            ],
            'base' => [],
            'prefixes' => [],
        ];
        file_put_contents($file, '<?php return ' . var_export($catalog, true) . ';');

        $registry = new Udunits2UnitRegistry($file);

        $this->assertNotNull($registry->record('widget'));
        $this->assertNull($registry->record('widgets'));
        $this->assertSame(['widget'], $registry->names());
    }

    public function testBareUnitDimensionRequiresUnitsContextOrDefinition(): void
    {
        $this->expectException(UnsupportedUnitDimensionException::class);
        $this->expectExceptionMessage('Units::unit()');

        (new Unit('foot'))->dimension();
    }

    public function testBareUnitDimensionFallsBackToBoundUnitsContext(): void
    {
        $units = Units::default();

        $this->assertSame('length', (new Unit('foot'))->withUnits($units)->dimension()->toString());
    }
}
