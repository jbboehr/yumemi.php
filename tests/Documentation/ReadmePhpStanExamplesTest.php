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

namespace jbboehr\Yumemi\Tests\Documentation;

use PHPUnit\Framework\TestCase;

/**
 * Runs the PHPStan-relevant README code blocks through the real extension and checks that the
 * documented static diagnostics actually fire.
 *
 * Companion to {@see ReadmeExamplesTest}, which executes every block at runtime. Here each
 * PHPStan-relevant block (one that mentions a unit type or a `//!` marker) is written to a temp
 * file and analysed at level max with extension.neon loaded. The convention read straight from the
 * block body:
 *
 *   //! <substring>
 *   <the offending statement>
 *
 * asserts the analyser reports an error whose message (or tip) contains `<substring>` — so the
 * "…is rejected" comments in the README are verified, not just decorative. A block with no `//!`
 * marker must analyse clean, which pins down the documented *good* code too.
 */
final class ReadmePhpStanExamplesTest extends TestCase
{
    private const MARKER = '//!';

    /**
     * Tokens that mark a README block as PHPStan-relevant (vs. a pure runtime example).
     */
    private const UNIT_TOKENS = ["unit_int<", "unit_float<", "Quantity<'", '@yumemi-', self::MARKER];

    public function testPhpStanRelevantReadmeExamplesMatchDocumentedDiagnostics(): void
    {
        $blocks = self::phpStanBlocks();
        self::assertNotEmpty($blocks, 'Expected at least one PHPStan-relevant README code block.');

        $dir = self::analysisDir();

        try {
            foreach ($blocks as $name => $code) {
                file_put_contents($dir . '/' . $name . '.php', $code);
            }

            $errorsByBasename = self::analyse($dir);

            foreach ($blocks as $name => $code) {
                $expected = self::markers($code);
                $actual = $errorsByBasename[$name . '.php'] ?? [];

                $report = self::report($name, $expected, $actual);

                self::assertCount(count($expected), $actual, $report);

                foreach ($expected as $substring) {
                    self::assertTrue(
                        self::anyErrorContains($actual, $substring),
                        "No PHPStan error containing:\n  {$substring}\n\n{$report}",
                    );
                }
            }
        } finally {
            self::removeDir($dir);
        }
    }

    /**
     * Extract PHPStan-relevant ```php blocks from the README, keyed by a stable file-safe name that
     * encodes the block's overall position (so a failure points back to a specific example).
     *
     * @return array<string, string>
     */
    private static function phpStanBlocks(): array
    {
        $readme = self::projectRoot() . '/README.md';
        $contents = file_get_contents($readme);

        if ($contents === false) {
            throw new \RuntimeException('Unable to read ' . $readme);
        }

        preg_match_all('/```php\s*\R(.*?)\R```/s', $contents, $matches, PREG_SET_ORDER);

        $blocks = [];

        foreach ($matches as $index => $match) {
            $code = $match[1];

            foreach (self::UNIT_TOKENS as $token) {
                if (str_contains($code, $token)) {
                    $blocks[sprintf('example-%02d', $index + 1)] = $code;
                    break;
                }
            }
        }

        return $blocks;
    }

    /**
     * Pull the `//!` expectations out of a block body, in order.
     *
     * @return list<string>
     */
    private static function markers(string $code): array
    {
        $expected = [];

        $lines = preg_split('/\R/', $code);
        if ($lines === false) {
            return $expected;
        }

        foreach ($lines as $line) {
            if (preg_match('/^\s*' . preg_quote(self::MARKER, '/') . '\s?(.*\S)\s*$/', $line, $m) === 1) {
                $expected[] = $m[1];
            }
        }

        return $expected;
    }

    /**
     * @param list<array{message: string, tip: string}> $errors
     */
    private static function anyErrorContains(array $errors, string $substring): bool
    {
        foreach ($errors as $error) {
            if (str_contains($error['message'] . "\n" . $error['tip'], $substring)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Run PHPStan once over the whole temp dir and return reported errors grouped by file basename.
     *
     * @return array<string, list<array{message: string, tip: string}>>
     */
    private static function analyse(string $dir): array
    {
        $extension = realpath(self::projectRoot() . '/extension.neon');
        self::assertNotFalse($extension);

        $functions = realpath(self::projectRoot() . '/src/functions.php');
        self::assertNotFalse($functions);

        $config = $dir . '/phpstan.neon';
        $neon = <<<NEON
            includes:
                - {$extension}
            parameters:
                level: max
                paths:
                    - {$dir}
                scanFiles:
                    - {$functions}
                reportUnmatchedIgnoredErrors: false
            NEON;
        file_put_contents($config, $neon);

        $phpstan = realpath(self::projectRoot() . '/vendor/bin/phpstan');
        self::assertNotFalse($phpstan);

        [$stdout, $stderr, $exitCode] = self::runProcess([
            PHP_BINARY,
            $phpstan,
            'analyse',
            '--no-progress',
            '--memory-limit=512M',
            '--error-format=json',
            '-c',
            $config,
        ]);

        $decoded = json_decode($stdout, true);
        self::assertIsArray(
            $decoded,
            "PHPStan did not emit JSON (exit {$exitCode}).\nSTDOUT:\n{$stdout}\nSTDERR:\n{$stderr}",
        );

        $files = $decoded['files'] ?? null;
        if (!is_array($files)) {
            return [];
        }

        $byBasename = [];

        foreach ($files as $path => $fileData) {
            if (!is_array($fileData) || !is_array($fileData['messages'] ?? null)) {
                continue;
            }

            foreach ($fileData['messages'] as $message) {
                if (!is_array($message)) {
                    continue;
                }

                $byBasename[basename((string) $path)][] = [
                    'message' => self::asString($message['message'] ?? null),
                    'tip' => self::asString($message['tip'] ?? null),
                ];
            }
        }

        return $byBasename;
    }

    private static function asString(mixed $value): string
    {
        return is_string($value) ? $value : '';
    }

    /**
     * @param list<string> $command
     *
     * @return array{string, string, int}
     */
    private static function runProcess(array $command): array
    {
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptors, $pipes, self::projectRoot());
        self::assertIsResource($process);

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        return [is_string($stdout) ? $stdout : '', is_string($stderr) ? $stderr : '', $exitCode];
    }

    /**
     * @param list<string> $expected
     * @param list<array{message: string, tip: string}> $actual
     */
    private static function report(string $name, array $expected, array $actual): string
    {
        $lines = ["Block {$name}:", '  expected (//! markers):'];

        foreach ($expected as $substring) {
            $lines[] = '    - ' . $substring;
        }

        $lines[] = '  reported by PHPStan:';

        if ($actual === []) {
            $lines[] = '    (none)';
        }

        foreach ($actual as $error) {
            $lines[] = '    - ' . $error['message'] . ($error['tip'] !== '' ? ' [tip: ' . $error['tip'] . ']' : '');
        }

        return implode("\n", $lines);
    }

    private static function analysisDir(): string
    {
        $dir = sys_get_temp_dir() . '/yumemi-readme-phpstan-' . bin2hex(random_bytes(6));
        self::assertTrue(mkdir($dir, 0o777, true) && is_dir($dir), 'Unable to create temp analysis dir.');

        return $dir;
    }

    private static function removeDir(string $dir): void
    {
        $files = glob($dir . '/*');
        if ($files === false) {
            $files = [];
        }

        foreach ($files as $file) {
            @unlink($file);
        }

        @rmdir($dir);
    }

    private static function projectRoot(): string
    {
        return dirname(__DIR__, 2);
    }
}
