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

use jbboehr\Yumemi\Units;

use function jbboehr\Yumemi\unit;

function inspectNamedQuantityCalls(Units $units): void
{
    $units->quantity(1, 'unknown_unit');
    $units->quantity(value: 1, unit: 'unknown_unit');
    $units->quantity(unit: 'unknown_unit', value: 1);

    $seconds = unit(1, 'second');
    $units->quantity($seconds, 'meter');
    $units->quantity(value: $seconds, unit: 'meter');
    $units->quantity(unit: 'meter', value: $seconds);

    $units->point(1, 'celsius / second');
    $units->point(value: 1, unit: 'celsius / second');
    $units->point(unit: 'celsius / second', value: 1);

    $units->deltaQuantity(1, 'unknown_unit');
    $units->deltaQuantity(value: 1, unit: 'unknown_unit');
    $units->deltaQuantity(unit: 'unknown_unit', value: 1);

    $units->parseQuantity('2 unknown_unit');
    $units->parseQuantity(input: '2 unknown_unit');

    $distance = $units->quantity(1, 'meter');
    $distance->decimalValueIn('second', 2, RoundingMode::HalfEven);
    $distance->decimalValueIn(unit: 'second', scale: 2, mode: RoundingMode::HalfEven);
    $distance->decimalValueIn(scale: 2, mode: RoundingMode::HalfEven, unit: 'second');
    $distance->decimalValueIn('second', mode: RoundingMode::HalfEven, scale: 2);

    $temperature = $units->point(1, 'celsius');
    $temperature->decimalValueIn('meter', 2, RoundingMode::HalfEven);
    $temperature->decimalValueIn(unit: 'meter', scale: 2, mode: RoundingMode::HalfEven);
    $temperature->decimalValueIn(scale: 2, mode: RoundingMode::HalfEven, unit: 'meter');
    $temperature->decimalValueIn('meter', mode: RoundingMode::HalfEven, scale: 2);
}
