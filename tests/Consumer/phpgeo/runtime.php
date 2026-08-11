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

require __DIR__ . '/vendor/autoload.php';

use Location\Bearing\BearingSpherical;
use Location\Coordinate;
use Location\Distance\Vincenty;

use function jbboehr\Yumemi\unit;
use function jbboehr\Yumemi\unit_to;

$start = new Coordinate(unit(0.0, 'degree'), unit(0.0, 'degree'));
$end = new Coordinate(unit(0.0, 'degree'), unit(1.0, 'degree'));
$distance = (new Vincenty())->getDistance($start, $end);

if (abs($distance - 111_319.491) > 1e-6) {
    throw new RuntimeException('phpgeo returned an unexpected Vincenty distance.');
}

if (abs(unit_to($distance, 'meter', 'kilometer') - 111.319491) > 1e-9) {
    throw new RuntimeException('Yumemi failed to convert phpgeo distance output.');
}

$destination = (new BearingSpherical())->calculateDestination(
    $start,
    unit(90.0, 'degree'),
    unit(1_000.0, 'meter'),
);

if (abs($destination->getLat()) > 1e-12 || abs($destination->getLng() - 0.0089932033549289) > 1e-12) {
    throw new RuntimeException('phpgeo returned an unexpected destination coordinate.');
}
