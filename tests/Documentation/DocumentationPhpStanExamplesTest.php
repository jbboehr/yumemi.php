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

use PHPStan\Rules\Functions\CallToFunctionParametersRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * Checks that documented static diagnostics actually fire, in-process.
 *
 * Companion to {@see DocumentationExamplesTest}, which executes every block at runtime. Here each
 * PHPStan-relevant block (one that mentions a unit type or a `//!` marker) is analysed with the
 * core extension and opt-in tag promotion loaded, and the convention read straight from the block body:
 *
 *   //! <substring>
 *   <the offending statement>
 *
 * asserts the analyser reports an error whose message (or tip) contains `<substring>` — so the
 * "…is rejected" comments are verified, not just decorative. A block with no `//!`
 * marker must analyse clean, which pins down the documented *good* code too.
 *
 * No subprocess: the diagnostics come from PHPStan's real {@see CallToFunctionParametersRule},
 * pulled out of the container after Yumemi's parser has promoted any custom tags. Each block is
 * `require`d into the process first so file-local
 * functions resolve in reflection (same reason {@see \jbboehr\Yumemi\Tests\PHPStan\YumemiReturnTagExtensionTest}
 * requires its fixtures); the blocks are already runtime-safe because DocumentationExamplesTest runs them.
 *
 * @extends RuleTestCase<CallToFunctionParametersRule>
 */
final class DocumentationPhpStanExamplesTest extends RuleTestCase
{
    private const MARKER = '//!';

    /**
     * Tokens that mark a documentation block as PHPStan-relevant (vs. a pure runtime example).
     */
    private const UNIT_TOKENS = ['unit_int<', 'unit_float<', "Quantity<'", '@yumemi-', self::MARKER];

    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(CallToFunctionParametersRule::class); // @phpstan-ignore phpstanApi.classConstant
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [
            MarkdownExamples::projectRoot() . '/extension.neon',
            MarkdownExamples::projectRoot() . '/yumemi-tags.neon',
        ];
    }

    public function testPhpStanRelevantDocumentationExamplesMatchDocumentedDiagnostics(): void
    {
        $blocks = self::phpStanBlocks();
        self::assertNotEmpty($blocks, 'Expected at least one PHPStan-relevant documentation code block.');

        $dir = self::analysisDir();
        $previousCwd = getcwd();

        try {
            // Blocks resolve `require 'vendor/autoload.php'` relative to the working directory.
            chdir(MarkdownExamples::projectRoot());

            $files = [];
            foreach ($blocks as $name => $block) {
                $file = $dir . '/' . $name . '.php';
                file_put_contents($file, $block['code']);
                // Declare the block's file-local functions so reflection can resolve calls to them.
                require_once $file;
                $files[$name] = $file;
            }

            foreach ($blocks as $name => $block) {
                $expected = self::markers($block['code']);
                $actual = self::errorsFor($this->gatherAnalyserErrors([$files[$name]]));

                $report = self::report($block['label'], $expected, $actual);

                self::assertCount(count($expected), $actual, $report);

                foreach ($expected as $substring) {
                    self::assertTrue(
                        self::anyErrorContains($actual, $substring),
                        "No PHPStan error containing:\n  {$substring}\n\n{$report}",
                    );
                }
            }
        } finally {
            if (is_string($previousCwd)) {
                chdir($previousCwd);
            }
            self::removeDir($dir);
        }
    }

    /**
     * Extract PHPStan-relevant ```php blocks, keyed by stable file-safe document/block identities.
     *
     * @return array<string, array{label: string, code: string}>
     */
    private static function phpStanBlocks(): array
    {
        $blocks = [];

        foreach (MarkdownExamples::phpBlocks() as $block) {
            foreach (self::UNIT_TOKENS as $token) {
                if (str_contains($block['code'], $token)) {
                    $blocks[$block['id']] = ['label' => $block['label'], 'code' => $block['code']];
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
     * Normalise analyser errors to the (message, tip) shape the assertions work with.
     *
     * @param list<\PHPStan\Analyser\Error> $errors
     *
     * @return list<array{message: string, tip: string}>
     */
    private static function errorsFor(array $errors): array
    {
        $out = [];

        foreach ($errors as $error) {
            $out[] = [
                'message' => $error->getMessage(),
                'tip' => $error->getTip() ?? '',
            ];
        }

        return $out;
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
        $dir = sys_get_temp_dir() . '/yumemi-documentation-phpstan-' . bin2hex(random_bytes(6));
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
}
