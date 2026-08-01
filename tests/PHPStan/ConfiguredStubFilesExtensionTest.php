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

namespace jbboehr\Yumemi\Tests\PHPStan;

use jbboehr\Yumemi\PHPStan\ConfiguredStubFilesExtension;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ConfiguredStubFilesExtensionTest extends TestCase
{
    public function testEmptyConfigurationDoesNotResolvePackages(): void
    {
        $extension = new ConfiguredStubFilesExtension([], static function (): never {
            self::fail('The package resolver must not run for an empty configuration.');
        });

        self::assertSame([], $extension->getFiles());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function supportedVersions(): iterable
    {
        yield 'Laravel 11 development branch' => ['11.x-dev'];
        yield 'Laravel 12 tagged release' => ['v12.4.1'];
        yield 'Laravel 13 normalized version' => ['13.0.0.0'];
    }

    #[DataProvider('supportedVersions')]
    public function testSupportedVersionReturnsStubFile(string $version): void
    {
        $extension = $this->extension(['illuminate/cache'], $version);

        self::assertSame([
            realpath(__DIR__ . '/../../stubs/illuminate-cache.stub'),
        ], $extension->getFiles());
    }

    public function testDuplicatePackagesReturnOneStubFile(): void
    {
        $calls = 0;
        $extension = new ConfiguredStubFilesExtension(
            ['illuminate/cache', 'illuminate/cache'],
            static function (string $package) use (&$calls): string {
                self::assertSame('illuminate/cache', $package);
                ++$calls;

                return '12.0.0';
            },
        );

        self::assertCount(1, $extension->getFiles());
        self::assertSame(1, $calls);
    }

    public function testUnknownPackageIsRejectedBeforeVersionResolution(): void
    {
        $extension = new ConfiguredStubFilesExtension(['illuminate/database'], static function (): never {
            self::fail('Unsupported package names must be rejected before version resolution.');
        });

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Unsupported Yumemi stub package "illuminate/database"; supported packages: illuminate/cache.',
        );

        $extension->getFiles();
    }

    public function testMissingPackageIsRejected(): void
    {
        $extension = $this->extension(['illuminate/cache'], null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Yumemi stubs for "illuminate/cache" were enabled, but that Composer package is not installed.',
        );

        $extension->getFiles();
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function unsupportedVersions(): iterable
    {
        yield 'Laravel 10' => ['10.48.0'];
        yield 'future Laravel major' => ['14.x-dev'];
    }

    #[DataProvider('unsupportedVersions')]
    public function testUnsupportedMajorVersionIsRejected(string $version): void
    {
        $extension = $this->extension(['illuminate/cache'], $version);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'Yumemi stubs for "illuminate/cache" support major versions 11, 12, 13; installed version is %s.',
            $version,
        ));

        $extension->getFiles();
    }

    public function testUnparseableVersionIsRejected(): void
    {
        $extension = $this->extension(['illuminate/cache'], 'dev-main');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Unable to determine the installed major version of Yumemi stub package "illuminate/cache" from "dev-main".',
        );

        $extension->getFiles();
    }

    /**
     * @param list<string> $packages
     */
    private function extension(array $packages, ?string $version): ConfiguredStubFilesExtension
    {
        return new ConfiguredStubFilesExtension(
            $packages,
            static function (string $package) use ($version): ?string {
                self::assertSame('illuminate/cache', $package);

                return $version;
            },
        );
    }
}
