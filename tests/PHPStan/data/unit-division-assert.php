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

use function jbboehr\Yumemi\unit;
use function PHPStan\Testing\assertType;

$whole = unit(4, 'meter') / 2;
assertType("2&unit_int<'meter'>", $whole);
assertType('true', is_int($whole));
assertType("2.5&unit_float<'meter'>", unit(5, 'meter') / 2);
assertType("2.0&unit_float<'meter'>", unit(4, 'meter') / 2.0);
assertType("2&unit_int<'meter / second'>", unit(4, 'meter') / unit(2, 'second'));
assertType("2&unit_int<'1 / second'>", 4 / unit(2, 'second'));
assertType("2&unit_int<'1'>", unit(4, 'meter') / unit(2, 'meter'));
assertType("2.0&unit_float<'meter'>", fdiv(unit(4, 'meter'), 2));

function divideMeasuredDistance(int $distance, int $seconds): void
{
    $speed = unit($distance, 'meter') / unit($seconds, 'second');
    assertType("(unit_float<'meter / second'>|unit_int<'meter / second'>)", $speed);
    assertType('bool', is_int($speed));
    assertType('bool', is_float($speed));
    if (is_int($speed)) {
        assertType("unit_int<'meter / second'>", $speed);
    } else {
        assertType("unit_float<'meter / second'>", $speed);
    }
}

/** @param 3|4 $distance */
function divideKnownDistance(int $distance): void
{
    $part = unit($distance, 'meter') / 2;
    assertType("1.5&unit_float<'meter'>|2&unit_int<'meter'>", $part);
    if (is_int($part)) {
        assertType("2&unit_int<'meter'>", $part);
    } else {
        assertType("1.5&unit_float<'meter'>", $part);
    }
}

/** @param unit_int<'meter'>|unit_float<'meter'> $distance */
function divideMixedDistance(int|float $distance, int $parts): void
{
    $part = $distance / $parts;
    assertType("unit_float<'meter'>|unit_int<'meter'>", $part);
    if (is_int($part)) {
        assertType("unit_int<'meter'>", $part);
    } else {
        assertType("unit_float<'meter'>", $part);
    }
}
