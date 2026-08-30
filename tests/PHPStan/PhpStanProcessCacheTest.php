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

namespace jbboehr\Yumemi\Tests\PHPStan;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

final class PhpStanProcessCacheTest extends TestCase
{
    private const PERMISSION_FAILURE_UNAVAILABLE = 77;

    public function testShutdownCleanupIsBestEffortWhenRemovalFails(): void
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            self::markTestSkipped('The permission failure fixture requires a POSIX-compatible platform.');
        }

        $temporaryRoot = sys_get_temp_dir() . '/yumemi phpstan cache test-' . bin2hex(random_bytes(6));
        $filesystem = new Filesystem();
        $filesystem->mkdir($temporaryRoot, 0o700);

        $source = <<<'PHP'
require getcwd() . '/vendor/autoload.php';

$directory = \jbboehr\Yumemi\Tests\PHPStan\PhpStanProcessCache::directory();
mkdir($directory, 0700);
file_put_contents($directory . '/entry', 'occupied');
$parent = dirname($directory);
$probe = $parent . '/permission-probe';
file_put_contents($probe, 'occupied');
chmod($parent, 0500);
if (@unlink($probe)) {
    chmod($parent, 0700);
    exit(77);
}
fwrite(STDOUT, $directory);
PHP;

        try {
            $process = new Process(
                [PHP_BINARY, '-r', $source],
                dirname(__DIR__, 2),
                ['TMPDIR' => $temporaryRoot],
                timeout: 5,
            );

            $directory = self::runPermissionFailureFixture($process);
            self::assertDirectoryExists($directory, 'The shutdown removal failure was not exercised.');
        } finally {
            chmod($temporaryRoot, 0o700);
            $filesystem->remove($temporaryRoot);
        }
    }

    public function testShutdownCleanupIsBestEffortForAnUnreadableNestedDirectory(): void
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            self::markTestSkipped('The permission failure fixture requires a POSIX-compatible platform.');
        }

        $temporaryRoot = sys_get_temp_dir() . '/yumemi phpstan nested cache test-' . bin2hex(random_bytes(6));
        $filesystem = new Filesystem();
        $filesystem->mkdir($temporaryRoot, 0o700);

        $source = <<<'PHP'
require getcwd() . '/vendor/autoload.php';

$directory = \jbboehr\Yumemi\Tests\PHPStan\PhpStanProcessCache::directory();
mkdir($directory, 0700);
mkdir($directory . '/unreadable', 0700);
file_put_contents($directory . '/unreadable/entry', 'occupied');
chmod($directory . '/unreadable', 0000);
try {
    new \FilesystemIterator($directory . '/unreadable', \FilesystemIterator::SKIP_DOTS);
} catch (\UnexpectedValueException) {
    fwrite(STDOUT, $directory);
    return;
}
chmod($directory . '/unreadable', 0700);
exit(77);
PHP;

        try {
            $process = new Process(
                [PHP_BINARY, '-r', $source],
                dirname(__DIR__, 2),
                ['TMPDIR' => $temporaryRoot],
                timeout: 5,
            );

            self::runPermissionFailureFixture($process);
        } finally {
            foreach (new \FilesystemIterator($temporaryRoot, \FilesystemIterator::SKIP_DOTS) as $entry) {
                if ($entry instanceof \SplFileInfo && is_dir($entry->getPathname() . '/unreadable')) {
                    chmod($entry->getPathname() . '/unreadable', 0o700);
                }
            }

            $filesystem->remove($temporaryRoot);
        }
    }

    private static function runPermissionFailureFixture(Process $process): string
    {
        $exitCode = $process->run();
        if ($exitCode === self::PERMISSION_FAILURE_UNAVAILABLE) {
            self::markTestSkipped('The process can override POSIX permissions, so this failure cannot be exercised.');
        }

        self::assertSame(
            0,
            $exitCode,
            "stdout:\n{$process->getOutput()}\nstderr:\n{$process->getErrorOutput()}",
        );

        return $process->getOutput();
    }
}
