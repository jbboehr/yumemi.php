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

/**
 * @param class-string $class
 * @param array<string, list<string>> $methods
 */
function verifyMethods(string $class, array $methods): void
{
    $reflection = new ReflectionClass($class);

    foreach ($methods as $methodName => $parameterNames) {
        if (!$reflection->hasMethod($methodName)) {
            throw new RuntimeException(sprintf('%s::%s() does not exist.', $class, $methodName));
        }

        $actualNames = array_map(
            static fn (ReflectionParameter $parameter): string => $parameter->getName(),
            $reflection->getMethod($methodName)->getParameters(),
        );
        foreach ($parameterNames as $parameterName) {
            if (!in_array($parameterName, $actualNames, true)) {
                throw new RuntimeException(sprintf(
                    '%s::%s() does not have parameter $%s; found: %s.',
                    $class,
                    $methodName,
                    $parameterName,
                    implode(', ', $actualNames),
                ));
            }
        }
    }
}

verifyMethods(Illuminate\Contracts\Cache\Repository::class, [
    'put' => ['ttl'],
    'add' => ['ttl'],
    'remember' => ['ttl'],
]);
verifyMethods(Illuminate\Contracts\Cache\Store::class, [
    'put' => ['seconds'],
    'putMany' => ['seconds'],
]);
verifyMethods(Illuminate\Contracts\Cache\Lock::class, ['block' => ['seconds']]);
verifyMethods(Illuminate\Contracts\Cache\LockProvider::class, ['lock' => ['seconds']]);
verifyMethods(Illuminate\Cache\Repository::class, [
    'put' => ['ttl'],
    'set' => ['ttl'],
    'putMany' => ['ttl'],
    'setMultiple' => ['ttl'],
    'add' => ['ttl'],
    'remember' => ['ttl'],
    'getDefaultCacheTime' => [],
    'setDefaultCacheTime' => ['seconds'],
]);
verifyMethods(Illuminate\Cache\RateLimiter::class, [
    'attempt' => ['decaySeconds'],
    'hit' => ['decaySeconds'],
    'increment' => ['decaySeconds'],
    'decrement' => ['decaySeconds'],
    'availableIn' => [],
]);
verifyMethods(Illuminate\Cache\Lock::class, [
    '__construct' => ['seconds'],
    'block' => ['seconds'],
    'betweenBlockedAttemptsSleepFor' => ['milliseconds'],
]);
verifyMethods(Illuminate\Cache\RateLimiting\Limit::class, [
    '__construct' => ['decaySeconds'],
    'perSecond' => ['decaySeconds'],
    'perMinute' => ['decayMinutes'],
    'perMinutes' => ['decayMinutes'],
    'perHour' => ['decayHours'],
    'perDay' => ['decayDays'],
]);

if (!(new ReflectionClass(Illuminate\Cache\RateLimiting\Limit::class))->hasProperty('decaySeconds')) {
    throw new RuntimeException('Illuminate\\Cache\\RateLimiting\\Limit::$decaySeconds does not exist.');
}
