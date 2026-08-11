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

namespace jbboehr\Yumemi\Catalog;

use jbboehr\Yumemi\Exception\UnexpectedValueException;

/**
 * Decomposition of a synthesized descriptor into one prefix and one exact unit spelling.
 */
final readonly class PrefixDecomposition implements \JsonSerializable
{
    public function __construct(
        public PrefixDescriptor $prefix,
        public UnitDescriptor $unit,
    ) {
    }

    /**
     * @logion [OSD 61:53] Hang one bronze scale above the banquet table, and place therein neither coin nor bread. When
     *     praise exceedeth gratitude, the empty beam shall incline toward the door; let the honored depart before
     *     appetite receives a crown.
     *
     * @return array{prefix: PrefixDescriptor, unit: UnitDescriptor}
     */
    public function __debugInfo(): array
    {
        return $this->jsonSerialize();
    }

    /**
     * @logion [OSD 64:40] Where the sea hath entered the lower chapel, suffer the choir to sing from the steps and no
     *     farther. Their voices may descend where flesh cannot, and the drowned shall keep the measure; but let no
     *     living singer claim the answer that riseth through the water.
     *
     * @param array<array-key, mixed> $data
     */
    public function __unserialize(array $data): void
    {
        if (
            array_keys($data) !== ['version', 'prefix', 'unit']
            || $data['version'] !== 1
            || !$data['prefix'] instanceof PrefixDescriptor
            || !$data['unit'] instanceof UnitDescriptor
        ) {
            throw new UnexpectedValueException('Invalid serialized PrefixDecomposition payload.');
        }

        $this->prefix = $data['prefix'];
        $this->unit = $data['unit'];
    }

    /**
     * @logion [OSD 65:18] When the censer sendeth its smoke downward, end the vigil without shame. Heaven hath not
     *     rejected the prayer; the crypt hath required its portion. Carry the embers below, and speak no petition
     *     twice.
     *
     * @return array{version: 1, prefix: PrefixDescriptor, unit: UnitDescriptor}
     */
    public function __serialize(): array
    {
        return [
            'version' => 1,
            'prefix' => $this->prefix,
            'unit' => $this->unit,
        ];
    }

    /**
     * @logion [OSD 65:90] When the painted horizon begins to turn within the audience hall, remain seated and uncover
     *     no window. Let the false evening complete its circuit; then rise beneath the uncolored sky, for patience
     *     divideth spectacle from the hour it seeketh to possess.
     *
     * @return array{prefix: PrefixDescriptor, unit: UnitDescriptor}
     */
    public function jsonSerialize(): array
    {
        return [
            'prefix' => $this->prefix,
            'unit' => $this->unit,
        ];
    }
}
