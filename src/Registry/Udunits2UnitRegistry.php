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

use jbboehr\Yumemi\Expr\Unit;

/**
 * UDUNITS2 catalog data source.
 *
 * This class does not parse definition strings or own a UnitResolver/AstConverter.
 * {@see \jbboehr\Yumemi\Analyzer\UnitResolver} reads {@see record()}
 * rows and builds expression trees.
 *
 * @phpstan-type Udunits2BaseUnit array{type: 'base', name: string, definition?: string, plural?: string, comment?: string}
 * @phpstan-type Udunits2DimensionlessUnit array{
 *     type: 'dimensionless',
 *     name: string,
 *     definition?: string,
 *     plural?: string,
 *     comment?: string
 * }
 * @phpstan-type Udunits2DerivedUnit array{type: 'unit', name: string, def: string, definition?: string, plural?: string, comment?: string}
 * @phpstan-type Udunits2AliasUnit array{type: 'alias', name: string, def: string}
 * @phpstan-type Udunits2Unit Udunits2BaseUnit|Udunits2DimensionlessUnit|Udunits2DerivedUnit|Udunits2AliasUnit
 * @phpstan-type Udunits2Catalog array{
 *     units: array<string, Udunits2Unit>,
 *     base: list<string>,
 *     prefixes: array<string, string>,
 *     prefixRegex?: string
 * }
 * @phpstan-import-type CatalogRecord from UnitRegistry
 */
final class Udunits2UnitRegistry extends UnitRegistry
{
    /** Path to the generated UDUNITS2 catalog shipped with this package. */
    public const DATA_FILE = __DIR__ . '/../../data/udunits2.php';

    /** @phpstan-var Udunits2Catalog */
    private readonly array $catalog;

    public function __construct(?string $dataFile = null)
    {
        parent::__construct();

        $this->catalog = $this->loadCatalog($dataFile ?? self::DATA_FILE);
    }

    /**
     * Catalog-backed registries do not precompose Units; use UnitResolver or Units::unit().
     */
    public function lookup(string $name): ?Unit
    {
        return null;
    }

    /**
     * @return list<string>
     */
    public function names(): array
    {
        return array_keys($this->catalog['units']);
    }

    /**
     * @phpstan-return CatalogRecord|null
     */
    public function record(string $name): ?array
    {
        $unit = $this->catalog['units'][$name] ?? null;
        if ($unit === null) {
            return null;
        }

        return match ($unit['type']) {
            'alias' => [
                'type' => 'alias',
                'name' => $unit['name'],
                'def' => $unit['def'],
            ],
            'base' => [
                'type' => 'base',
                'name' => $unit['name'],
            ],
            'dimensionless' => [
                'type' => 'dimensionless',
                'name' => $unit['name'],
            ],
            'unit' => [
                'type' => 'unit',
                'name' => $unit['name'],
                'def' => $unit['def'],
            ],
        };
    }

    /**
     * @return array<string, string>
     */
    public function prefixes(): array
    {
        return $this->catalog['prefixes'];
    }

    /**
     * @phpstan-return Udunits2Catalog
     */
    private function loadCatalog(string $dataFile): array
    {
        $catalog = require $dataFile;

        if (!is_array($catalog)) {
            throw new \UnexpectedValueException('UDUNITS2 catalog file must return an array.');
        }

        /** @phpstan-var Udunits2Catalog $catalog */
        return $catalog;
    }
}
