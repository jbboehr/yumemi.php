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

use jbboehr\Yumemi\Quantity;
use jbboehr\Yumemi\Units;

use function jbboehr\Yumemi\unit;

function inspectUnpackedQuantityCalls(Units $units): void
{
    $seconds = unit(1, 'second');
    $units->quantity(...[], value: $seconds, unit: 'meter');
    $units->quantity(...['value' => $seconds, 'unit' => 'meter']);
    $units->quantity(...[$seconds, 'meter']);
    $units->quantity(...[], ...[], value: $seconds, unit: 'meter');
    $units->quantity(...[1, 'unknown_unit']);
    $units->point(...[1, 'celsius / second']);
    $units->deltaQuantity(...['unit' => 'unknown_unit', 'value' => 1]);
    $units->parseQuantity(...['2 unknown_unit']);

    $distance = $units->quantity(1, 'meter');
    $duration = $units->quantity(1, 'second');
    $distance->to(...['second']);
    $distance->to(...[], unit: 'second');
    $distance->decimalValueIn(...['second', 2, RoundingMode::HalfEven]);
    $distance->add(...[$duration]);
    $distance->compareTo(...['other' => $duration]);
    $distance->root(...[2]);

    $temperature = $units->point(1, 'celsius');
    $temperature->to(...['meter']);
    $temperature->decimalValueIn(...['meter', 2, RoundingMode::HalfEven]);
    $temperature->add(...[$distance]);

    saveUnpackedDistance($units->quantity(...[], value: $seconds, unit: 'meter'));
}

/** @param Quantity<'meter'> $distance */
function saveUnpackedDistance(Quantity $distance): void
{
}
