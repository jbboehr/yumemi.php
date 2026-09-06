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

use jbboehr\Yumemi\Number\FloatRangePolicy;
use jbboehr\Yumemi\Quantity;
use jbboehr\Yumemi\Units;

use function PHPStan\Testing\assertType;

$units = Units::default();

assertType("Quantity<'meter'>", $units->quantity(1, 'meter'));
assertType("Quantity<'meter'>", $units->quantity(value: 1, unit: 'meter'));
assertType("Quantity<'meter'>", $units->quantity(unit: 'meter', value: 1));
assertType("PointQuantity<'celsius'>", $units->point(0, 'celsius'));
assertType("PointQuantity<'celsius'>", $units->point(value: 0, unit: 'celsius'));
assertType("PointQuantity<'celsius'>", $units->point(unit: 'celsius', value: 0));
assertType("Quantity<'delta_fahrenheit'>", $units->deltaQuantity(18, 'fahrenheit'));
assertType("Quantity<'delta_fahrenheit'>", $units->deltaQuantity(value: 18, unit: 'fahrenheit'));
assertType("Quantity<'delta_fahrenheit'>", $units->deltaQuantity(unit: 'fahrenheit', value: 18));
assertType("Quantity<'meter'>", $units->parseQuantity(input: '2 meter'));

$distance = $units->quantity(unit: 'meter', value: 1);
assertType('string', $distance->decimalValueIn('meter', 2, RoundingMode::HalfEven));
assertType('string', $distance->decimalValueIn(unit: 'meter', scale: 2, mode: RoundingMode::HalfEven));
assertType('string', $distance->decimalValueIn(scale: 2, mode: RoundingMode::HalfEven, unit: 'meter'));
assertType('string', $distance->decimalValueIn('meter', mode: RoundingMode::HalfEven, scale: 2));
assertType("unit_float<'meter'>", $distance->floatValueIn(unit: 'meter'));
assertType("unit_float<'meter'>", $distance->floatValueIn(rangePolicy: FloatRangePolicy::Strict, unit: 'meter'));
assertType("Quantity<'meter ^ 2'>", $distance->pow(power: 2));

$temperature = $units->point(unit: 'celsius', value: 0);
assertType('string', $temperature->decimalValueIn('fahrenheit', 2, RoundingMode::HalfEven));
assertType('string', $temperature->decimalValueIn(unit: 'fahrenheit', scale: 2, mode: RoundingMode::HalfEven));
assertType('string', $temperature->decimalValueIn(scale: 2, mode: RoundingMode::HalfEven, unit: 'fahrenheit'));
assertType('float', $temperature->floatValueIn(rangePolicy: FloatRangePolicy::Strict, unit: 'fahrenheit'));

function convertUnbrandedNamedQuantity(Quantity $quantity): void
{
    assertType("unit_float<'meter'>", $quantity->floatValueIn(rangePolicy: FloatRangePolicy::Strict, unit: 'meter'));
}

function constructDynamicNamedQuantity(Units $units, string $unit): void
{
    assertType('jbboehr\\Yumemi\\Quantity', $units->quantity(unit: $unit, value: 1));
}
