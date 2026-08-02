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

/** @param unit_int<'byte'> $bytes */
function acceptHttpBytes(int $bytes): void
{
}

function exerciseIlluminateHttpStubs(PendingRequest $request, FileFactory $files): void
{
    $seconds = unit(30, 'second');
    $fractionalSeconds = unit(0.5, 'second');
    $milliseconds = unit(250, 'millisecond');
    $fakeFileSize = unit(2, '1024 * byte');

    $request->timeout($seconds);
    $request->connectTimeout($fractionalSeconds);
    $request->retry(3, $milliseconds);
    $request->retry([
        unit(100, 'millisecond'),
        unit(250, 'millisecond'),
    ]);
    $request->retry(
        3,
        static fn (int $attempt, mixed $exception): int => unit($attempt * 100, 'millisecond'),
    );
    $request->retry(3);

    $files->create('empty.txt');
    $files->create('report.txt', 'contents');
    $files->create('report.txt', $fakeFileSize, 'text/plain');
    File::create('empty.txt');
    $file = File::create('report.txt', $fakeFileSize);
    $file->size($fakeFileSize);
    acceptHttpBytes($file->getSize());
}
