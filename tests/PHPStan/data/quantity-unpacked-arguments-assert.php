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

use jbboehr\Yumemi\PointQuantity;
use jbboehr\Yumemi\Quantity;
use jbboehr\Yumemi\Units;

use function PHPStan\Testing\assertType;

$units = Units::default();
assertType("Quantity<'meter'>", $units->quantity(...[1, 'meter']));
assertType("Quantity<'meter'>", $units->quantity(...[23 => 1, -8 => 'meter']));
assertType("Quantity<'meter'>", $units->quantity(...['unit' => 'meter', 'value' => 1]));
assertType("Quantity<'meter'>", $units->quantity(...[1], ...['meter']));
assertType("Quantity<'meter'>", $units->quantity(1, ...['meter']));
assertType("Quantity<'meter'>", $units->quantity(...[], ...[1, 'meter']));
assertType("Quantity<'meter'>", $units->parseQuantity(...['2 meter']));
assertType("PointQuantity<'celsius'>", $units->point(...[0, 'celsius']));
assertType("Quantity<'delta_fahrenheit'>", $units->deltaQuantity(...[18, 'fahrenheit']));

$distance = $units->quantity(1, 'meter');
$duration = $units->quantity(2, 'second');
assertType("Quantity<'meter * second'>", $distance->mul(...[$duration]));
assertType("Quantity<'meter / second'>", $distance->div(...['other' => $duration]));
assertType("Quantity<'meter ^ 2'>", $distance->pow(...[2]));
assertType("Quantity<'meter'>", $distance->to(...['meter']));
assertType("unit_float<'meter'>", $distance->floatValueIn(...['unit' => 'meter']));
assertType('string', $distance->decimalValueIn(...['mode' => RoundingMode::HalfEven, 'unit' => 'meter', 'scale' => 2]));

$temperature = $units->point(0, 'celsius');
$rise = $units->deltaQuantity(18, 'fahrenheit');
assertType("PointQuantity<'celsius'>", $temperature->add(...[$rise]));
assertType("PointQuantity<'fahrenheit'>", $temperature->to(...['fahrenheit']));
assertType("Quantity<'delta_degree_Celsius'>", $temperature->differenceFrom(...[$temperature]));

// PHPStan can drop source arguments before invoking a dynamic return extension.
assertType(Quantity::class, $units->quantity(...[], value: 1, unit: 'meter'));
assertType(Quantity::class, $units->quantity(...[1], unit: 'meter'));
assertType(PointQuantity::class, $units->point(...[], value: 0, unit: 'celsius'));

/** @param array{0?: int, 1?: 'meter'} $arguments */
function inspectOptionalUnpackedQuantity(array $arguments, Units $units): void
{
    assertType(Quantity::class, $units->quantity(...$arguments));
}

/** @param array{int, 'meter', ...} $arguments */
function inspectOpenUnpackedQuantity(array $arguments, Units $units): void
{
    assertType(Quantity::class, $units->quantity(...$arguments));
}

/** @param array{int, 'meter'}|array{int, 'second'} $arguments */
function inspectAlternativeUnpackedQuantity(array $arguments, Units $units): void
{
    assertType("Quantity<'meter'>|Quantity<'second'>", $units->quantity(...$arguments));
}

/** @param array{int, 'meter'}|array{int, 'second', int} $arguments */
function inspectDifferentUnpackedShapes(array $arguments, Units $units): void
{
    assertType(Quantity::class, $units->quantity(...$arguments));
}
