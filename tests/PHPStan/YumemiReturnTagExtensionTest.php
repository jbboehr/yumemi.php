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

use PHPStan\Testing\TypeInferenceTestCase;

// The @yumemi-return functions must exist in the process for native function reflection to resolve
// them: TypeInferenceTestCase does not index functions declared in the analysed data fixture.
require_once __DIR__ . '/Fixtures/YumemiTagReturnFunctions.php';

/**
 * Type-inference and end-to-end coverage for parser-level @yumemi-* promotion.
 */
final class YumemiReturnTagExtensionTest extends TypeInferenceTestCase
{
    use AssertsFixtureUnderCoverage;

    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../../extension.neon',
            __DIR__ . '/../../yumemi-tags.neon',
        ];
    }

    public function testFileAsserts(): void
    {
        $this->assertFixtureUnderCoverage(__DIR__ . '/data/yumemi-tag-return.php');
    }

    /**
     * The brand is not cosmetic: it flows into PHPStan's ordinary argument checking, so a
     * unit_int<'international_foot'> result is rejected at a unit_int<'meter'> parameter.
     *
     * Run through the real CLI because this asserts on emitted diagnostics rather than a single
     * expression type, and the fixture calls a function local to the analysed file.
     */
    public function testBrandedReturnIsEnforcedAtCallSites(): void
    {
        $output = $this->analyse('yumemi-tag-return-enforced.php');

        $this->assertStringNotContainsString('[OK] No errors', $output, $output);
        $this->assertStringContainsString('argument.type', $output, $output);
        $this->assertStringContainsString("unit_int<'meter'>", $output, $output);
        $this->assertStringContainsString("unit_int<'international_foot'>", $output, $output);
    }

    public function testPromotedParamTagsUsePhpStanCoreChecking(): void
    {
        $output = $this->analyse('yumemi-tag-call-enforcement.php');

        $this->assertStringContainsString('[ERROR] Found 3 errors', $output, $output);
        $this->assertStringContainsString("unit_int<'meter'>", $output, $output);
        $this->assertStringContainsString("unit_int<'international_foot'>", $output, $output);
    }

    public function testPromotedCallableUnionPreservesItsBrandedReturnContract(): void
    {
        $output = $this->analyse('yumemi-tag-callable-enforcement.php');

        $this->assertStringContainsString('[ERROR] Found 1 error', $output, $output);
        $this->assertStringContainsString('argument.type', $output, $output);
        $this->assertStringContainsString("unit_int<'1/1000 * second'>", $output, $output);
        $this->assertStringContainsString('Closure(int, mixed): 250 given.', $output, $output);
    }

    public function testTagsRemainIgnoredWithoutTheOptInConfig(): void
    {
        $output = $this->analyse('yumemi-tag-no-extension.php', false);

        $this->assertStringContainsString('[OK] No errors', $output, $output);
    }

    public function testPhpStanChecksPromotedTypesAgainstNativeSignatures(): void
    {
        $output = $this->analyse('yumemi-tag-native-mismatch.php');

        $this->assertStringContainsString('parameter.phpDocType', $output, $output);
        $this->assertStringContainsString('return.phpDocType', $output, $output);
        $this->assertStringContainsString("unit_float<'meter'>", $output, $output);
    }

    public function testStubParserPromotesTags(): void
    {
        $output = $this->analyse('yumemi-tag-stub.php', true, 'yumemi-tag-stub.stub');

        $this->assertStringContainsString('[ERROR] Found 1 error', $output, $output);
        $this->assertStringContainsString("unit_int<'meter'>", $output, $output);
    }

    public function testStubTagsRemainIgnoredWithoutTheOptInConfig(): void
    {
        $output = $this->analyse('yumemi-tag-stub.php', false, 'yumemi-tag-stub.stub');

        $this->assertStringContainsString('[OK] No errors', $output, $output);
    }

    private function analyse(string $fixture, bool $withTagPromotion = true, ?string $stub = null): string
    {
        $fixturePath = __DIR__ . '/data/' . $fixture;
        $this->assertFileExists($fixturePath);

        $temporaryFile = tempnam(sys_get_temp_dir(), 'yumemi-tag-');
        $this->assertNotFalse($temporaryFile);
        $config = $temporaryFile . '.neon';

        try {
            $this->assertTrue(rename($temporaryFile, $config));
            $extension = realpath(__DIR__ . '/../../extension.neon');
            $this->assertNotFalse($extension);
            $includes = "includes:\n    - {$extension}\n";
            if ($withTagPromotion) {
                $tagExtension = realpath(__DIR__ . '/../../yumemi-tags.neon');
                $this->assertNotFalse($tagExtension);
                $includes .= "    - {$tagExtension}\n";
            }

            $stubFiles = '';
            if ($stub !== null) {
                $stubPath = realpath(__DIR__ . '/data/' . $stub);
                $this->assertNotFalse($stubPath);
                $bootstrapPath = realpath(__DIR__ . '/Fixtures/YumemiTagStubFunctions.php');
                $this->assertNotFalse($bootstrapPath);
                $stubFiles = "    bootstrapFiles:\n        - {$bootstrapPath}\n    stubFiles:\n        - {$stubPath}\n";
            }

            $neon = <<<NEON
{$includes}parameters:
    level: max
    paths:
        - {$fixturePath}
{$stubFiles}    treatPhpDocTypesAsCertain: true
    reportUnmatchedIgnoredErrors: false
NEON;
            $this->assertNotFalse(file_put_contents($config, $neon));

            $phpstan = realpath(__DIR__ . '/../../vendor/bin/phpstan');
            $this->assertNotFalse($phpstan);

            $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($phpstan)
                . ' analyse --no-progress --memory-limit=512M --error-format=table '
                . escapeshellarg('-c') . ' ' . escapeshellarg($config)
                . ' 2>&1';

            $output = shell_exec($command);

            return is_string($output) ? $output : '';
        } finally {
            @unlink($config);
            @unlink($temporaryFile);
        }
    }
}
