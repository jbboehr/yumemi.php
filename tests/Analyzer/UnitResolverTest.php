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

namespace jbboehr\Yumemi\Tests\Analyzer;

use jbboehr\Yumemi\Analyzer\UnitConversionResolver;
use jbboehr\Yumemi\Analyzer\UnitNameResolver;
use jbboehr\Yumemi\Analyzer\UnitResolver;
use jbboehr\Yumemi\Catalog\UnitSemantics;
use jbboehr\Yumemi\Exception\UnitNotFoundException;
use jbboehr\Yumemi\Exception\UnsupportedUnitAlgebraException;
use jbboehr\Yumemi\Expr\Unit;
use jbboehr\Yumemi\Parser\ExpressionLimitExceededException;
use jbboehr\Yumemi\Parser\SourceSpan;
use jbboehr\Yumemi\Registry\CompositeUnitRegistry;
use jbboehr\Yumemi\Registry\Udunits2UnitRegistry;
use jbboehr\Yumemi\Registry\UnitRegistry;
use jbboehr\Yumemi\Units;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class UnitResolverTest extends TestCase
{
    public function testResolvesUdunits2Aliases(): void
    {
        $resolver = new UnitResolver(new Udunits2UnitRegistry());

        $this->assertSame('meter', $resolver->resolveOrFail('m')->toString());
    }

    public function testResolvesUdunits2Prefixes(): void
    {
        $resolver = new UnitResolver(new Udunits2UnitRegistry());

        $this->assertSame('1000 * meter', $resolver->resolveOrFail('kilometer')->toString());
        $this->assertSame('1/100 * meter', $resolver->resolveOrFail('centimeter')->toString());
    }

    public function testPrefixDefinitionLimitsRetainTheCallerAndDefinitionSpans(): void
    {
        $registry = new class () extends UnitRegistry {
            public function __construct()
            {
                parent::__construct([new Unit('meter')]);
            }

            public function prefixes(): array
            {
                return ['x' => str_repeat('1', 1025)];
            }
        };
        $callerSpan = new SourceSpan(4, 10);

        try {
            (new UnitResolver($registry))->resolveOrFail('xmeter', $callerSpan);
            self::fail('Expected the prefix definition to exceed the shared expression limits.');
        } catch (ExpressionLimitExceededException $exception) {
            $this->assertSame($callerSpan, $exception->getSpan());

            $definitionFailure = $exception->getPrevious();
            $this->assertInstanceOf(ExpressionLimitExceededException::class, $definitionFailure);
            $definitionSpan = $definitionFailure->getSpan();
            $this->assertNotNull($definitionSpan);
            $this->assertSame(0, $definitionSpan->start);
            $this->assertSame(1025, $definitionSpan->end);
        }
    }

    public function testUnitNameResolverSortsImmutableRegistryPrefixesOnce(): void
    {
        $registry = new class () extends UnitRegistry {
            public int $prefixLookups = 0;

            public function __construct()
            {
                parent::__construct([new Unit('meter')]);
            }

            public function prefixes(): array
            {
                ++$this->prefixLookups;

                return ['da' => '10', 'd' => '1/10'];
            }
        };
        $resolver = new UnitNameResolver($registry);

        $this->assertSame('da', $resolver->resolve('dameter')?->prefixName);
        $this->assertSame('d', $resolver->resolve('dmeter')?->prefixName);
        $this->assertSame(1, $registry->prefixLookups);
    }

    public function testConversionResolverCachesRawUnitStrings(): void
    {
        $resolver = new UnitConversionResolver(new Udunits2UnitRegistry());
        $resolved = $resolver->resolve('meter / second');

        $this->assertSame($resolved, $resolver->resolve('meter / second'));
        $this->assertNotSame($resolved, $resolver->resolve('meter * second ^ -1'));
    }

    public function testConversionResolverCachesDerivedDataByExpressionIdentity(): void
    {
        $resolver = new UnitConversionResolver(new Udunits2UnitRegistry());
        $unit = new Unit('meter');
        $first = $resolver->resolve($unit);
        $second = $resolver->resolve($unit);
        $equivalent = $resolver->resolve(new Unit('meter'));

        $this->assertNotSame($first, $second);
        $this->assertSame($unit, $first->source);
        $this->assertSame($unit, $second->source);
        $this->assertSame($first->dimension, $second->dimension);
        $this->assertSame($first->conversion, $second->conversion);
        $this->assertNotSame($first->dimension, $equivalent->dimension);
        $this->assertNotSame($first->conversion, $equivalent->conversion);
    }

    public function testConversionResolverDoesNotRetainExpressionKeys(): void
    {
        $resolver = new UnitConversionResolver(new Udunits2UnitRegistry());
        $unit = new Unit('meter');
        $reference = \WeakReference::create($unit);
        $resolved = $resolver->resolve($unit);

        unset($unit, $resolved);
        gc_collect_cycles();

        $this->assertNull($reference->get());
    }

    public function testResolvesCatalogBackedPlurals(): void
    {
        $resolver = new UnitResolver(new Udunits2UnitRegistry());

        $this->assertSame('meter', $resolver->resolveOrFail('meters')->toString());
        $this->assertSame('meter', $resolver->resolveOrFail('metres')->toString());
        $this->assertSame('second', $resolver->resolveOrFail('seconds')->toString());
        $this->assertSame('international_inch', $resolver->resolveOrFail('inches')->toString());
        $this->assertSame('international_foot', $resolver->resolveOrFail('feet')->toString());
        $this->assertSame('hertz', $resolver->resolveOrFail('hertzes')->toString());
        $this->assertSame('kilogram', $resolver->resolveOrFail('kilograms')->toString());
    }

    public function testResolvesPrefixPlusCatalogPlural(): void
    {
        $resolver = new UnitResolver(new Udunits2UnitRegistry());

        $this->assertSame('1/100 * meter', $resolver->resolveOrFail('centimeters')->toString());
        $this->assertSame('1/1000 * meter', $resolver->resolveOrFail('millimeters')->toString());
    }

    public function testResolvesKnownSymbolsCaseSensitively(): void
    {
        $resolver = new UnitResolver(new Udunits2UnitRegistry());

        $this->assertSame('pascal', $resolver->resolveOrFail('Pa')->toString());
        $this->assertSame('meter', $resolver->resolveOrFail('m')->toString());
    }

    /**
     * @return list<array{0: string}>
     */
    public static function falseFriendProvider(): array
    {
        return [
            ['mass'],
            ['pass'],
            ['ass'],
            ['has'],
            ['bus'],
            ['METER'],
            ['gas'],
            ['lass'],
            ['percents'],
            ['avogadro_constants'],
            ['pis'],
        ];
    }

    #[DataProvider('falseFriendProvider')]
    public function testRejectsFalseFriendIdentifiers(string $name): void
    {
        $resolver = new UnitResolver(new Udunits2UnitRegistry());

        $this->assertNull($resolver->resolve($name), $name . ' should not resolve');

        $this->expectException(UnitNotFoundException::class);
        $resolver->resolveOrFail($name);
    }

    public function testUnknownUnitErrorIncludesNearMatchSuggestions(): void
    {
        $resolver = new UnitResolver(new Udunits2UnitRegistry());

        try {
            $resolver->resolveOrFail('metr');
            self::fail('Expected UnitNotFoundException');
        } catch (UnitNotFoundException $exception) {
            $this->assertSame('metr', $exception->unitName);
            $this->assertSame('meter', $exception->suggestions[0] ?? null);
            $this->assertStringContainsString('Did you mean', $exception->getMessage());
        }
    }

    public function testUnknownUnitErrorSuggestsCaseVariants(): void
    {
        $resolver = new UnitResolver(new Udunits2UnitRegistry());

        try {
            $resolver->resolveOrFail('METER');
            self::fail('Expected UnitNotFoundException for METER');
        } catch (UnitNotFoundException $exception) {
            $this->assertSame('METER', $exception->unitName);
            $this->assertSame('meter', $exception->suggestions[0] ?? null);
        }
    }

    public function testUnknownUnitSuggestionsAreIndependentOfRegistryEnumerationOrder(): void
    {
        $forward = new UnitRegistry([
            new Unit('cur'),
            new Unit('cot'),
            new Unit('cat'),
        ]);
        $reverse = new UnitRegistry([
            new Unit('cat'),
            new Unit('cot'),
            new Unit('cur'),
        ]);

        $expected = ['cat', 'cot', 'cur'];
        $this->assertSame($expected, self::suggestionsFor($forward, 'cut'));
        $this->assertSame($expected, self::suggestionsFor($reverse, 'cut'));
    }

    public function testUnknownUnitSuggestionsAreEmptyForAnEmptyRegistry(): void
    {
        try {
            (new UnitResolver(new UnitRegistry()))->resolveOrFail('missing');
            self::fail('Expected UnitNotFoundException');
        } catch (UnitNotFoundException $exception) {
            $this->assertSame([], $exception->suggestions);
            $this->assertSame('Unit not found: missing.', $exception->getMessage());
        }
    }

    public function testUnknownUnitSuggestionsAreIndependentOfCompositeLayerEnumerationOrder(): void
    {
        $first = new CompositeUnitRegistry(
            new UnitRegistry([new Unit('cur'), new Unit('cot')]),
            new UnitRegistry([new Unit('cat')]),
        );
        $second = new CompositeUnitRegistry(
            new UnitRegistry([new Unit('cat')]),
            new UnitRegistry([new Unit('cot'), new Unit('cur')]),
        );

        $expected = ['cat', 'cot', 'cur'];
        $this->assertSame($expected, self::suggestionsFor($first, 'cut'));
        $this->assertSame($expected, self::suggestionsFor($second, 'cut'));
    }

    public function testUnknownUnitSuggestionsUseNameKindBeforeByteOrderAndRemainBounded(): void
    {
        $registry = new UnitRegistry([], [
            'aone' => [
                'type' => 'alias',
                'name' => 'aone',
                'def' => 'zone',
                'aliasKind' => 'symbol',
            ],
            'wone' => [
                'type' => 'alias',
                'name' => 'wone',
                'def' => 'zone',
                'aliasKind' => 'generated_plural',
            ],
            'xone' => [
                'type' => 'alias',
                'name' => 'xone',
                'def' => 'zone',
                'aliasKind' => 'explicit_plural',
            ],
            'yone' => ['type' => 'alias', 'name' => 'yone', 'def' => 'zone'],
            'zone' => ['type' => 'base', 'name' => 'zone'],
            'vone' => ['type' => 'alias', 'name' => 'vone', 'def' => 'zone'],
        ]);

        $suggestions = self::suggestionsFor($registry, 'tone!');

        $this->assertSame(['zone', 'vone', 'yone', 'wone', 'xone'], $suggestions);
        $this->assertNotContains('aone', $suggestions);
    }

    public function testUnknownUnitSuggestionsDistinguishPrebuiltCanonicalNamesFromAliases(): void
    {
        $canonical = new Unit('zone');
        $registry = new UnitRegistry([
            'aone' => $canonical,
            'zone' => $canonical,
        ]);

        $this->assertSame(['zone', 'aone'], self::suggestionsFor($registry, 'tone!'));
    }

    public function testUnknownUnitSuggestionMessageUsesTheDeterministicOrder(): void
    {
        $registry = new UnitRegistry([
            new Unit('cur'),
            new Unit('cat'),
            new Unit('cot'),
        ]);

        try {
            (new UnitResolver($registry))->resolveOrFail('cut');
            self::fail('Expected UnitNotFoundException');
        } catch (UnitNotFoundException $exception) {
            $this->assertSame('Unit not found: cut. Did you mean: cat, cot, cur?', $exception->getMessage());
        }
    }

    public function testDoesNotApplyNestedPrefixesToInventUnits(): void
    {
        $units = Units::default();

        $this->expectException(UnitNotFoundException::class);
        $units->parse('mass');
    }

    public function testDoesNotStripPluralToUnrelatedShortAlias(): void
    {
        $resolver = new UnitResolver(new Udunits2UnitRegistry());

        // Previously "bus" stripped to "bu" (bushel). Fail-closed resolution must reject it.
        $this->assertNull($resolver->resolve('bus'));
        $this->assertSame('bushel', $resolver->resolveOrFail('bushel')->toString());
        $this->assertSame('bushel', $resolver->resolveOrFail('bu')->toString());
    }

    public function testRejectsKnownUnsupportedUnitsBeforeParsingDefinitions(): void
    {
        $registry = new UnitRegistry([], [
            'bel_widget' => [
                'type' => 'unit',
                'name' => 'bel_widget',
                'def' => 'lg(re 1 widget)',
                'semantics' => 'logarithmic',
            ],
            'BW' => ['type' => 'alias', 'name' => 'BW', 'def' => 'bel_widget'],
        ]);
        $resolver = new UnitResolver($registry);

        foreach (['bel_widget', 'BW'] as $name) {
            try {
                $resolver->resolveOrFail($name);
                self::fail('Expected unsupported-unit failure for ' . $name);
            } catch (UnsupportedUnitAlgebraException $exception) {
                $this->assertSame('bel_widget', $exception->unitName);
                $this->assertSame(UnitSemantics::Logarithmic, $exception->semantics);
                $this->assertSame('lg(re 1 widget)', $exception->definition);
                $this->assertStringContainsString(
                    'logarithmic semantics, which are not supported by multiplicative unit algebra',
                    $exception->getMessage(),
                );
            }
        }
    }

    /**
     * @return list<string>
     */
    private static function suggestionsFor(UnitRegistry $registry, string $name): array
    {
        try {
            (new UnitResolver($registry))->resolveOrFail($name);
            self::fail('Expected UnitNotFoundException');
        } catch (UnitNotFoundException $exception) {
            return $exception->suggestions;
        }
    }
}
