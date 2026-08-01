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

use Illuminate\Cache\Lock;
use Illuminate\Cache\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Cache\Repository;
use Illuminate\Contracts\Cache\Repository as RepositoryContract;
use Illuminate\Contracts\Cache\Store;

use function jbboehr\Yumemi\unit;

function rejectInvalidIlluminateCacheUnits(
    Store $store,
    RepositoryContract $contractRepository,
    Repository $repository,
    RateLimiter $rateLimiter,
    Lock $lock,
): void {
    $store->put('key', 'value', 30);
    $store->put('key', 'value', unit(30, 'meter'));
    $contractRepository->put('key', 'value', 30);
    $contractRepository->put('key', 'value', unit(2, 'minute'));
    $repository->put('key', 'value', 30);
    $repository->put('key', 'value', unit(2, 'minute'));
    $repository->setDefaultCacheTime(30);
    $rateLimiter->hit('key', 30);
    $rateLimiter->hit('key', unit(2, 'minute'));
    $lock->betweenBlockedAttemptsSleepFor(unit(1, 'second'));
    Limit::perMinute(3, unit(1, 'second'));
}
