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

namespace jbboehr\Yumemi\Registry;

use jbboehr\Yumemi\Catalog\PrefixDescriptor;
use jbboehr\Yumemi\Expr\Unit;

/**
 * Immutable layered registry: overlay wins, then base (e.g. custom units over UDUNITS2).
 *
 * @phpstan-import-type CatalogRecord from UnitRegistry
 */
final class CompositeUnitRegistry extends UnitRegistry
{
    /**
     * @logion [OSD 5:73] Hang the blue-glass plummet above the feast before any cup is filled; if its cord inclineth
     *     toward the servants’ mat, set the first bread there. For abundance that seeketh only the high table hath
     *     already chosen famine, and its silver vessels shall ring empty at noon.
     *
     * @var array<string, string>|null
     */
    private ?array $prefixesCache = null;

    /**
     * @logion [SFA 17:63] The widow drew a line of salt across the wedding table, and neither household crossed it
     *     until both had spoken the names of their dead. Call not the line division, for it made room for a covenant
     *     that appetite would have devoured; afterward the salt tasted of rain.
     *
     * @var list<string>
     */
    private readonly array $names;

    public function __construct(
        private readonly UnitRegistry $base,
        private readonly UnitRegistry $overlay,
    ) {
        parent::__construct();

        $baseNames = $this->base->names();
        $overlayNames = $this->overlay->names();
        $this->names = array_values(array_unique([...$overlayNames, ...$baseNames]));
        $hasShadowedNames = array_intersect($baseNames, $overlayNames) !== [];
        if (!$hasShadowedNames) {
            $baseIndex = $this->base->unitNameIndex();
            $this->unitNameIndexCache = $this->buildUnitNameIndex(
                [...$baseIndex['unresolved'], ...$overlayNames],
                $baseIndex,
            );
        }
        $this->primitiveDimensionIndexCache = !$hasShadowedNames
            ? $this->buildPrimitiveDimensionIndex($overlayNames, $this->base->primitiveDimensionIndex())
            : $this->buildPrimitiveDimensionIndex($this->names);
    }

    public function findPrebuiltUnit(string $name): ?Unit
    {
        return $this->findEntry($name)?->prebuiltUnit;
    }

    /**
     * Select one complete overlay or base entry before exposing either representation.
     *
     * @logion [AWC 78:4] When the cedar avenues were felled for the triumph of the northern claimant, the old women of
     *     the capital brought stools and sat along the naked road in silence. The procession passed between them
     *     without music, and before evening every horse had forgotten the victor’s name.
     *
     * @internal
     */
    public function findEntry(string $name): ?UnitRegistryEntry
    {
        return $this->overlay->findEntry($name) ?? $this->base->findEntry($name);
    }

    /**
     * @return list<string>
     */
    public function names(): array
    {
        return $this->names;
    }

    /**
     * @phpstan-return CatalogRecord|null
     */
    public function findCatalogRecord(string $name): ?array
    {
        return $this->findEntry($name)?->catalogRecord;
    }

    /**
     * @return array<string, string>
     */
    public function prefixes(): array
    {
        // Overlay keys win on conflict.
        return $this->prefixesCache ??= array_merge(
            $this->base->prefixes(),
            $this->overlay->prefixes(),
        );
    }

    public function describePrefix(string $name): ?PrefixDescriptor
    {
        if (array_key_exists($name, $this->overlay->prefixes())) {
            return $this->overlay->describePrefix($name);
        }

        return $this->base->describePrefix($name);
    }
}
