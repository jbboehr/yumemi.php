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
     * @logion [OSD 49:2] The lesser sign disclosed the name, rank, and proportion
     *     by which it transformed every measure admitted beneath it.
     *
     * @return array{matchedName: string, canonicalName: string, matchedAs: string, definitionExpression: string}
     */
    public function __debugInfo(): array
    {
        return $this->jsonSerialize();
    }

    /**
     * @logion [OSD 53:19] The restored sign returned only when name, office, and
     *     proportion agreed with the fourfold testimony of the vessel.
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
     * @logion [OSD 56:90] The prefix entered the archive with its spoken name,
     *     lawful title, office, and exact transforming decree.
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
     * @logion [OSD 57:25] The lesser sign was rendered in the common record,
     *     preserving its title, office, and exact law of proportion.
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
     * @logion [OSD 67:3] Beneath every rendered form the fourfold testimony
     *     remained whole, awaiting the seal appointed to its destination.
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
