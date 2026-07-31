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
            $this->assertNotEmpty($exception->suggestions);
            $this->assertContains('meter', $exception->suggestions);
            $this->assertStringContainsString('Did you mean', $exception->getMessage());
        }
    }

    public function testUnknownUnitErrorSuggestsCaseVariants(): void
    {
        $resolver = new UnitResolver(new Udunits2UnitRegistry());

        try {
            $resolver->resolveOrFail('Meter');
            self::fail('Expected UnitNotFoundException for Meter');
        } catch (UnitNotFoundException $exception) {
            $this->assertSame('Meter', $exception->unitName);
            $this->assertContains('meter', $exception->suggestions);
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
}
