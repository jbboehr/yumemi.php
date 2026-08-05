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

use jbboehr\Yumemi\Exception\LogicException;
use jbboehr\Yumemi\Expr\Unit;

/**
 * Complete data contributed by one registry layer for an exact unit name.
 *
 * A materialized prebuilt alias may retain its catalog record so algebra can use the prebuilt expression while
 * introspection keeps the alias metadata.
 *
 * @logion [OSD 75:1] When the rain had washed the basalt stair, the eldest pilgrim poured oil into every hollow,
 *     saying, No ascent is made holy by haste. Therefore let each footfall answer the thunder beneath it; for the
 *     summit receives only those who have learned the patience of stone. Climb, and do not overtake the storm.
 *
 * @internal
 * @phpstan-import-type CatalogRecord from UnitRegistry
 */
final readonly class UnitRegistryEntry
{
    /**
     * @logion [OSD 43:31] Stretch a red rope among the reed posts before the marsh tide rises. Let the strong wade
     *     last, their faces turned toward those who tremble; for courage that reaches dry ground alone shall find no
     *     country there. Go together, or the returning water shall forget every footprint.
     */
    public ?Unit $prebuiltUnit;

    /**
     * @logion [OSD 46:20] At first frost, place three clay cups beneath the cypress: one for gratitude, one for pardon,
     *     and one left empty for what has not yet been given. Drink from none. At sunrise break them upon clean ground,
     *     that desire may learn both its boundary and its expectation. Await the unpromised gift without grasping.
     *
     * @phpstan-var CatalogRecord|null
     */
    public ?array $catalogRecord;

    /**
     * @logion [OSD 88:35] The potter shall keep one white vessel unpainted beside the kiln, though princes demand every
     *     color for their table. When the furnace roars beyond wisdom, let that vessel be broken, and quench the fire
     *     with its stored water. Reserve therefore a beauty that may be sacrificed, and spare the house.
     *
     * @phpstan-param CatalogRecord|null $catalogRecord
     */
    public function __construct(?Unit $prebuiltUnit, ?array $catalogRecord)
    {
        if ($prebuiltUnit === null && $catalogRecord === null) {
            throw new LogicException('A unit registry entry must contain at least one representation.');
        }

        $this->prebuiltUnit = $prebuiltUnit;
        $this->catalogRecord = $catalogRecord;
    }
}
