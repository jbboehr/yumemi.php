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

declare(strict_types=1);

use function jbboehr\Yumemi\unit;

/** @param unit_float<'meter'> $distance */
function saveDividedDistance(float $distance): void
{
}

/** @param unit_float<'meter ^ 2'> $area */
function saveDividedArea(float $area): void
{
}

/** @param unit_int<'meter'>|unit_int<'second'> $value */
function saveAmbiguousDividedValue(int $value): void
{
    saveDividedDistance(($value / 2) * 2);
    saveDividedDistance(2 * ($value / 2));
    saveDividedArea(($value / 2) ** 2);
    saveDividedArea((2 / $value) * unit(1.0, 'meter ^ 3'));
    saveDividedArea(unit(1.0, 'meter ^ 3') * (2 / $value));
}

/** @param unit_int<'meter'> $distance */
function saveKnownDividedDistance(int $distance): void
{
    saveDividedDistance(($distance / 2) * 2);
    saveDividedDistance(2 * ($distance / 2));
    saveDividedArea(($distance / 2) ** 2);
    saveDividedArea((2 / $distance) * unit(1.0, 'meter ^ 3'));
    saveDividedArea(unit(1.0, 'meter ^ 3') * (2 / $distance));
}
