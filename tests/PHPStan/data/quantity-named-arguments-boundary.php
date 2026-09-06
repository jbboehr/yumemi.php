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

use function PHPStan\Testing\assertType;

$units = Units::default();
$distance = $units->quantity(1, unit: 'meter');
$duration = $units->quantity(value: 1, unit: 'second');

assertType("Quantity<'meter'>", $distance);
assertType("Quantity<'meter * second'>", $distance->mul(other: $duration));
assertType("Quantity<'meter / second'>", $distance->div(other: $duration));
assertType("Quantity<'1 / meter'>", $distance->rdiv(numerator: 2));
assertType("Quantity<'meter ^ 2'>", $distance->pow(power: 2));
assertType("Quantity<'meter'>", $units->quantity(unit: 'meter ^ 2', value: 1)->root(degree: 2));

$freezing = $units->point(0, unit: 'celsius');
$rise = $units->deltaQuantity(1, unit: 'fahrenheit');
$boiling = $units->point(unit: 'fahrenheit', value: 212);

assertType("PointQuantity<'celsius'>", $freezing->add(delta: $rise));
assertType("Quantity<'delta_fahrenheit'>", $boiling->differenceFrom(origin: $freezing));

assertType(Quantity::class, $units->quantity(...['value' => 1, 'unit' => 'meter']));
assertType(Quantity::class, ($units->quantity(...))(value: 1, unit: 'meter'));
assertType(Quantity::class, ($distance->to(...))(unit: 'foot'));
