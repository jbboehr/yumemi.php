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
use jbboehr\Yumemi\Catalog\PrefixDecomposition;
use jbboehr\Yumemi\Catalog\UnitKind;
use jbboehr\Yumemi\Catalog\UnitSemantics;
use jbboehr\Yumemi\Exception\UnexpectedValueException;
use jbboehr\Yumemi\Exception\UnresolvableUnitDimensionException;
use jbboehr\Yumemi\Expr\Unit;
use jbboehr\Yumemi\Registry\Udunits2UnitRegistry;
use jbboehr\Yumemi\Units;
use PHPUnit\Framework\Attributes\DataProvider;
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
        $record = $registry->findCatalogRecord('meter');

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
        ], $registry->findCatalogRecord('m'));
        $this->assertSame('international_foot', $registry->findCatalogRecord('foot')['def'] ?? null);
    }

    public function testRecordReturnsDerivedUnitDefinitionStrings(): void
    {
        $registry = new Udunits2UnitRegistry();
        $record = $registry->findCatalogRecord('international_foot');

        $this->assertNotNull($record);
        $this->assertSame('unit', $record['type']);
        $this->assertSame('12 international_inches', $record['def'] ?? null);
    }

    public function testLookupDoesNotPrecomposeUnits(): void
    {
        $registry = new Udunits2UnitRegistry();

        $this->assertNull($registry->findPrebuiltUnit('meter'));
        $this->assertNull($registry->findPrebuiltUnit('newton'));
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

        $this->assertNull($registry->findCatalogRecord('supercalifragilisticexpialidocious'));
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

    public function testDescribesUnsupportedCanonicalSynonymAndAliasRecords(): void
    {
        $registry = new Udunits2UnitRegistry();

        $logarithmic = $registry->describe('Bz');
        $affineSynonym = $registry->describe('celsius');
        $affineAlias = $registry->describe('degC');

        $this->assertNotNull($logarithmic);
        $this->assertNotNull($affineSynonym);
        $this->assertNotNull($affineAlias);
        $this->assertSame('BZ', $logarithmic->canonicalName);
        $this->assertSame(UnitSemantics::Logarithmic, $logarithmic->semantics);
        $this->assertFalse($logarithmic->supportsMultiplicativeAlgebra());
        $this->assertFalse($logarithmic->supportsConversion());
        $this->assertSame(UnitSemantics::Affine, $affineSynonym->semantics);
        $this->assertFalse($affineSynonym->supportsMultiplicativeAlgebra());
        $this->assertTrue($affineSynonym->supportsConversion());
        $this->assertSame('celsius', $affineAlias->canonicalName);
        $this->assertSame(UnitSemantics::Affine, $affineAlias->semantics);
        $this->assertFalse($affineAlias->supportsMultiplicativeAlgebra());
        $this->assertTrue($affineAlias->supportsConversion());
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

    public function testDescribesDynamicallyPrefixedNamesWithComponentProvenance(): void
    {
        $registry = new Udunits2UnitRegistry();
        $descriptor = $registry->describe('kPa');

        $this->assertNotNull($descriptor);
        $this->assertSame('kPa', $descriptor->matchedName);
        $this->assertSame('kilopascal', $descriptor->canonicalName);
        $this->assertSame(CatalogNameKind::Prefixed, $descriptor->matchedAs);
        $this->assertSame(UnitKind::Derived, $descriptor->kind);
        $this->assertSame('1e3 * pascal', $descriptor->definitionExpression);
        $this->assertSame([], $descriptor->aliases);
        $this->assertSame([], $descriptor->symbols);
        $this->assertSame([], $descriptor->plurals());
        $this->assertTrue($descriptor->isDynamicallyPrefixed());
        $this->assertTrue($descriptor->supportsMultiplicativeAlgebra());
        $this->assertTrue($descriptor->supportsConversion());
        $this->assertSame($registry->describe('pascal')?->documentation, $descriptor->documentation);

        $decomposition = $descriptor->prefixDecomposition;
        $this->assertInstanceOf(PrefixDecomposition::class, $decomposition);
        $this->assertSame('k', $decomposition->prefix->matchedName);
        $this->assertSame('kilo', $decomposition->prefix->canonicalName);
        $this->assertSame(CatalogNameKind::Symbol, $decomposition->prefix->matchedAs);
        $this->assertSame('Pa', $decomposition->unit->matchedName);
        $this->assertSame('pascal', $decomposition->unit->canonicalName);
        $this->assertSame(CatalogNameKind::Symbol, $decomposition->unit->matchedAs);
    }

    public function testPrefixedDescriptionPreservesResidualSpellingProvenance(): void
    {
        $registry = new Udunits2UnitRegistry();

        $cases = [
            'kilometer' => [CatalogNameKind::Canonical, CatalogNameKind::Canonical],
            'kilometre' => [CatalogNameKind::Canonical, CatalogNameKind::Alias],
            'kilometers' => [CatalogNameKind::Canonical, CatalogNameKind::GeneratedPlural],
            'km' => [CatalogNameKind::Symbol, CatalogNameKind::Symbol],
        ];

        foreach ($cases as $name => [$prefixKind, $unitKind]) {
            $descriptor = $registry->describe($name);

            $this->assertNotNull($descriptor, $name);
            $this->assertSame('kilometer', $descriptor->canonicalName, $name);
            $this->assertSame(CatalogNameKind::Prefixed, $descriptor->matchedAs, $name);
            $this->assertNotNull($descriptor->prefixDecomposition, $name);
            $this->assertSame($prefixKind, $descriptor->prefixDecomposition->prefix->matchedAs, $name);
            $this->assertSame($unitKind, $descriptor->prefixDecomposition->unit->matchedAs, $name);
        }
    }

    public function testExactNameWinsOverPossiblePrefixDecomposition(): void
    {
        $descriptor = (new Udunits2UnitRegistry())->describe('Pa');

        $this->assertNotNull($descriptor);
        $this->assertSame('pascal', $descriptor->canonicalName);
        $this->assertSame(CatalogNameKind::Symbol, $descriptor->matchedAs);
        $this->assertFalse($descriptor->isDynamicallyPrefixed());
        $this->assertNull($descriptor->prefixDecomposition);
    }

    public function testPrefixedUnsupportedUnitsExplainResidualSemantics(): void
    {
        $registry = new Udunits2UnitRegistry();
        $affine = $registry->describe('kilocelsius');
        $logarithmic = $registry->describe('kBz');

        $this->assertNotNull($affine);
        $this->assertNotNull($logarithmic);
        $this->assertNotNull($affine->prefixDecomposition);
        $this->assertNotNull($logarithmic->prefixDecomposition);
        $this->assertSame(UnitSemantics::UnsupportedExpression, $affine->semantics);
        $this->assertSame(UnitSemantics::UnsupportedExpression, $logarithmic->semantics);
        $this->assertFalse($affine->supportsMultiplicativeAlgebra());
        $this->assertFalse($affine->supportsConversion());
        $this->assertFalse($logarithmic->supportsMultiplicativeAlgebra());
        $this->assertFalse($logarithmic->supportsConversion());
        $this->assertSame('celsius', $affine->prefixDecomposition->unit->canonicalName);
        $this->assertSame('BZ', $logarithmic->prefixDecomposition->unit->canonicalName);
        $this->assertSame(UnitSemantics::Affine, $affine->prefixDecomposition->unit->semantics);
        $this->assertSame(UnitSemantics::Logarithmic, $logarithmic->prefixDecomposition->unit->semantics);
    }

    public function testDynamicDescriptionDoesNotInventNestedPrefixesOrCatalogEntries(): void
    {
        $registry = new Udunits2UnitRegistry();

        $this->assertNull($registry->describe('kkmeter'));
        $this->assertNull($registry->describe('kquark'));
        $this->assertNull($registry->describe('k'));
        $this->assertNotContains('kilometer', $registry->names());
        $this->assertNotNull($registry->describe('kilometer'));
    }

    public function testUnitsFacadeExposesDescriptions(): void
    {
        $units = Units::default();

        $this->assertSame('meter', $units->describe('m')?->canonicalName);
        $this->assertSame('kilometer', $units->describe('km')?->canonicalName);
        $this->assertSame('kilo', $units->describePrefix('k')?->canonicalName);
    }

    public function testCatalogLoaderDoesNotSynthesizePluralRecords(): void
    {
        $catalog = [
            'units' => [
                'widget' => ['type' => 'dimensionless', 'name' => 'widget'],
            ],
            'base' => [],
            'prefixes' => [],
        ];

        $registry = new Udunits2UnitRegistry($this->catalogFile($catalog));

        $this->assertNotNull($registry->findCatalogRecord('widget'));
        $this->assertNull($registry->findCatalogRecord('widgets'));
        $this->assertSame(['widget'], $registry->names());
    }

    public function testCatalogLoaderRejectsNonLocalPaths(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('readable local PHP file');

        new Udunits2UnitRegistry('https://example.com/udunits2.php');
    }

    public function testCatalogLoaderRejectsMissingLocalFile(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('readable local PHP file');

        new Udunits2UnitRegistry(sys_get_temp_dir() . '/yumemi-missing-catalog.php');
    }

    public function testCatalogLoaderRejectsNonArrayReturn(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('must return an array');

        new Udunits2UnitRegistry($this->catalogFile(42));
    }

    /**
     * @param array<mixed> $catalog
     */
    #[DataProvider('invalidCatalogProvider')]
    public function testCatalogLoaderRejectsInvalidShape(array $catalog, string $message): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage($message);

        new Udunits2UnitRegistry($this->catalogFile($catalog));
    }

    /**
     * @return iterable<string, array{array<mixed>, string}>
     */
    public static function invalidCatalogProvider(): iterable
    {
        yield 'missing top-level key' => [
            ['units' => [], 'base' => []],
            'missing required key: prefixes',
        ];
        yield 'unexpected top-level key' => [
            ['units' => [], 'base' => [], 'prefixes' => [], 'other' => []],
            'contains unexpected key: other',
        ];
        yield 'record name differs from lookup key' => [
            [
                'units' => ['widget' => ['type' => 'base', 'name' => 'gadget']],
                'base' => ['widget'],
                'prefixes' => [],
            ],
            'Invalid UDUNITS2 catalog unit identity: widget',
        ];
        yield 'derived record lacks definition' => [
            [
                'units' => ['widget' => ['type' => 'unit', 'name' => 'widget']],
                'base' => [],
                'prefixes' => [],
            ],
            'derived units and aliases require a definition: widget',
        ];
        yield 'base list differs from records' => [
            [
                'units' => ['widget' => ['type' => 'base', 'name' => 'widget']],
                'base' => [],
                'prefixes' => [],
            ],
            'base list does not match its base unit records',
        ];
        yield 'invalid prefix value' => [
            ['units' => [], 'base' => [], 'prefixes' => ['kilo' => 1000]],
            'prefixes must map non-empty string names to non-empty string values',
        ];
        yield 'metadata without matching prefix' => [
            [
                'units' => [],
                'base' => [],
                'prefixes' => [],
                'prefixMetadata' => [
                    'k' => ['name' => 'kilo', 'kind' => 'symbol', 'value' => '1000'],
                ],
            ],
            'Invalid UDUNITS2 prefix metadata for: k',
        ];
        yield 'null optional value' => [
            ['units' => [], 'base' => [], 'prefixes' => [], 'prefixRegex' => null],
            'prefixRegex must be a non-empty string',
        ];
        yield 'alias with derived-unit metadata' => [
            [
                'units' => [
                    'thing' => [
                        'type' => 'alias',
                        'name' => 'thing',
                        'def' => 'widget',
                        'definition' => 'not valid on an alias',
                    ],
                ],
                'base' => [],
                'prefixes' => [],
            ],
            'unit record contains an unexpected key: thing',
        ];
    }

    public function testBareUnitDimensionRequiresUnitsContextOrDefinition(): void
    {
        $this->expectException(UnresolvableUnitDimensionException::class);
        $this->expectExceptionMessage('Units::unit()');

        (new Unit('foot'))->dimension();
    }

    public function testBareUnitDimensionFallsBackToBoundUnitsContext(): void
    {
        $units = Units::default();

        $this->assertSame('length', (new Unit('foot'))->withUnits($units)->dimension()->toString());
    }

    private function catalogFile(mixed $catalog): string
    {
        $file = tempnam(sys_get_temp_dir(), 'yumemi-catalog-');
        $this->assertNotFalse($file);
        $this->tempFiles[] = $file;
        file_put_contents($file, "<?php\n\nreturn " . var_export($catalog, true) . ";\n");

        return $file;
    }
}
