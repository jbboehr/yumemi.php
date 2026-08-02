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

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Testing\File;
use Illuminate\Http\Testing\FileFactory;

use function jbboehr\Yumemi\unit;

/** @param unit_int<'second'> $seconds */
function acceptHttpSeconds(int $seconds): void
{
}

function rejectInvalidIlluminateHttpUnits(PendingRequest $request, FileFactory $files, File $file): void
{
    $request->timeout(30);
    $request->timeout(unit(30, 'millisecond'));
    $request->timeout(unit(30, 'meter'));
    $request->connectTimeout(unit(30, 'minute'));
    $request->retry(3, 250);
    $request->retry(3, unit(1, 'second'));
    $request->retry([100, 250]);
    $request->retry([unit(1, 'second')]);
    $request->retry(3, static fn (int $attempt, mixed $exception): int => 250);

    $files->create('report.txt', 2);
    $files->create('report.txt', unit(2, 'kilobyte'));
    $files->create('report.txt', unit(2, 'second'));
    File::create('report.txt', 2);
    $file->size(unit(2, 'kilobyte'));
    acceptHttpSeconds($file->getSize());
}
