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
use jbboehr\Yumemi\Exception\UnitNotFoundException;
use jbboehr\Yumemi\Expr\Unit;
use jbboehr\Yumemi\Registry\UnitRegistry;
use jbboehr\Yumemi\Registry\UnitRegistryBuilder;
use jbboehr\Yumemi\Registry\UnitRegistryEntry;
use jbboehr\Yumemi\Units;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ResolverCacheTest extends TestCase
{
    public function testVariedQuantityParsingReleasesOldConversionResults(): void
    {
        $units = new Units(UnitRegistry::bundled());
        $dimension = $units->dimension('2 meter');
        $reference = \WeakReference::create($dimension);
        unset($dimension);
        $this->assertNotNull($reference->get());

        for ($index = 1000; $index < 1256; ++$index) {
            $quantity = $units->parseQuantity($index . ' meter');
            $this->assertSame((string) $index, $quantity->valueIn('meter')->toString());
        }
        unset($quantity);
        gc_collect_cycles();

        $this->assertNull($reference->get());
        $restored = $units->parseQuantity('2 meter');
        $this->assertSame('200', $restored->valueIn('centimeter')->toString());
        $this->assertSame($units, $restored->units());
    }

    public function testRecentlyUsedConversionStringsSurviveEntryEviction(): void
    {
        $resolver = new UnitConversionResolver(UnitRegistry::bundled());
        $oldest = $resolver->resolve('1000 meter');
        $recent = $resolver->resolve('1001 meter');
        for ($index = 1002; $index < 1256; ++$index) {
            $resolver->resolve($index . ' meter');
        }
        $this->assertSame($recent, $resolver->resolve('1001 meter'));
        $resolver->resolve('1256 meter');

        $this->assertSame($recent, $resolver->resolve('1001 meter'));
        $this->assertNotSame($oldest, $resolver->resolve('1000 meter'));
    }

    public function testConversionWeightIncludesExpandedRationals(): void
    {
        $resolver = new UnitConversionResolver(UnitRegistry::bundled());
        $first = $resolver->resolve('1e400 meter');
        for ($index = 2; $index <= 100; ++$index) {
            $resolver->resolve($index . 'e400 meter');
        }
        $again = $resolver->resolve('1e400 meter');

        $this->assertNotSame($first, $again);
        $this->assertSame('1' . str_repeat('0', 400), $again->conversion->scale->toString());
        $this->assertTrue($first->dimension->equals($again->dimension));
    }

    public function testConversionWeightIncludesCustomDimensionNames(): void
    {
        $dimensionName = 'dimension_' . str_repeat('x', 5000);
        $resolver = new UnitConversionResolver(UnitRegistryBuilder::empty()
            ->baseUnit('measure', $dimensionName)
            ->build());

        $first = $resolver->resolve('measure');
        $second = $resolver->resolve('measure');

        $this->assertNotSame($first, $second);
        $this->assertSame($dimensionName, $first->dimension->toString());
        $this->assertTrue($first->dimension->equals($second->dimension));
    }

    #[DataProvider('uncacheableConversionProvider')]
    public function testOversizedConversionsAreReturnedWithoutRetention(string $input, string $scale, string $offset): void
    {
        $resolver = new UnitConversionResolver(UnitRegistry::bundled());
        $anchor = $resolver->resolve('2 meter');
        $first = $resolver->resolve($input);
        $second = $resolver->resolve($input);

        $this->assertNotSame($first, $second);
        $this->assertSame($scale, $first->conversion->scale->toString());
        $this->assertSame($scale, $second->conversion->scale->toString());
        $this->assertSame($offset, $first->conversion->offset->toString());
        $this->assertSame($anchor, $resolver->resolve('2 meter'));
    }

    /** @return iterable<string, array{string, string, string}> */
    public static function uncacheableConversionProvider(): iterable
    {
        yield 'long source input' => [str_repeat(' ', 508) . 'meter', '1', '0'];
        yield 'large exact scale' => ['1e5000 meter', '1' . str_repeat('0', 5000), '0'];
        yield 'large source values cancel from the scale' => ['1e3000 / 1e3000 meter', '1', '0'];
        yield 'large affine offset' => ['meter @ 1e5000', '1', '1' . str_repeat('0', 5000)];
    }

    /** @param 'parse'|'unit'|'dimension'|'describe' $operation */
    #[DataProvider('lookupOperationProvider')]
    public function testFailedNamesDoNotRemainMemoizedAcrossVariedLookups(string $operation): void
    {
        $registry = new class () extends UnitRegistry {
            public int $anchorLookups = 0;

            public function __construct()
            {
                parent::__construct([new Unit('meter')]);
            }

            public function prefixes(): array
            {
                return ['k' => '1000'];
            }

            public function findEntry(string $name): ?UnitRegistryEntry
            {
                if ($name === 'absent_measurement_anchor') {
                    ++$this->anchorLookups;
                }

                return parent::findEntry($name);
            }
        };
        $units = new Units($registry);
        $lookup = function (string $name) use ($operation, $units, $registry): void {
            if ($operation === 'describe') {
                $this->assertNull($registry->describe($name));
                return;
            }

            try {
                match ($operation) {
                    'parse' => $units->parse($name),
                    'unit' => $units->unit($name),
                    'dimension' => $units->dimension($name),
                };
                self::fail('An unknown unit must still fail after cache eviction.');
            } catch (UnitNotFoundException $exception) {
                $this->assertSame($name, $exception->unitName);
            }
        };

        $lookup('kabsent_measurement_anchor');
        for ($index = 0; $index < 300; ++$index) {
            $lookup('kabsent_measurement_' . $index);
        }
        $registry->anchorLookups = 0;
        $lookup('kabsent_measurement_anchor');

        $this->assertGreaterThan(0, $registry->anchorLookups);
    }

    /** @return iterable<string, array{'parse'|'unit'|'dimension'|'describe'}> */
    public static function lookupOperationProvider(): iterable
    {
        yield 'symbolic parsing' => ['parse'];
        yield 'direct unit lookup' => ['unit'];
        yield 'conversion resolution' => ['dimension'];
        yield 'registry introspection' => ['describe'];
    }

    /** @param 'unit'|'dimension' $operation */
    #[DataProvider('cyclicLookupOperationProvider')]
    public function testCycleFailureDoesNotPoisonNameResolutionState(string $operation): void
    {
        $registry = new class () extends UnitRegistry {
            public int $cycleLookups = 0;

            public function __construct()
            {
                parent::__construct(records: [
                    'anchor' => ['type' => 'base', 'name' => 'anchor', 'dimension' => 'test_axis'],
                    'loop_a' => ['type' => 'unit', 'name' => 'loop_a', 'def' => 'loop_b'],
                    'loop_b' => ['type' => 'unit', 'name' => 'loop_b', 'def' => 'loop_a'],
                ]);
            }

            public function findEntry(string $name): ?UnitRegistryEntry
            {
                if (str_starts_with($name, 'loop_')) {
                    ++$this->cycleLookups;
                }

                return parent::findEntry($name);
            }
        };
        $units = new Units($registry);

        for ($attempt = 0; $attempt < 2; ++$attempt) {
            if ($attempt === 1) {
                $registry->cycleLookups = 0;
            }

            try {
                $operation === 'unit'
                    ? $units->unit('loop_a')
                    : $units->dimension('loop_a');
                self::fail('A cyclic definition must fail on every attempt.');
            } catch (\UnexpectedValueException) {
            }

            $this->assertSame('test_axis', $units->dimension('anchor')->toString());
        }

        $this->assertGreaterThan(0, $registry->cycleLookups);
    }

    /** @return iterable<string, array{'unit'|'dimension'}> */
    public static function cyclicLookupOperationProvider(): iterable
    {
        yield 'symbolic resolver' => ['unit'];
        yield 'conversion resolver' => ['dimension'];
    }
}
