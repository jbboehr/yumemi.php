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

namespace Inventory;

use function PHPStan\Testing\assertType;

/** @template T */
class Quantity
{
}

/** @template T */
class PointQuantity
{
}

/** @template T */
class unit_int
{
}

/** @template T */
class unit_float
{
}

/** @template T */
class unit_numeric_string
{
}

/**
 * @param Quantity<string> $quantity
 * @param PointQuantity<int> $point
 * @param \Inventory\unit_int<string> $integer
 * @param \Inventory\unit_float<string> $float
 * @param \Inventory\unit_numeric_string<int> $numericString
 */
function inspectLocalInventoryTypes(
    Quantity $quantity,
    PointQuantity $point,
    unit_int $integer,
    unit_float $float,
    unit_numeric_string $numericString,
): void {
    assertType('Inventory\\Quantity<string>', $quantity);
    assertType('Inventory\\PointQuantity<int>', $point);
    assertType('Inventory\\unit_int<string>', $integer);
    assertType('Inventory\\unit_float<string>', $float);
    assertType('Inventory\\unit_numeric_string<int>', $numericString);
}

namespace Shipping;

use Inventory\PointQuantity;
use Inventory\Quantity as Stock;

use function PHPStan\Testing\assertType;

/**
 * @param Stock<string> $imported
 * @param PointQuantity<int> $point
 * @param \Inventory\Quantity<string> $fullyQualified
 */
function inspectImportedInventoryTypes(Stock $imported, PointQuantity $point, \Inventory\Quantity $fullyQualified): void
{
    assertType('Inventory\\Quantity<string>', $imported);
    assertType('Inventory\\PointQuantity<int>', $point);
    assertType('Inventory\\Quantity<string>', $fullyQualified);
}
