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

namespace YumemiTypeNames;

use jbboehr\Yumemi as Measures;
use jbboehr\Yumemi\PointQuantity;
use jbboehr\Yumemi\PointQuantity as Coordinate;
use jbboehr\Yumemi\Quantity;
use jbboehr\Yumemi\Quantity as Distance;

use function PHPStan\Testing\assertType;

/**
 * @param Quantity<'meter'> $ordinary
 * @param Distance<'meter'> $renamed
 * @param Measures\Quantity<'meter'> $qualifiedAlias
 * @param \jbboehr\Yumemi\Quantity<'meter'> $fullyQualified
 * @param PointQuantity<'celsius'> $point
 * @param Coordinate<'celsius'> $renamedPoint
 * @param Measures\PointQuantity<'celsius'> $qualifiedPointAlias
 * @param \jbboehr\Yumemi\PointQuantity<'celsius'> $fullyQualifiedPoint
 * @param unit_int<'meter'> $integer
 * @param unit_float<'meter'> $float
 * @param unit_numeric_string<'meter'> $numericString
 */
function inspectImportedTypes(
    Quantity $ordinary,
    Distance $renamed,
    Measures\Quantity $qualifiedAlias,
    \jbboehr\Yumemi\Quantity $fullyQualified,
    PointQuantity $point,
    Coordinate $renamedPoint,
    Measures\PointQuantity $qualifiedPointAlias,
    \jbboehr\Yumemi\PointQuantity $fullyQualifiedPoint,
    int $integer,
    float $float,
    string $numericString,
): void {
    assertType("Quantity<'meter'>", $ordinary);
    assertType("Quantity<'meter'>", $renamed);
    assertType("Quantity<'meter'>", $qualifiedAlias);
    assertType("Quantity<'meter'>", $fullyQualified);
    assertType("PointQuantity<'celsius'>", $point);
    assertType("PointQuantity<'celsius'>", $renamedPoint);
    assertType("PointQuantity<'celsius'>", $qualifiedPointAlias);
    assertType("PointQuantity<'celsius'>", $fullyQualifiedPoint);
    assertType("unit_int<'meter'>", $integer);
    assertType("unit_float<'meter'>", $float);
    assertType("unit_numeric_string<'meter'>", $numericString);
}

namespace jbboehr\Yumemi;

use function PHPStan\Testing\assertType;

/**
 * @param Quantity<'second'> $duration
 * @param PointQuantity<'kelvin'> $temperature
 */
function inspectLocalQuantityTypes(Quantity $duration, PointQuantity $temperature): void
{
    assertType("Quantity<'second'>", $duration);
    assertType("PointQuantity<'kelvin'>", $temperature);
}
