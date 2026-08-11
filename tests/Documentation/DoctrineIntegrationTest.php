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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DoctrineIntegrationTest extends TestCase
{
    #[DataProvider('adoptedDocumentProvider')]
    public function testAdoptedDocumentIsInstalled(string $filename): void
    {
        self::assertFileExists($this->packageRoot() . '/' . $filename);
    }

    #[DataProvider('codexAdapterProvider')]
    public function testCodexAdapterMatchesPinnedPackage(string $filename): void
    {
        $local = dirname(__DIR__, 2) . '/.codex/agents/' . $filename;
        $installed = $this->packageRoot() . '/integrations/codex/agents/' . $filename;

        self::assertFileExists($local);
        self::assertFileExists($installed);
        self::assertFileEquals($installed, $local);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function adoptedDocumentProvider(): iterable
    {
        foreach ([
            'DOCTRINE-STYLE-GUIDE.md',
            'DOCTRINE-CODING-GUIDE.md',
            'DOCTRINE-IMAGE-GUIDE.md',
            'DOCTRINE-GENERATION-GUIDE.md',
            'DOCTRINE-GOLD-EXEMPLARS.md',
            'MEASURE-OF-WORDS.md',
            'RUINENWERT.md',
        ] as $filename) {
            yield $filename => [$filename];
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function codexAdapterProvider(): iterable
    {
        foreach (['doctrine-writer.toml', 'doctrine-reviewer.toml'] as $filename) {
            yield $filename => [$filename];
        }
    }

    private function packageRoot(): string
    {
        return dirname(__DIR__, 2) . '/vendor/jbboehr/doctrine-of-the-second-sun';
    }
}
