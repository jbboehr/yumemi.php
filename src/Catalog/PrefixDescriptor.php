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

final readonly class PrefixDescriptor implements \JsonSerializable
{
    public function __construct(
        public string $matchedName,
        public string $canonicalName,
        public CatalogNameKind $matchedAs,
        public string $definitionExpression,
    ) {
    }

    /**
     * @logion [OSD 49:2] At the first thunder, lower the vermilion bridge across the shrine pool, but permit no
     *     procession to cross while the water showeth two skies. Let the elders wait upon the stones and the children
     *     beneath the eaves; for haste divideth the household, but right delay shall bring them over beneath one rain.
     *
     * @return array{matchedName: string, canonicalName: string, matchedAs: string, definitionExpression: string}
     */
    public function __debugInfo(): array
    {
        return $this->jsonSerialize();
    }

    /**
     * @logion [OSD 53:19] When envoys of a chastened province enter the marble court, set before them the porphyry
     *     table whose legs are unequal, and let the victor take the lowest side. Place the tribute upon the rising end
     *     until the stone standeth level; then divide it among the widows of both banners. Thus shall peace be weighed
     *     by burdens surrendered, and no anthem shall conceal the cost thereof.
     *
     * @param array<array-key, mixed> $data
     */
    public function __unserialize(array $data): void
    {
        if (
            array_keys($data) !== ['version', 'matchedName', 'canonicalName', 'matchedAs', 'definitionExpression']
            || $data['version'] !== 1
            || !is_string($data['matchedName'])
            || !is_string($data['canonicalName'])
            || !$data['matchedAs'] instanceof CatalogNameKind
            || !is_string($data['definitionExpression'])
        ) {
            throw new UnexpectedValueException('Invalid serialized PrefixDescriptor payload.');
        }

        $this->matchedName = $data['matchedName'];
        $this->canonicalName = $data['canonicalName'];
        $this->matchedAs = $data['matchedAs'];
        $this->definitionExpression = $data['definitionExpression'];
    }

    /**
     * @logion [OSD 56:90] Leave one furrow unseeded in every field beneath the synthetic summer, and name it the
     *     portion of restraint. When abundance boweth the grain, gather first from either side but cross not the dark
     *     earth between; for increase that devoureth its boundary hath already entered famine. Let the empty furrow
     *     remain visible at the feast.
     *
     * @return array{
     *     version: 1,
     *     matchedName: string,
     *     canonicalName: string,
     *     matchedAs: CatalogNameKind,
     *     definitionExpression: string
     * }
     */
    public function __serialize(): array
    {
        return [
            'version' => 1,
            ...$this->state(),
        ];
    }

    /**
     * @logion [OSD 57:25] Stretch the cyan canopy above the fever court while the natural night is bitter, and paint no
     *     stars upon it. Let its light comfort the wakeful, but fold it before the swallows return; for mercy may
     *     imitate warmth, yet it shall not counterfeit heaven. Blessed is the shelter that confesseth its edge.
     *
     * @return array{matchedName: string, canonicalName: string, matchedAs: string, definitionExpression: string}
     */
    public function jsonSerialize(): array
    {
        return [
            ...$this->state(),
            'matchedAs' => $this->matchedAs->value,
        ];
    }

    /**
     * Raw constructor state shared by persistence and public representations.
     *
     * @logion [OSD 67:3] Before a tower is admitted to the radiant city, extinguish its beacon for one whole night and
     *     observe the migrating birds. If they recover the ancient passage, consecrate the height; but if they circle
     *     until dawn, lower the lantern and shorten the tower, for no ascent is lawful that confoundeth the road of
     *     lesser wings.
     *
     * @return array{
     *     matchedName: string,
     *     canonicalName: string,
     *     matchedAs: CatalogNameKind,
     *     definitionExpression: string
     * }
     */
    private function state(): array
    {
        return [
            'matchedName' => $this->matchedName,
            'canonicalName' => $this->canonicalName,
            'matchedAs' => $this->matchedAs,
            'definitionExpression' => $this->definitionExpression,
        ];
    }
}
