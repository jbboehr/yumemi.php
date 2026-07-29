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
use jbboehr\Yumemi\Catalog\UnitSemantics;
use jbboehr\Yumemi\Exception\UnsupportedUnitAlgebraException;
use jbboehr\Yumemi\Registry\Udunits2UnitRegistry;
use jbboehr\Yumemi\Units;
use PHPUnit\Framework\TestCase;
use Throwable;

/**
 * @phpstan-import-type Udunits2Catalog from \jbboehr\Yumemi\Registry\Udunits2UnitRegistry
 */
final class Udunits2CatalogSmokeTest extends TestCase
{
    private const EXPECTED_UNSUPPORTED_BY_REASON = [
        'affine' => [
            '°C',
            'degree_Celsius',
            'degrees_Celsius',
            '℃',
            'degree_C',
            'degrees_C',
            'degreeC',
            'degreesC',
            'deg_C',
            'degs_C',
            'degC',
            'degsC',
            'celsius',
            '°F',
            '℉',
            'degree_fahrenheit',
            'degrees_fahrenheit',
            'degreeF',
            'degreesF',
            'degree_F',
            'degrees_F',
            'degF',
            'degsF',
            'deg_F',
            'degs_F',
            'fahrenheit',
            'celsiuses',
            'fahrenheits',
        ],
        'logarithmic' => [
            'B',
            'Bz',
            'BZ',
            'B_SPL',
            'BW',
            'Bm',
            'BV',
            'Bv',
            'BµV',
        ],
    ];

    public function testLooksUpEverySupportedUdunits2CatalogEntry(): void
    {
        $catalog = self::catalog();
        $registry = new Udunits2UnitRegistry();
        $resolver = new UnitResolver($registry);
        $failures = [];
        $lookedUpCount = 0;

        foreach ($catalog['units'] as $name => $unit) {
            if ($this->semantics($catalog, $name) !== null) {
                continue;
            }

            try {
                if ($registry->findCatalogRecord($name) === null) {
                    $failures[] = $name . ' missing catalog record';
                    continue;
                }

                if ($resolver->resolve($name) === null) {
                    $failures[] = $name . ' failed to resolve';
                    continue;
                }

                $lookedUpCount++;
            } catch (Throwable $exception) {
                $failures[] = sprintf('%s: %s: %s', $name, $exception::class, $exception->getMessage());
            }
        }

        $this->assertSame([], $failures);
        $this->assertGreaterThan(500, $lookedUpCount);
    }

    public function testSupportedUdunits2AliasesResolveToTheirTargets(): void
    {
        $catalog = self::catalog();
        $resolver = new UnitResolver(new Udunits2UnitRegistry());
        $failures = [];
        $aliasCount = 0;

        foreach ($catalog['units'] as $name => $unit) {
            if ($unit['type'] !== 'alias' || $this->semantics($catalog, $name) !== null) {
                continue;
            }

            try {
                $alias = $resolver->resolveOrFail($name);
                $target = $resolver->resolveOrFail($unit['def']);

                if ($alias->toString() !== $target->toString()) {
                    $failures[] = sprintf(
                        '%s resolved to %s instead of %s',
                        $name,
                        $alias->toString(),
                        $target->toString(),
                    );
                    continue;
                }

                $aliasCount++;
            } catch (Throwable $exception) {
                $failures[] = sprintf('%s: %s: %s', $name, $exception::class, $exception->getMessage());
            }
        }

        $this->assertSame([], $failures);
        $this->assertGreaterThan(250, $aliasCount);
    }

    public function testUdunits2PrefixesResolveWithLongestPrefixFirst(): void
    {
        $units = Units::default();

        $this->assertSame('10 * meter', $units->normalize('dekameter')->toString());
        $this->assertSame('10 * meter', $units->normalize('dameter')->toString());
        $this->assertSame('1/1000 * meter', $units->normalize('millimeter')->toString());
        $this->assertSame('1/1000000 * meter', $units->normalize('micrometer')->toString());

        $this->assertSame('10000 * kilogram', $units->normalize('dat')->toString());
    }

    public function testNormalizesEverySupportedUdunits2Definition(): void
    {
        $catalog = self::catalog();
        $units = Units::default();
        $failures = [];
        $normalizedCount = 0;
        $unsupported = [
            'affine' => [],
            'logarithmic' => [],
        ];

        foreach ($catalog['units'] as $name => $unit) {
            if (!isset($unit['def'])) {
                continue;
            }

            $semantics = $this->semantics($catalog, $name);
            if ($semantics !== null) {
                $unsupported[$semantics][] = $name;
                continue;
            }

            try {
                $units->normalize($name);
                $normalizedCount++;
            } catch (Throwable $exception) {
                $failures[] = sprintf(
                    '%s (%s): %s: %s',
                    $name,
                    $unit['def'],
                    $exception::class,
                    $exception->getMessage(),
                );
            }
        }

        $this->assertSame([], $failures);
        $this->assertSame(784, $normalizedCount);
        $this->assertSame(self::EXPECTED_UNSUPPORTED_BY_REASON, $unsupported);
    }

    public function testKnownUnsupportedUdunits2DefinitionsFailDeliberately(): void
    {
        $units = Units::default();

        foreach (self::EXPECTED_UNSUPPORTED_BY_REASON as $semantics => $names) {
            foreach ($names as $name) {
                try {
                    $units->normalize($name);
                    self::fail('Expected unsupported-unit failure for UDUNITS2 unit: ' . $name);
                } catch (UnsupportedUnitAlgebraException $exception) {
                    $this->assertSame(UnitSemantics::from($semantics), $exception->semantics, $name);
                    $this->assertStringContainsString($semantics, $exception->getMessage(), $name);
                }
            }
        }
    }

    public function testDynamicallyPrefixedUnsupportedUnitIsDescribedButStillFailsEvaluation(): void
    {
        $units = Units::default();
        $descriptor = $units->describe('dB');

        $this->assertNotNull($descriptor);
        $this->assertSame('deciB', $descriptor->canonicalName);
        $this->assertSame(CatalogNameKind::Prefixed, $descriptor->matchedAs);
        $this->assertSame(UnitSemantics::Logarithmic, $descriptor->semantics);
        $this->assertNotNull($descriptor->prefixDecomposition);
        $this->assertSame('deci', $descriptor->prefixDecomposition->prefix->canonicalName);
        $this->assertSame('B', $descriptor->prefixDecomposition->unit->canonicalName);

        try {
            $units->normalize('dB');
            self::fail('Expected unsupported-unit failure for dB.');
        } catch (UnsupportedUnitAlgebraException $exception) {
            $this->assertSame('B', $exception->unitName);
            $this->assertSame(UnitSemantics::Logarithmic, $exception->semantics);
        }
    }

    /**
     * @phpstan-param Udunits2Catalog $catalog
     */
    private function semantics(array $catalog, string $name): ?string
    {
        return $this->semanticsInner($catalog, $name, []);
    }

    /**
     * @param array<string, true> $seen
     * @phpstan-param Udunits2Catalog $catalog
     */
    private function semanticsInner(array $catalog, string $name, array $seen): ?string
    {
        if (isset($seen[$name])) {
            return null;
        }

        $seen[$name] = true;
        $unit = $catalog['units'][$name] ?? null;

        if ($unit === null || !isset($unit['def'])) {
            return null;
        }

        if (str_contains($unit['def'], '@')) {
            return 'affine';
        }

        if (str_contains($unit['def'], 'lg(')) {
            return 'logarithmic';
        }

        if (isset($catalog['units'][$unit['def']])) {
            return $this->semanticsInner($catalog, $unit['def'], $seen);
        }

        return null;
    }

    /**
     * @phpstan-return Udunits2Catalog
     */
    private static function catalog(): array
    {
        $catalog = require __DIR__ . '/../../data/udunits2.php';

        if (!is_array($catalog)) {
            throw new \UnexpectedValueException('UDUNITS2 catalog must return an array.');
        }

        /** @phpstan-var Udunits2Catalog $catalog */
        return $catalog;
    }
}
