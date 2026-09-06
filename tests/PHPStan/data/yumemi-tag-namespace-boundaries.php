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

namespace YumemiTagBoundaryModel {
    /** @template T */
    class Quantity
    {
    }

    /**
     * @template T
     * @template U
     */
    class Pair
    {
    }
}

namespace YumemiTagNamespaceBoundaries {
    use jbboehr\Yumemi\{Quantity as Distance, Units, function unit};
    use jbboehr\Yumemi as Measures;
    use YumemiTagBoundaryModel\{Pair, Quantity};

    use function PHPStan\Testing\assertType;

    /**
     * @param Distance $distance
     * @return Measures\Quantity
     * @yumemi-param Distance<'meter'> $distance
     * @yumemi-return Measures\Quantity<'second'>
     */
    function relay(Distance $distance, Units $units): Measures\Quantity
    {
        assertType("Quantity<'meter'>", $distance);

        return $units->quantity(unit(1, 'second'), 'second');
    }

    $units = Units::default();
    assertType("Quantity<'second'>", relay($units->quantity(1, 'meter'), $units));

    /**
     * @var \jbboehr\Yumemi\PointQuantity $point
     * @yumemi-var \jbboehr\Yumemi\PointQuantity<'celsius'> $point
     */
    $point = $units->point(0, 'celsius');
    assertType("PointQuantity<'celsius'>", $point);

    /**
     * A foreign class with the same short name is not itself a Yumemi unit type.
     *
     * @return Quantity<string>
     * @yumemi-return Quantity<'meter'>
     */
    function invalidForeignQuantity(): Quantity
    {
        throw new \RuntimeException();
    }

    /**
     * Unit validation still descends through an unrelated generic container.
     *
     * @return Pair<int, string>
     * @yumemi-return Pair<unit_int<'not_a_real_unit_xyz'>, string>
     */
    function invalidNestedUnit(): Pair
    {
        throw new \RuntimeException();
    }

    /**
     * PHPStan remains responsible for validating the surrounding generic type.
     *
     * @return Pair<int>
     * @yumemi-return Pair<unit_int<'meter'>>
     */
    function invalidOuterGeneric(): Pair
    {
        throw new \RuntimeException();
    }
}
