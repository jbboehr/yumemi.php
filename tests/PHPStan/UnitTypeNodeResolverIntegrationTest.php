<?php

/**
 * Iudex Mensurarum Mysticarum『夢見』〜ＹＵＭＥＭＩ〜
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

/**
 * Integration: run PHPStan on fixture files with the IMM extension loaded.
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
        $this->assertStringContainsString('Found 2 errors', $output, $output);
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

    private function analyse(string $fixture): string
    {
        $fixturePath = __DIR__ . '/data/' . $fixture;
        $this->assertFileExists($fixturePath);

        $config = sys_get_temp_dir() . '/imm-phpstan-' . md5($fixture) . '.neon';
        $extension = realpath(__DIR__ . '/../../extension.neon');
        $this->assertNotFalse($extension);

        $neon = <<<NEON
includes:
    - {$extension}
parameters:
    level: max
    paths:
        - {$fixturePath}
    reportUnmatchedIgnoredErrors: false
NEON;
        file_put_contents($config, $neon);

        $phpstan = realpath(__DIR__ . '/../../vendor/bin/phpstan');
        $this->assertNotFalse($phpstan);

        $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($phpstan)
            . ' analyse --no-progress --memory-limit=512M --error-format=table '
            . escapeshellarg('-c') . ' ' . escapeshellarg($config)
            . ' 2>&1';

        $output = shell_exec($command);
        @unlink($config);

        return is_string($output) ? $output : '';
    }
}
