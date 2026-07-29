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

namespace jbboehr\Yumemi\Analyzer;

use jbboehr\Yumemi\Registry\UnitRegistry;

/**
 * Resolves only unit-name structure: exact spelling first, then one prefix plus an exact residual.
 *
 * @internal Expression construction remains the responsibility of {@see UnitResolver}.
 */
final class UnitNameResolver
{
    /** @var array<string, ResolvedUnitName|null> */
    private array $cache = [];

    public function __construct(
        private readonly UnitRegistry $unitRegistry,
    ) {
    }

    public function resolve(string $name): ?ResolvedUnitName
    {
        if (array_key_exists($name, $this->cache)) {
            return $this->cache[$name];
        }

        if ($this->existsExactly($name)) {
            return $this->cache[$name] = new ResolvedUnitName($name, $name);
        }

        foreach ($this->sortedPrefixes() as $prefix => $definition) {
            if (!str_starts_with($name, $prefix)) {
                continue;
            }

            $unitName = substr($name, strlen($prefix));
            if ($unitName === '' || !$this->existsExactly($unitName)) {
                continue;
            }

            return $this->cache[$name] = new ResolvedUnitName(
                matchedName: $name,
                unitName: $unitName,
                prefixName: $prefix,
                prefixDefinition: $definition,
            );
        }

        return $this->cache[$name] = null;
    }

    private function existsExactly(string $name): bool
    {
        return $this->unitRegistry->findPrebuiltUnit($name) !== null || $this->unitRegistry->findCatalogRecord($name) !== null;
    }

    /**
     * @return array<string, string>
     */
    private function sortedPrefixes(): array
    {
        $prefixes = $this->unitRegistry->prefixes();
        uksort($prefixes, static fn (string $left, string $right): int => strlen($right) <=> strlen($left));

        return $prefixes;
    }
}
