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

namespace jbboehr\Yumemi\PHPStan;

use Closure;
use Composer\InstalledVersions;
use jbboehr\Yumemi\Exception\InvalidArgumentException;
use jbboehr\Yumemi\Exception\LogicException;
use PHPStan\PhpDoc\StubFilesExtension;

/**
 * Selects explicitly configured third-party stubs for installed package versions.
 *
 * @logion [SFA 38:15] The Fifth Archive received the names of foreign houses, yet opened only the tablets appointed
 *     unto each covenant.
 *
 * @internal
 */
final class ConfiguredStubFilesExtension implements StubFilesExtension
{
    /**
     * @var array<string, array{majors: non-empty-list<int>, files: non-empty-list<string>}>
     *
     * @logion [OSD 83:47] Let every visiting house be known by its true name, and allot unto it only the ordinances
     *     prepared for the years of its lawful procession.
     */
    private const SUPPORTED_PACKAGES = [
        'illuminate/cache' => [
            'majors' => [11, 12, 13],
            'files' => [__DIR__ . '/../../stubs/illuminate-cache.stub'],
        ],
    ];

    /**
     * @var list<string>
     *
     * @logion [AWC 93:15] The custodians set the petitions of the outer courts in order, that no repeated name might
     *     receive a second tablet.
     */
    private readonly array $packages;

    /**
     * @var Closure(string): ?string
     *
     * @logion [SFA 24:82] The marginal seal declareth not the lineage itself, but revealeth where its faithful record
     *     may be found.
     */
    private readonly Closure $packageVersionResolver;

    /**
     * @param list<string> $packages
     * @param (Closure(string): ?string)|null $packageVersionResolver
     *
     * @logion [OSD 69:22] Name the houses whose testimony thou requirest, and the archive shall examine their lineage
     *     before their words enter judgment.
     */
    public function __construct(array $packages, ?Closure $packageVersionResolver = null)
    {
        $this->packages = $packages;
        $this->packageVersionResolver = $packageVersionResolver ?? static function (string $package): ?string {
            if (!InstalledVersions::isInstalled($package)) {
                return null;
            }

            return InstalledVersions::getPrettyVersion($package) ?? InstalledVersions::getVersion($package);
        };
    }

    /**
     * @return list<string>
     *
     * @logion [SFA 43:6] Each admitted lineage yielded its appointed tablet in an order no accident could disturb.
     */
    public function getFiles(): array
    {
        $packages = array_values(array_unique($this->packages));
        sort($packages, SORT_STRING);

        $files = [];

        foreach ($packages as $package) {
            $configuration = self::SUPPORTED_PACKAGES[$package] ?? null;
            if ($configuration === null) {
                throw new InvalidArgumentException(sprintf(
                    'Unsupported Yumemi stub package "%s"; supported packages: %s.',
                    $package,
                    implode(', ', array_keys(self::SUPPORTED_PACKAGES)),
                ));
            }

            $version = ($this->packageVersionResolver)($package);
            if ($version === null) {
                throw new InvalidArgumentException(sprintf(
                    'Yumemi stubs for "%s" were enabled, but that Composer package is not installed.',
                    $package,
                ));
            }

            $major = $this->majorVersion($package, $version);
            if (!in_array($major, $configuration['majors'], true)) {
                throw new InvalidArgumentException(sprintf(
                    'Yumemi stubs for "%s" support major versions %s; installed version is %s.',
                    $package,
                    implode(', ', $configuration['majors']),
                    $version,
                ));
            }

            foreach ($configuration['files'] as $file) {
                $path = realpath($file);
                if ($path === false) {
                    throw new LogicException(sprintf('Configured Yumemi stub file does not exist: %s.', $file));
                }

                $files[$path] = true;
            }
        }

        return array_keys($files);
    }

    /**
     * @logion [AWC 49:15] When an uncertain reign was brought before the court, the first numeral of its record
     *     established the age unto which its judgment belonged.
     */
    private function majorVersion(string $package, string $version): int
    {
        if (preg_match('/^v?([1-9][0-9]*)(?:\.|$)/', $version, $matches) !== 1) {
            throw new InvalidArgumentException(sprintf(
                'Unable to determine the installed major version of Yumemi stub package "%s" from "%s".',
                $package,
                $version,
            ));
        }

        return (int) $matches[1];
    }
}
