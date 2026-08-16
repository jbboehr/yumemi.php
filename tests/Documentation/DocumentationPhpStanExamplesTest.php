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

use jbboehr\Akashi\Integration\PHPStan\PhpStanExampleConfiguration;
use jbboehr\Akashi\Integration\PHPStan\VerifiesPhpStanExamples;
use PHPStan\Rules\Functions\CallToFunctionParametersRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * Verifies documented static diagnostics through Akashi and PHPStan's real call rule.
 *
 * @extends RuleTestCase<CallToFunctionParametersRule>
 */
final class DocumentationPhpStanExamplesTest extends RuleTestCase
{
    use VerifiesPhpStanExamples;

    /**
     * Tokens that mark a documentation block as PHPStan-relevant rather than a pure runtime example.
     */
    private const UNIT_TOKENS = [
        '@akashi-phpstan-error',
        '//!',
        'unit_int<',
        'unit_float<',
        'unit_numeric_string<',
        "Quantity<'",
        '@yumemi-',
    ];

    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(CallToFunctionParametersRule::class); // @phpstan-ignore phpstanApi.classConstant
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [
            self::projectRoot() . '/extension.neon',
            self::projectRoot() . '/yumemi-tags.neon',
        ];
    }

    public function testPhpStanRelevantDocumentationExamplesMatchDocumentedDiagnostics(): void
    {
        $this->assertPhpStanExamples(
            DocumentationCorpus::load(),
            PhpStanExampleConfiguration::forTokens(DocumentationCorpus::projectRoot(), ...self::UNIT_TOKENS),
        );
    }

    private static function projectRoot(): string
    {
        return DocumentationCorpus::projectRoot();
    }
}
