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

$fixturesRoot = $argv[1] ?? __DIR__;
$aggregate = packageVersions($fixturesRoot . '/dependencies/composer.lock');
$valid = true;

foreach (['automatic', 'manual', 'phpgeo'] as $fixture) {
    foreach (packageVersions($fixturesRoot . '/' . $fixture . '/composer.lock') as $package => $version) {
        if ($package === 'jbboehr/yumemi') {
            continue;
        }

        if (($aggregate[$package] ?? null) === $version) {
            continue;
        }

        $actual = $aggregate[$package] ?? 'missing';
        fwrite(
            STDERR,
            sprintf(
                "%s requires %s %s, but the shared consumer closure contains %s.\n",
                $fixture,
                $package,
                $version,
                $actual,
            ),
        );
        $valid = false;
    }
}

if (!$valid) {
    exit(1);
}

fwrite(STDOUT, "Consumer lock files use one compatible external dependency closure.\n");

/**
 * @return array<string, string>
 */
function packageVersions(string $lockFile): array
{
    $contents = file_get_contents($lockFile);
    if ($contents === false) {
        throw new RuntimeException(sprintf('Unable to read %s.', $lockFile));
    }

    /**
     * @var array{
     *     packages?: list<array{name: string, version: string}>,
     *     packages-dev?: list<array{name: string, version: string}>
     * } $lock
     */
    $lock = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
    $versions = [];

    foreach (array_merge($lock['packages'] ?? [], $lock['packages-dev'] ?? []) as $package) {
        $versions[$package['name']] = $package['version'];
    }

    ksort($versions);

    return $versions;
}
