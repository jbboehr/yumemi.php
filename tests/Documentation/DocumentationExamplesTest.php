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

use jbboehr\Akashi\Example;
use jbboehr\Akashi\ExampleCorpus;
use jbboehr\Akashi\Execution\RuntimeConfiguration;
use jbboehr\Akashi\Integration\PhpUnit\PhpUnitExampleDataSets;
use jbboehr\Akashi\Integration\PhpUnit\PhpUnitRuntime;
use jbboehr\Akashi\Source\MarkdownSource;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Executes every PHP fence in the public documentation through Akashi.
 */
final class DocumentationExamplesTest extends TestCase
{
    #[DataProvider('documentationExampleProvider')]
    public function testDocumentationPhpExamplesExecute(Example $example): void
    {
        $configuration = RuntimeConfiguration::forProject(self::projectRoot())
            ->withBootstrap('vendor/autoload.php');

        PhpUnitRuntime::assertExample($example, $configuration);
    }

    /**
     * @return iterable<string, array{Example}>
     */
    public static function documentationExampleProvider(): iterable
    {
        yield from PhpUnitExampleDataSets::fromCorpus(self::documentationCorpus());
    }

    private static function documentationCorpus(): ExampleCorpus
    {
        return MarkdownSource::forProject(self::projectRoot())
            ->includeFile('README.md')
            ->includeDirectory('docs/pages')
            ->exclude('docs/pages/SUMMARY.md')
            ->load();
    }

    private static function projectRoot(): string
    {
        return dirname(__DIR__, 2);
    }
}
