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

namespace ScopedQuantityTags {
    use jbboehr\Yumemi\{Quantity as Distance, PointQuantity as Coordinate, Units};

    use function PHPStan\Testing\assertType;

    /**
     * @return Distance
     * @yumemi-return Distance<'meter'>
     */
    function measuredDistance(Units $units): Distance
    {
        return $units->quantity(1, 'meter');
    }

    /**
     * @return Coordinate
     * @yumemi-return Coordinate<'celsius'>
     */
    function measuredTemperature(Units $units): Coordinate
    {
        return $units->point(0, 'celsius');
    }

    /** @yumemi-param Distance<'meter'> $distance */
    function inspectDistance(Distance $distance): void
    {
        assertType("Quantity<'meter'>", $distance);
    }

    assertType("Quantity<'meter'>", measuredDistance(Units::default()));
    assertType("PointQuantity<'celsius'>", measuredTemperature(Units::default()));
}

namespace TaggedInventory {
    /** @template T */
    class Quantity
    {
    }
}

namespace ForeignQuantityTags {
    use TaggedInventory\Quantity;

    use function PHPStan\Testing\assertType;

    /**
     * @param Quantity<unit_int<'meter'>> $stock
     * @return Quantity<int>
     * @yumemi-return Quantity<unit_int<'meter'>>
     */
    function keepStock(Quantity $stock): Quantity
    {
        assertType("TaggedInventory\\Quantity<unit_int<'meter'>>", $stock);

        return $stock;
    }

    /** @param Quantity<unit_int<'meter'>> $stock */
    function inspectStock(Quantity $stock): void
    {
        assertType("TaggedInventory\\Quantity<unit_int<'meter'>>", keepStock($stock));
    }
}

namespace ResetQuantityTags {
    use jbboehr\Yumemi\Quantity;

    use function PHPStan\Testing\assertType;

    /** @yumemi-param Quantity<'second'> $duration */
    function inspectDuration(Quantity $duration): void
    {
        assertType("Quantity<'second'>", $duration);
    }
}
