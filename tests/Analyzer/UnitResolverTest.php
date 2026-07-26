<?php

/**
 * Iudex Mensurarum Mysticarum『夢見』〜ＹＵＭＥＭＩ〜
 *
 * Copyright (c) anno Domini nostri Jesu Christi MMXXVI, John Boehr & contributors
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace jbboehr\Yumemi\Tests\Analyzer;

use jbboehr\Yumemi\Analyzer\UnitResolver;
use jbboehr\Yumemi\Exception\UnitNotFoundException;
use jbboehr\Yumemi\Registry\Udunits2UnitRegistry;
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

    public function testResolvesCatalogBackedPlurals(): void
    {
        $resolver = new UnitResolver(new Udunits2UnitRegistry());

        $this->assertSame('meter', $resolver->resolveOrFail('meters')->toString());
        $this->assertSame('second', $resolver->resolveOrFail('seconds')->toString());
        $this->assertSame('international_inch', $resolver->resolveOrFail('inches')->toString());
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
}
