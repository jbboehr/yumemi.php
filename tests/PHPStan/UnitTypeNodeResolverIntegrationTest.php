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

use jbboehr\Yumemi\Tests\PHPStan\Fixtures\ConfiguredUnitRegistryFactory;
use PHPUnit\Framework\TestCase;

/**
 * Integration: run PHPStan on fixture files with the Yumemi extension loaded.
 */
final class UnitTypeNodeResolverIntegrationTest extends TestCase
{
    public function testValidUnitPhpDocHasNoErrors(): void
    {
        $output = $this->analyse('unit-phpdoc-valid.php');

        $this->assertStringContainsString('[OK] No errors', $output, $output);
    }

    public function testInvalidUnitPhpDocReportsErrors(): void
    {
        $output = $this->analyse('unit-phpdoc-invalid.php');

        $this->assertStringNotContainsString('[OK] No errors', $output, $output);
        $this->assertStringContainsString('mass', $output);
        $this->assertStringContainsString(
            "Syntax error, unexpected '/' at line 1, column 9 (byte offset 8).",
            $output,
        );
        $this->assertStringContainsString('| meter * / second', $output);
        $this->assertStringContainsString('|         ^', $output);
        $this->assertStringContainsString(
            'Unit "B" uses logarithmic semantics',
            $output,
        );
        $this->assertTrue(
            str_contains($output, 'Unit not found')
            || str_contains($output, 'ERROR')
            || str_contains($output, 'invalid type'),
            $output,
        );
    }

    public function testUnitArithmeticFixtureReportsIncompatibleAdd(): void
    {
        $output = $this->analyse('unit-ops.php');

        // PHPStan InvalidBinaryOperationRule reports ErrorType as binaryOp.invalid;
        // our custom ErrorType reason is not surfaced in the message.
        $this->assertStringContainsString('binaryOp.invalid', $output, $output);
        $this->assertStringContainsString("unit_int<'meter'>", $output, $output);
        $this->assertStringContainsString("unit_int<'second'>", $output, $output);
        $this->assertStringContainsString("unit_float<'meter'>", $output, $output);
        $this->assertStringContainsString('Found 4 errors', $output, $output);
    }

    /**
     * Real-world formulas with native unit_float types: each result is passed to a
     * sink whose PHPDoc parameter carries the expected unit (normalized equality).
     *
     * Exactly one intentional error: foot is not assignable to meter (same
     * dimension, different scale after normalize).
     */
    public function testNativeRealWorldFormulasTypecheck(): void
    {
        $output = $this->analyse('unit-real-world-native.php');

        $this->assertStringContainsString('argument.type', $output, $output);
        $this->assertStringContainsString("unit_float<'meter'>", $output, $output);
        $this->assertTrue(
            str_contains($output, 'foot') || str_contains($output, 'international_foot'),
            $output,
        );
        $this->assertStringContainsString('normalized forms differ', $output, $output);
        $this->assertStringContainsString('Found 1 error', $output, $output);
    }

    public function testConfiguredRegistryIsUsedAcrossPhpStanIntegrations(): void
    {
        $output = $this->analyse('configured-unit-registry.php', ConfiguredUnitRegistryFactory::class);

        $this->assertStringContainsString('[OK] No errors', $output, $output);
    }

    public function testConfiguredPrimitiveDimensionsDoNotPermitImplicitNativeConversion(): void
    {
        $output = $this->analyse(
            'configured-custom-dimension-invalid.php',
            ConfiguredUnitRegistryFactory::class,
        );

        $this->assertStringContainsString('[ERROR] Found 2 errors', $output, $output);
        $this->assertStringContainsString("unit_float<'USD'>", $output, $output);
        $this->assertStringContainsString("unit_int<'EUR'>", $output, $output);
        $this->assertStringContainsString('normalized forms differ', $output, $output);
    }

    public function testIntegerOverflowPromotionCanBeDisabledThroughConfiguration(): void
    {
        $output = $this->analyse('unit-overflow-config.php', integerOverflowToFloat: false);

        $this->assertStringContainsString('[OK] No errors', $output, $output);
    }

    public function testUnitPreservingFunctionsDoNotOverrideNamespacedFunctions(): void
    {
        $output = $this->analyse('unit-scalar-transformations-namespaced.php');

        $this->assertStringContainsString('[OK] No errors', $output, $output);
    }

    public function testInvalidNativeUnitRootHasStableIdentifierAndLocalIgnore(): void
    {
        $output = $this->analyse('unit-root-function-invalid.php');

        $this->assertStringContainsString('yumemi.invalidUnitRoot', $output, $output);
        $this->assertStringContainsString('[ERROR] Found 1 error', $output, $output);
    }

    public function testInvalidNativeAngleFunctionHasStableIdentifierAndLocalIgnore(): void
    {
        $output = $this->analyse('unit-angle-function-invalid.php');

        $this->assertStringContainsString('yumemi.invalidUnitAngleFunction', $output, $output);
        $this->assertStringContainsString('[ERROR] Found 8 errors', $output, $output);
    }

    public function testBrandedIntegerRangesEnforceBoundsAndUnits(): void
    {
        $output = $this->analyse('unit-range-invalid.php');

        $this->assertStringContainsString('[ERROR] Found 4 errors', $output, $output);
        $this->assertStringContainsString("unit_int<'meter'>&int<0, 100>", $output, $output);
        $this->assertStringContainsString("101&unit_int<'meter'>", $output, $output);
        $this->assertStringContainsString("50&unit_int<'second'>", $output, $output);
        $this->assertStringContainsString('Bare int is not assignable', $output, $output);
    }

    public function testNumericStringBrandsRequireMatchingUnitsAndExplicitNumericCasts(): void
    {
        $output = $this->analyse('unit-numeric-string-invalid.php', errorFormat: 'json');
        $analysis = json_decode($output, true, flags: JSON_THROW_ON_ERROR);
        $this->assertIsArray($analysis);

        $totals = $analysis['totals'] ?? null;
        $this->assertIsArray($totals);
        $this->assertSame(3, $totals['file_errors'] ?? null);

        $files = $analysis['files'] ?? null;
        $this->assertIsArray($files);
        $this->assertCount(1, $files);

        $file = reset($files);
        $this->assertIsArray($file);
        $messages = $file['messages'] ?? null;
        $this->assertIsArray($messages);
        $this->assertCount(3, $messages);

        $this->assertPhpStanJsonDiagnostic(
            $messages[0] ?? null,
            12,
            "Parameter #1 \$duration of function acceptNumericDuration expects unit_numeric_string<'second'>, "
                . 'string given.',
            "Bare numeric string is not assignable to unit_numeric_string<'second'>; keep the unit annotation.",
        );
        $this->assertPhpStanJsonDiagnostic(
            $messages[1] ?? null,
            16,
            "Parameter #1 \$duration of function acceptNumericDuration expects unit_numeric_string<'second'>, "
                . "unit_numeric_string<'meter'> given.",
            "Unit unit_numeric_string<'meter'> is not assignable to unit_numeric_string<'second'> "
                . '(normalized forms differ).',
        );
        $this->assertPhpStanJsonDiagnostic(
            $messages[2] ?? null,
            27,
            "Parameter #1 \$seconds of function acceptIntegerDuration expects unit_int<'second'>, "
                . "unit_numeric_string<'second'> given.",
            "Unit unit_numeric_string<'second'> must be explicitly cast before assignment to unit_int<'second'>.",
        );
    }

    public function testWeakCoercionStillRequiresAnExplicitNumericCast(): void
    {
        $output = $this->analyse('unit-numeric-string-weak-coercion.php');

        $this->assertStringContainsString('[ERROR] Found 2 errors', $output, $output);
        $this->assertStringContainsString("unit_numeric_string<'second'>", $output, $output);
        $this->assertStringContainsString('expects int', $output, $output);
        $this->assertStringContainsString('expects float', $output, $output);
    }

    public function testInvalidConfiguredRegistryFactoryFailsAtStartup(): void
    {
        $output = $this->analyse('unit-phpdoc-valid.php', \stdClass::class);

        $this->assertStringContainsString('parameters.yumemi.registryFactory', $output, $output);
        $this->assertStringContainsString('must name a class implementing', $output, $output);
    }

    public function testQuantityBoundaryDiagnosticsHaveStableIdentifiers(): void
    {
        $output = $this->analyse('quantity-boundary-invalid.php');

        $this->assertStringContainsString('yumemi.invalidQuantityConstruction', $output, $output);
        $this->assertStringContainsString('yumemi.invalidQuantityConversion', $output, $output);
        $this->assertStringContainsString('Found 2 errors', $output, $output);
    }

    public function testQuantityComparisonDiagnosticsHaveStableIdentifier(): void
    {
        $output = $this->analyse('quantity-comparison-invalid.php');

        $this->assertStringContainsString('yumemi.invalidQuantityComparison', $output, $output);
        $this->assertStringContainsString('Found 5 errors', $output, $output);
    }

    public function testQuantityOperatorsRemainDisabledByDefault(): void
    {
        $output = $this->analyse('quantity-operators-default.php');

        $this->assertStringContainsString('binaryOp.invalid', $output, $output);
        $this->assertStringContainsString('[ERROR] Found 1 error', $output, $output);
    }

    public function testInvalidOptInQuantityOperatorsUseTheStandardBinaryOperationDiagnostic(): void
    {
        $output = $this->analyse('quantity-operators-invalid.php', quantityOperators: true);

        $this->assertStringContainsString('binaryOp.invalid', $output, $output);
        $this->assertStringContainsString('[ERROR] Found 6 errors', $output, $output);
    }

    public function testValidOptInQuantityOperatorsHaveNoDiagnostics(): void
    {
        $output = $this->analyse('quantity-operators.php', quantityOperators: true);

        $this->assertStringContainsString('[OK] No errors', $output, $output);
    }

    public function testNativeUnitExpressionDiagnosticsHaveStableIdentifiersAndLocalIgnores(): void
    {
        $output = $this->analyse('native-unit-expression-diagnostics.php');

        $this->assertStringContainsString('yumemi.dynamicUnitExpression', $output, $output);
        $this->assertStringContainsString('yumemi.ambiguousUnitExpression', $output, $output);
        $this->assertStringContainsString('yumemi.invalidUnitCall', $output, $output);
        $this->assertStringContainsString('identifier or numeric token byte length', $output, $output);
        $this->assertStringContainsString('(observed 1025)', $output, $output);
        $this->assertStringContainsString('Found 5 errors', $output, $output);
    }

    public function testNativeDynamicUnitExpressionDiagnosticCanBeDisabled(): void
    {
        $output = $this->analyse(
            'native-unit-expression-diagnostics.php',
            requireConstantNativeUnitExpressions: false,
        );

        $this->assertStringNotContainsString('yumemi.dynamicUnitExpression', $output, $output);
        $this->assertStringContainsString('yumemi.ambiguousUnitExpression', $output, $output);
        $this->assertStringContainsString('yumemi.invalidUnitCall', $output, $output);
        $this->assertStringContainsString('Found 2 errors', $output, $output);
    }

    private function analyse(
        string $fixture,
        ?string $registryFactory = null,
        ?bool $integerOverflowToFloat = null,
        ?bool $requireConstantNativeUnitExpressions = null,
        bool $quantityOperators = false,
        string $errorFormat = 'table',
    ): string {
        $fixturePath = __DIR__ . '/data/' . $fixture;
        $this->assertFileExists($fixturePath);

        $temporaryFile = tempnam(sys_get_temp_dir(), 'yumemi-phpstan-');
        $this->assertNotFalse($temporaryFile);
        $config = $temporaryFile . '.neon';
        $cache = PhpStanProcessCache::directory();
        $stderr = $temporaryFile . '.stderr';

        try {
            $this->assertTrue(rename($temporaryFile, $config));
            $extension = realpath(__DIR__ . '/../../extension.neon');
            $this->assertNotFalse($extension);
            $includes = "    - {$extension}";
            if ($quantityOperators) {
                $operatorExtension = realpath(__DIR__ . '/../../yumemi-operators.neon');
                $this->assertNotFalse($operatorExtension);
                $includes .= "\n    - {$operatorExtension}";
            }

            $yumemiOptions = [];
            if ($registryFactory !== null) {
                $yumemiOptions[] = '        registryFactory: ' . $registryFactory;
            }
            if ($integerOverflowToFloat !== null) {
                $yumemiOptions[] = '        integerOverflowToFloat: '
                    . ($integerOverflowToFloat ? 'true' : 'false');
            }
            if ($requireConstantNativeUnitExpressions !== null) {
                $yumemiOptions[] = '        requireConstantNativeUnitExpressions: '
                    . ($requireConstantNativeUnitExpressions ? 'true' : 'false');
            }
            $yumemi = $yumemiOptions === []
                ? ''
                : "    yumemi:\n" . implode("\n", $yumemiOptions);

            $neon = <<<NEON
includes:
{$includes}
parameters:
    level: max
    tmpDir: {$cache}
    paths:
        - {$fixturePath}
    reportUnmatchedIgnoredErrors: false
{$yumemi}
NEON;
            $this->assertNotFalse(file_put_contents($config, $neon));

            $phpstan = realpath(__DIR__ . '/../../vendor/bin/phpstan');
            $this->assertNotFalse($phpstan);

            $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($phpstan)
                . ' analyse --no-ansi --no-progress --memory-limit=512M --error-format '
                . escapeshellarg($errorFormat) . ' '
                . escapeshellarg('-c') . ' ' . escapeshellarg($config)
                . ($errorFormat === 'json' ? ' 2>' . escapeshellarg($stderr) : ' 2>&1');

            $githubActions = getenv('GITHUB_ACTIONS');
            putenv('GITHUB_ACTIONS');

            try {
                $output = shell_exec($command);
            } finally {
                if ($githubActions === false) {
                    putenv('GITHUB_ACTIONS');
                } else {
                    putenv('GITHUB_ACTIONS=' . $githubActions);
                }
            }

            return is_string($output) ? $output : '';
        } finally {
            @unlink($config);
            @unlink($stderr);
            @unlink($temporaryFile);
        }
    }

    private function assertPhpStanJsonDiagnostic(
        mixed $diagnostic,
        int $line,
        string $message,
        string $tip,
    ): void {
        $this->assertIsArray($diagnostic);
        $this->assertSame($line, $diagnostic['line'] ?? null);
        $this->assertSame('argument.type', $diagnostic['identifier'] ?? null);
        $this->assertSame($message, $diagnostic['message'] ?? null);
        $this->assertSame($tip, $diagnostic['tip'] ?? null);
    }
}
