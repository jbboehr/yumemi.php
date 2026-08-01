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
use Illuminate\Contracts\Cache\Lock as LockContract;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\Repository as RepositoryContract;
use Illuminate\Contracts\Cache\Store;

use function jbboehr\Yumemi\unit;

/** @param unit_int<'second'> $seconds */
function acceptCacheSeconds(int $seconds): void
{
}

function exerciseIlluminateCacheStubs(
    RepositoryContract $contractRepository,
    Store $store,
    LockContract $contractLock,
    LockProvider $lockProvider,
    Repository $repository,
    RateLimiter $rateLimiter,
    Lock $lock,
    Limit $limit,
): void {
    $seconds = unit(30, 'second');
    $milliseconds = unit(250, 'millisecond');

    $contractRepository->put('key', 'value', $seconds);
    $contractRepository->add('key', 'value', new DateInterval('PT30S'));
    $contractRepository->remember('key', $seconds, static fn (): string => 'value');
    $store->put('key', 'value', $seconds);
    $store->putMany(['key' => 'value'], $seconds);
    $contractLock->block($seconds);
    $lockProvider->lock('key', $seconds);

    $repository->put('key', 'value', $seconds);
    $repository->set('key', 'value', new DateTimeImmutable('+30 seconds'));
    $repository->putMany(['key' => 'value'], $seconds);
    $repository->setMultiple(['key' => 'value'], $seconds);
    $repository->add('key', 'value', $seconds);
    $repository->remember('key', $seconds, static fn (): string => 'value');
    $repository->setDefaultCacheTime($seconds);

    $defaultCacheTime = $repository->getDefaultCacheTime();
    if ($defaultCacheTime !== null) {
        acceptCacheSeconds($defaultCacheTime);
    }

    $rateLimiter->attempt('key', 3, static fn (): bool => true, $seconds);
    $rateLimiter->hit('key', $seconds);
    $rateLimiter->increment('key', $seconds);
    $rateLimiter->decrement('key', $seconds);
    acceptCacheSeconds($rateLimiter->availableIn('key'));

    $lock->block($seconds);
    $lock->betweenBlockedAttemptsSleepFor($milliseconds);

    Limit::perSecond(3, $seconds);
    new Limit('', 3, $seconds);
    Limit::perMinute(3, unit(2, 'minute'));
    Limit::perMinutes(unit(2, 'minute'), 3);
    Limit::perHour(3, unit(2, 'hour'));
    Limit::perDay(3, unit(2, 'day'));
    acceptCacheSeconds($limit->decaySeconds);

    $contractRepository->put('key', 'value');
    $repository->put('key', 'value');
    $rateLimiter->hit('key');
}
