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

namespace jbboehr\Yumemi\Tests\Internal;

use jbboehr\Yumemi\Exception\InvalidArgumentException;
use jbboehr\Yumemi\Internal\BoundedLruCache;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class BoundedLruCacheTest extends TestCase
{
    public function testEvictsTheLeastRecentlyUsedEntryAtTheEntryLimit(): void
    {
        $cache = new BoundedLruCache(2, 100, 100);
        $first = new \stdClass();
        $second = new \stdClass();
        $third = new \stdClass();

        $cache->put('first', $first, 1);
        $cache->put('second', $second, 1);
        $this->assertSame($first, $cache->get('first'));
        $cache->put('third', $third, 1);

        $this->assertNull($cache->get('second'));
        $this->assertSame($first, $cache->get('first'));
        $this->assertSame($third, $cache->get('third'));
    }

    public function testEvictsOldestEntriesUntilTheWeightFits(): void
    {
        $cache = new BoundedLruCache(10, 5, 5);
        $first = new \stdClass();
        $second = new \stdClass();
        $third = new \stdClass();

        $cache->put('first', $first, 2);
        $cache->put('second', $second, 2);
        $this->assertSame($first, $cache->get('first'));
        $cache->put('third', $third, 3);

        $this->assertNull($cache->get('second'));
        $this->assertSame($first, $cache->get('first'));
        $this->assertSame($third, $cache->get('third'));
    }

    public function testContinuesEvictingWhenOneWeightedEvictionIsInsufficient(): void
    {
        $cache = new BoundedLruCache(10, 5, 5);
        $first = new \stdClass();
        $third = new \stdClass();

        $cache->put('first', $first, 3);
        $cache->put('second', new \stdClass(), 1);
        $this->assertSame($first, $cache->get('first'));
        $cache->put('third', $third, 3);

        $this->assertNull($cache->get('first'));
        $this->assertNull($cache->get('second'));
        $this->assertSame($third, $cache->get('third'));
    }

    public function testReplacingAnEntryUpdatesItsWeightAndRecency(): void
    {
        $cache = new BoundedLruCache(10, 8, 8);
        $replacement = new \stdClass();

        $cache->put('first', new \stdClass(), 3);
        $cache->put('second', new \stdClass(), 2);
        $cache->put('third', new \stdClass(), 1);
        $cache->put('second', $replacement, 3);
        $cache->put('fourth', new \stdClass(), 2);

        $this->assertNull($cache->get('first'));
        $this->assertSame($replacement, $cache->get('second'));
        $this->assertNotNull($cache->get('third'));
        $this->assertNotNull($cache->get('fourth'));
    }

    public function testRetainsAnEntryWhoseWeightEqualsTheLimit(): void
    {
        $cache = new BoundedLruCache(2, 5, 5);
        $value = new \stdClass();

        $cache->put('exact', $value, 5);

        $this->assertSame($value, $cache->get('exact'));
    }

    public function testAnOverweightEntryIsNotRetainedOrAllowedToEvictOtherEntries(): void
    {
        $cache = new BoundedLruCache(2, 10, 5);
        $retained = new \stdClass();

        $cache->put('retained', $retained, 2);
        $cache->put('overweight', new \stdClass(), 6);

        $this->assertSame($retained, $cache->get('retained'));
        $this->assertNull($cache->get('overweight'));
    }

    public function testAnOverweightReplacementRemovesThePreviousValue(): void
    {
        $cache = new BoundedLruCache(2, 3, 5);

        $cache->put('entry', new \stdClass(), 2);
        $cache->put('entry', new \stdClass(), 4);

        $this->assertNull($cache->get('entry'));
    }

    public function testAnEntryOverThePerEntryLimitDoesNotEvictOtherEntries(): void
    {
        $cache = new BoundedLruCache(2, 3, 5);
        $retained = new \stdClass();

        $cache->put('retained', $retained, 2);
        $cache->put('oversized', new \stdClass(), 4);

        $this->assertSame($retained, $cache->get('retained'));
        $this->assertNull($cache->get('oversized'));
    }

    public function testRetainsAnEntryWhoseWeightEqualsThePerEntryLimit(): void
    {
        $cache = new BoundedLruCache(2, 3, 5);
        $value = new \stdClass();

        $cache->put('exact', $value, 3);

        $this->assertSame($value, $cache->get('exact'));
    }

    public function testNumericStringKeysRemainAvailable(): void
    {
        $cache = new BoundedLruCache(2, 2, 2);
        $value = new \stdClass();

        $cache->put('2', $value, 1);

        $this->assertSame($value, $cache->get('2'));
    }

    #[DataProvider('invalidConfigurationProvider')]
    public function testRejectsInvalidConfiguration(
        int $maximumEntries,
        int $maximumEntryWeight,
        int $maximumWeight,
    ): void {
        $this->expectException(InvalidArgumentException::class);

        $cache = new BoundedLruCache($maximumEntries, $maximumEntryWeight, $maximumWeight);
        $this->assertNull($cache->get('unused'));
    }

    /**
     * @return iterable<string, array{int, int, int}>
     */
    public static function invalidConfigurationProvider(): iterable
    {
        yield 'zero entries' => [0, 1, 1];
        yield 'negative entries' => [-1, 1, 1];
        yield 'zero entry weight' => [1, 0, 1];
        yield 'negative entry weight' => [1, -1, 1];
        yield 'zero weight' => [1, 1, 0];
        yield 'negative weight' => [1, 1, -1];
    }

    public function testRejectsInvalidEntryWeight(): void
    {
        $cache = new BoundedLruCache(1, 1, 1);

        $this->expectException(InvalidArgumentException::class);
        $cache->put('invalid', new \stdClass(), 0);
    }
}
