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

namespace jbboehr\Yumemi\Tests\Architecture;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RuntimeDependencyDirectionTest extends TestCase
{
    private const ADAPTER_NAMESPACE = 'jbboehr\\Yumemi\\PHPStan';
    private const PHPSTAN_NAMESPACE = 'PHPStan';

    public function testRuntimeSourceDoesNotDependOnPhpStan(): void
    {
        $violations = [];

        foreach (self::runtimeSourceFiles() as $file) {
            $source = file_get_contents($file);
            self::assertNotFalse($source, 'Could not read runtime source: ' . $file);

            foreach (self::forbiddenReferences($source) as $reference) {
                $violations[] = sprintf(
                    '%s:%d references %s',
                    self::relativePath($file),
                    $reference['line'],
                    $reference['name'],
                );
            }
        }

        self::assertSame(
            [],
            $violations,
            "Runtime source must not depend on PHPStan or Yumemi's PHPStan adapter:\n" . implode("\n", $violations),
        );
    }

    /**
     * @param list<string> $expected
     */
    #[DataProvider('referenceProvider')]
    public function testScannerRecognizesCodeDependenciesButIgnoresComments(string $source, array $expected): void
    {
        self::assertSame(
            $expected,
            array_column(self::forbiddenReferences($source), 'name'),
        );
    }

    /**
     * @return iterable<string, array{string, list<string>}>
     */
    public static function referenceProvider(): iterable
    {
        yield 'external import' => [
            "<?php\nuse PHPStan\\Type\\Type;",
            ['PHPStan\\Type\\Type'],
        ];
        yield 'yumemi adapter import' => [
            "<?php\nuse jbboehr\\Yumemi\\PHPStan\\UnitExpression;",
            ['jbboehr\\Yumemi\\PHPStan\\UnitExpression'],
        ];
        yield 'fully qualified reference' => [
            "<?php\n\\PHPStan\\Type\\Type::class;",
            ['\\PHPStan\\Type\\Type'],
        ];
        yield 'namespace relative adapter reference' => [
            "<?php\nnamespace jbboehr\\Yumemi;\nPHPStan\\UnitExpression::class;",
            ['PHPStan\\UnitExpression'],
        ];
        yield 'grouped adapter import' => [
            "<?php\nuse jbboehr\\Yumemi\\PHPStan\\{UnitExpression, UnitFloatType};",
            ['jbboehr\\Yumemi\\PHPStan'],
        ];
        yield 'class name string' => [
            "<?php\nclass_exists('PHPStan\\Type\\Type');",
            ["'PHPStan\\Type\\Type'"],
        ];
        yield 'comments are not dependencies' => [
            "<?php\n/** PHPStan\\Type\\Type */\n// jbboehr\\Yumemi\\PHPStan\\UnitExpression\nfinal class Example {}",
            [],
        ];
        yield 'unrelated namespace segment' => [
            "<?php\nuse App\\PHPStanSupport\\Type;",
            [],
        ];
    }

    /**
     * @return list<array{line: int, name: string}>
     */
    private static function forbiddenReferences(string $source): array
    {
        $references = [];
        $nameTokenIds = [
            T_NAME_QUALIFIED,
            T_NAME_FULLY_QUALIFIED,
            T_NAME_RELATIVE,
            T_STRING,
            T_CONSTANT_ENCAPSED_STRING,
            T_ENCAPSED_AND_WHITESPACE,
        ];

        foreach (token_get_all($source, TOKEN_PARSE) as $token) {
            if (!is_array($token) || !in_array($token[0], $nameTokenIds, true)) {
                continue;
            }

            if (self::isForbiddenReference($token[1])) {
                $references[] = [
                    'line' => $token[2],
                    'name' => $token[1],
                ];
            }
        }

        return $references;
    }

    private static function isForbiddenReference(string $token): bool
    {
        $name = trim($token);
        if (
            strlen($name) >= 2
            && ($name[0] === "'" || $name[0] === '"')
            && $name[-1] === $name[0]
        ) {
            $name = substr($name, 1, -1);
        }

        $name = ltrim($name, '\\');

        return $name === self::PHPSTAN_NAMESPACE
            || str_starts_with($name, self::PHPSTAN_NAMESPACE . '\\')
            || $name === self::ADAPTER_NAMESPACE
            || str_starts_with($name, self::ADAPTER_NAMESPACE . '\\');
    }

    /**
     * @return list<string>
     */
    private static function runtimeSourceFiles(): array
    {
        $sourceRoot = dirname(__DIR__, 2) . '/src';
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(
            $sourceRoot,
            \FilesystemIterator::SKIP_DOTS,
        ));
        $files = [];

        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo || !$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $path = $file->getPathname();
            $relative = substr($path, strlen($sourceRoot) + 1);
            if (str_starts_with($relative, 'PHPStan' . DIRECTORY_SEPARATOR)) {
                continue;
            }

            $files[] = $path;
        }

        sort($files, SORT_STRING);

        return $files;
    }

    private static function relativePath(string $file): string
    {
        return substr($file, strlen(dirname(__DIR__, 2)) + 1);
    }
}
