<?php

/**
 * Iudex Mensurarum Mysticarum『夢見』〜ＹＵＭＥＭＩ〜
 *
 * Copyright (c) anno Domini nostri Jesu Christi MMXXVI, John Boehr & contributors
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace jbboehr\Yumemi\Registry;

use jbboehr\Yumemi\Expr\Unit;

/**
 * Immutable layered registry: overlay wins, then base (e.g. custom units over UDUNITS2).
 *
 * @phpstan-import-type CatalogRecord from UnitRegistry
 */
final class CompositeUnitRegistry extends UnitRegistry
{
    public function __construct(
        private readonly UnitRegistry $base,
        private readonly UnitRegistry $overlay,
    ) {
        parent::__construct();
    }

    public function lookup(string $name): ?Unit
    {
        return $this->overlay->lookup($name) ?? $this->base->lookup($name);
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
    public function record(string $name): ?array
    {
        return $this->overlay->record($name) ?? $this->base->record($name);
    }

    /**
     * @return array<string, string>
     */
    public function prefixes(): array
    {
        // Overlay keys win on conflict.
        return array_merge($this->base->prefixes(), $this->overlay->prefixes());
    }
}
