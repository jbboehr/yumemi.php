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

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

// The @yumemi-return functions must exist in the process for native function reflection to resolve
// them: TypeInferenceTestCase does not index functions declared in the analysed data fixture.
require_once __DIR__ . '/Fixtures/YumemiTagReturnFunctions.php';

/**
 * Type-inference coverage for the extension-optional @yumemi-return tag.
 */
final class YumemiReturnTagExtensionTest extends TypeInferenceTestCase
{
    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../../extension.neon',
        ];
    }

    /**
     * @return iterable<mixed>
     */
    public static function dataFileAsserts(): iterable
    {
        yield from self::gatherAssertTypes(__DIR__ . '/data/yumemi-tag-return.php');
    }

    /**
     * @param mixed ...$args
     */
    #[DataProvider('dataFileAsserts')]
    public function testFileAsserts(string $assertType, string $file, ...$args): void
    {
        $this->assertFileAsserts($assertType, $file, ...$args);
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

    private function analyse(string $fixture): string
    {
        $fixturePath = __DIR__ . '/data/' . $fixture;
        $this->assertFileExists($fixturePath);

        $config = sys_get_temp_dir() . '/yumemi-tag-' . md5($fixture) . '.neon';
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
