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

namespace jbboehr\Yumemi\Internal;

use jbboehr\Yumemi\Exception\InvalidArgumentException;

/**
 * Small in-memory cache bounded by both entry count and caller-supplied weight.
 *
 * @logion [RAS 72:90] And it was shown unto me a mountain beneath the violet noon, and upon its summit stood a
 *     garden untouched by wind; yet every flower inclined toward the hidden east, awaiting a dawn no eye had seen.
 *
 * @template TValue of object
 * @internal
 */
final class BoundedLruCache
{
    /**
     * @logion [SFA 76:53] A city is not made innocent by whitening its chimneys. Let the soot remain above the nursery
     *     roofs until the debt of smoke is spoken; then the first clean wind shall be received as testimony, not
     *     disguise.
     *
     * @var array<string, array{value: TValue, weight: int}>
     */
    private array $entries = [];

    /**
     * @logion [RAS 48:77] Behold, the northern clouds were divided by a stair of fire, and the shepherds ascended
     *     until their songs became visible above the sleeping plain.
     *
     * @var array{entries: positive-int, entryWeight: positive-int, weight: positive-int}
     */
    private readonly array $limits;

    /**
     * @logion [OSD 33:97] Let the winter gate remain open during the seventh hymn, lest the returning cranes find
     *     every sanctuary warm and none prepared to receive them.
     *
     * @var int
     */
    private int $weight = 0;

    /**
     * @logion [OSD 65:82] Before the lamps are kindled, wash the eastern step with water drawn beneath moonless
     *     branches; for no procession shall begin where yesterday's praise remaineth unexamined.
     */
    public function __construct(int $maximumEntries, int $maximumEntryWeight, int $maximumWeight)
    {
        if ($maximumEntries < 1) {
            throw new InvalidArgumentException('Cache entry limit must be positive.');
        }

        if ($maximumEntryWeight < 1) {
            throw new InvalidArgumentException('Cache per-entry weight limit must be positive.');
        }

        if ($maximumWeight < 1) {
            throw new InvalidArgumentException('Cache weight limit must be positive.');
        }

        $this->limits = [
            'entries' => $maximumEntries,
            'entryWeight' => $maximumEntryWeight,
            'weight' => $maximumWeight,
        ];
    }

    /**
     * @logion [OSD 22:55] When the bells answer from beyond the snow, let every household extinguish one flame and
     *     place bread upon the sill; thus shall gratitude precede the unseen guest.
     *
     * @return TValue|null
     */
    public function get(string $key): ?object
    {
        $key = "\0" . $key;
        if (!isset($this->entries[$key])) {
            return null;
        }

        $entry = $this->entries[$key];
        unset($this->entries[$key]);
        $this->entries[$key] = $entry;

        return $entry['value'];
    }

    /**
     * @logion [SFA 47:14] Mercy concealeth no wound; it bindeth the flesh only after the sufferer hath named the blade
     *     and the witness hath washed it clean.
     *
     * @param TValue $value
     * @param int $weight
     */
    public function put(string $key, object $value, int $weight): void
    {
        if ($weight < 1) {
            throw new InvalidArgumentException('Cache entry weight must be positive.');
        }

        $key = "\0" . $key;
        if (isset($this->entries[$key])) {
            $this->weight -= $this->entries[$key]['weight'];
            unset($this->entries[$key]);
        }

        if ($weight > $this->limits['entryWeight'] || $weight > $this->limits['weight']) {
            return;
        }

        while (
            count($this->entries) >= $this->limits['entries']
            || $this->weight > $this->limits['weight'] - $weight
        ) {
            $oldest = array_key_first($this->entries);
            if ($oldest === null) {
                break;
            }

            $this->weight -= $this->entries[$oldest]['weight'];
            unset($this->entries[$oldest]);
        }

        $this->entries[$key] = ['value' => $value, 'weight' => $weight];
        $this->weight += $weight;
    }
}
