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

use jbboehr\Yumemi\PHPStan\YumemiDocTagPromoter;
use PhpParser\Node\Stmt\Function_;
use PHPStan\Parser\Parser;
use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Component\Process\Process;

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

    public function testQuantityTagsRespectImportsAndForeignGenericContainers(): void
    {
        $output = $this->analyse('yumemi-tag-scoped-quantity.php');

        $this->assertStringContainsString('[OK] No errors', $output, $output);
    }

    public function testQuantityTagNamespaceBoundariesPreserveValidationLayers(): void
    {
        $output = $this->analyse('yumemi-tag-namespace-boundaries.php');

        $this->assertSame(2, substr_count($output, 'yumemi.docTagType'), $output);
        $this->assertStringContainsString('generics.lessTypes', $output, $output);
        $this->assertStringContainsString('[ERROR] Found 3 errors', $output, $output);
    }

    public function testTagImportsDoNotLeakBetweenParsedFiles(): void
    {
        $parser = self::getContainer()->getService('yumemiAnalysisParser');
        $this->assertInstanceOf(Parser::class, $parser);
        $first = $parser->parseString(<<<'PHP'
<?php
use jbboehr\Yumemi\Quantity as Distance;
/** @yumemi-param Distance<'meter'> $distance */
function firstFile(Distance $distance): void {}
PHP);
        $firstFunction = $first[1];
        $this->assertInstanceOf(Function_::class, $firstFunction);
        $this->assertNotNull($firstFunction->getDocComment());
        $this->assertStringContainsString('@phpstan-param', $firstFunction->getDocComment()->getText());
        $this->assertFalse($firstFunction->hasAttribute(YumemiDocTagPromoter::DIAGNOSTICS_ATTRIBUTE));

        $second = $parser->parseString(<<<'PHP'
<?php
/** @yumemi-param Distance<'meter'> $distance */
function secondFile(Distance $distance): void {}
PHP);
        $secondFunction = $second[0];
        $this->assertInstanceOf(Function_::class, $secondFunction);
        $this->assertNotNull($secondFunction->getDocComment());
        $this->assertStringNotContainsString('@phpstan-param', $secondFunction->getDocComment()->getText());
        $this->assertTrue($secondFunction->hasAttribute(YumemiDocTagPromoter::DIAGNOSTICS_ATTRIBUTE));
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

    /** @param array<string, string> $aliases */
    #[TestWith([['MeterValue' => "unit_float<'meter'>"]])]
    #[TestWith([['MeterValue' => 'MeterScalar', 'MeterScalar' => "unit_float<'meter'>"]])]
    public function testConfiguredTypeAliasInStubRetainsUnitConstraint(array $aliases): void
    {
        $output = $this->analyse(
            'yumemi-tag-stub-type-alias.php',
            stub: 'yumemi-tag-stub-type-alias.stub',
            typeAliases: $aliases,
        );

        $this->assertStringContainsString('[ERROR] Found 1 error', $output, $output);
        $this->assertStringContainsString('argument.type', $output, $output);
        $this->assertStringContainsString("unit_float<'meter'>", $output, $output);
        $this->assertStringContainsString("unit_float<'second'>", $output, $output);
    }

    public function testConfiguredAliasesPreserveNestedTypesAndGlobalScope(): void
    {
        $output = $this->analyse('yumemi-tag-configured-aliases.php', typeAliases: [
            'MeterValue' => "unit_float<'meter'>",
            'MeterStock' => 'Quantity<MeterValue>',
            'MeasuredDistance' => "\\jbboehr\\Yumemi\\Quantity<'meter'>",
            'MeterPacket' => 'array{distance: MeterValue}',
        ]);

        $this->assertSame(2, substr_count($output, 'yumemi.docTagType'), $output);
        $this->assertStringContainsString('[ERROR] Found 2 errors', $output, $output);
        $this->assertStringNotContainsString('phpstan.type', $output, $output);
    }

    public function testRepeatedNestedAliasInStubRetainsEveryUnitConstraint(): void
    {
        $output = $this->analyse(
            'yumemi-tag-stub-nested-type-alias.php',
            stub: 'yumemi-tag-stub-nested-type-alias.stub',
            typeAliases: [
                'MeterValue' => "unit_float<'meter'>",
                'MeterPair' => 'array{first: MeterValue, second: MeterValue}',
            ],
        );

        $this->assertStringContainsString('[ERROR] Found 1 error', $output, $output);
        $this->assertStringContainsString('argument.type', $output, $output);
        $this->assertStringContainsString("unit_float<'meter'>", $output, $output);
        $this->assertStringContainsString("unit_float<'second'>", $output, $output);
    }

    public function testConfiguredAliasValidatesEveryNestedUnitLeaf(): void
    {
        $output = $this->analyse('yumemi-tag-configured-alias-invalid-nested.php', typeAliases: [
            'MixedUnitPacket' => "array{valid: unit_float<'meter'>, invalid: unit_float<'not_a_real_unit_xyz'>}",
        ]);

        $this->assertSame(1, substr_count($output, 'yumemi.docTagType'), $output);
        $this->assertStringContainsString('[ERROR] Found 1 error', $output, $output);
        $this->assertStringContainsString('not_a_real_unit_xyz', $output, $output);
    }

    public function testConfiguredAliasDoesNotChangeFallbackMatching(): void
    {
        $output = $this->analyse('yumemi-tag-configured-alias-fallback.php', typeAliases: [
            'MeterValue' => "unit_float<'meter'>",
        ]);

        $this->assertSame(1, substr_count($output, 'yumemi.docTagTransform'), $output);
        $this->assertStringContainsString('[ERROR] Found 1 error', $output, $output);
    }

    public function testStubTagsRemainIgnoredWithoutTheOptInConfig(): void
    {
        $output = $this->analyse('yumemi-tag-stub.php', false, 'yumemi-tag-stub.stub');

        $this->assertStringContainsString('[OK] No errors', $output, $output);
    }

    /** @param array<string, string> $typeAliases */
    private function analyse(
        string $fixture,
        bool $withTagPromotion = true,
        ?string $stub = null,
        array $typeAliases = [],
    ): string {
        $fixturePath = __DIR__ . '/data/' . $fixture;
        $this->assertFileExists($fixturePath);

        $temporaryFile = tempnam(sys_get_temp_dir(), 'yumemi-tag-');
        $this->assertNotFalse($temporaryFile);
        $config = $temporaryFile . '.neon';
        $cache = PhpStanProcessCache::directory();

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

            $aliasConfig = $typeAliases === [] ? '' : "    typeAliases: " . json_encode($typeAliases, JSON_THROW_ON_ERROR) . "\n";
            $neon = <<<NEON
{$includes}parameters:
    level: max
    tmpDir: {$cache}
    paths:
        - {$fixturePath}
{$stubFiles}{$aliasConfig}    treatPhpDocTypesAsCertain: true
    reportUnmatchedIgnoredErrors: false
NEON;
            $this->assertNotFalse(file_put_contents($config, $neon));

            $phpstan = realpath(__DIR__ . '/../../vendor/bin/phpstan');
            $this->assertNotFalse($phpstan);

            $process = new Process([
                PHP_BINARY,
                $phpstan,
                'analyse',
                '--no-ansi',
                '--no-progress',
                '--debug',
                '--memory-limit=512M',
                '--error-format=table',
                '-c',
                $config,
            ], env: ['GITHUB_ACTIONS' => false], timeout: null);
            $process->run();
            $this->assertContains($process->getExitCode(), [0, 1], $process->getOutput() . $process->getErrorOutput());

            return $process->getOutput() . $process->getErrorOutput();
        } finally {
            @unlink($config);
            @unlink($temporaryFile);
        }
    }
}
