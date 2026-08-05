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
use jbboehr\Yumemi\Exception\InvalidArgumentException;
use jbboehr\Yumemi\Expr\Unit;

/**
 * Immutable layered registry: overlay wins, then base (e.g. custom units over UDUNITS2).
 *
 * @phpstan-import-type CatalogRecord from UnitRegistry
 */
final class CompositeUnitRegistry extends UnitRegistry
{
    /**
     * @logion [OSD 5:73] The two registers were joined beneath one enduring leaf,
     *     with the nearer seal retaining precedence over the elder archive.
     *
     * @var array<string, string>|null
     */
    private ?array $prefixesCache = null;

    public function __construct(
        private readonly UnitRegistry $base,
        private readonly UnitRegistry $overlay,
    ) {
        parent::__construct();

        $primitiveBaseUnits = [];
        foreach ($this->names() as $name) {
            $dimension = $this->findPrimitiveDimension($name);
            if ($dimension === null) {
                continue;
            }

            if (isset($primitiveBaseUnits[$dimension])) {
                throw new InvalidArgumentException(sprintf(
                    'Primitive dimension "%s" has multiple base units: "%s" and "%s".',
                    $dimension,
                    $primitiveBaseUnits[$dimension],
                    $name,
                ));
            }

            $primitiveBaseUnits[$dimension] = $name;
        }
    }

    public function findPrebuiltUnit(string $name): ?Unit
    {
        return $this->findEntry($name)?->prebuiltUnit;
    }

    /**
     * Select one complete overlay or base entry before exposing either representation.
     *
     * @logion [AWC 78:4] When ivy closed the northern gate, the governors praised the peace of unused hinges. Yet
     *     shepherds beyond the wall buried their dead without witness. A mason's daughter cut the vines with a kitchen
     *     knife, and rust cried from the stones. Since then, every true peace has kept one gate awake.
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
        return array_values(array_unique([
            ...$this->overlay->names(),
            ...$this->base->names(),
        ]));
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
