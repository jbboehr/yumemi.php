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
     * @return iterable<string, array{string, string, string}>
     */
    public static function supportedPackageVersions(): iterable
    {
        yield 'Cache on Laravel 11 development branch' => [
            'illuminate/cache',
            '11.x-dev',
            'illuminate-cache.stub',
        ];
        yield 'HTTP on Laravel 12 tagged release' => [
            'illuminate/http',
            'v12.4.1',
            'illuminate-http.stub',
        ];
        yield 'HTTP on Laravel 13 normalized version' => [
            'illuminate/http',
            '13.0.0.0',
            'illuminate-http.stub',
        ];
    }

    #[DataProvider('supportedPackageVersions')]
    public function testSupportedVersionReturnsStubFile(string $package, string $version, string $file): void
    {
        $extension = $this->extension([$package], $package, $version);

        self::assertSame([
            realpath(__DIR__ . '/../../stubs/' . $file),
        ], $extension->getFiles());
    }

    public function testPackagesReturnStubFilesInDeterministicOrder(): void
    {
        $resolved = [];
        $extension = new ConfiguredStubFilesExtension(
            ['illuminate/http', 'illuminate/cache'],
            static function (string $package) use (&$resolved): string {
                $resolved[] = $package;

                return '12.0.0';
            },
        );

        self::assertSame([
            realpath(__DIR__ . '/../../stubs/illuminate-cache.stub'),
            realpath(__DIR__ . '/../../stubs/illuminate-http.stub'),
        ], $extension->getFiles());
        self::assertSame(['illuminate/cache', 'illuminate/http'], $resolved);
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
            'Unsupported Yumemi stub package "illuminate/database"; supported packages: illuminate/cache, illuminate/http.',
        );

        $extension->getFiles();
    }

    public function testMissingPackageIsRejected(): void
    {
        $extension = $this->extension(['illuminate/http'], 'illuminate/http', null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Yumemi stubs for "illuminate/http" were enabled, but that Composer package is not installed.',
        );

        $extension->getFiles();
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function unsupportedVersions(): iterable
    {
        yield 'Cache on Laravel 10' => ['illuminate/cache', '10.48.0'];
        yield 'HTTP on future Laravel major' => ['illuminate/http', '14.x-dev'];
    }

    #[DataProvider('unsupportedVersions')]
    public function testUnsupportedMajorVersionIsRejected(string $package, string $version): void
    {
        $extension = $this->extension([$package], $package, $version);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'Yumemi stubs for "%s" support major versions 11, 12, 13; installed version is %s.',
            $package,
            $version,
        ));

        $extension->getFiles();
    }

    public function testUnparseableVersionIsRejected(): void
    {
        $extension = $this->extension(['illuminate/http'], 'illuminate/http', 'dev-main');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Unable to determine the installed major version of Yumemi stub package "illuminate/http" from "dev-main".',
        );

        $extension->getFiles();
    }

    /**
     * @param list<string> $packages
     */
    private function extension(array $packages, string $expectedPackage, ?string $version): ConfiguredStubFilesExtension
    {
        return new ConfiguredStubFilesExtension(
            $packages,
            static function (string $package) use ($expectedPackage, $version): ?string {
                self::assertSame($expectedPackage, $package);

                return $version;
            },
        );
    }
}
