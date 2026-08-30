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
use Symfony\Component\Filesystem\Filesystem;

final class GeneratedDocumentationLinkCheckerTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/yumemi-documentation-links-' . bin2hex(random_bytes(6));
        self::assertTrue(mkdir($this->root . '/reference', 0o777, true));
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->root);
    }

    public function testAcceptsExistingLocalTargetsFragmentsAssetsAndExternalUrls(): void
    {
        file_put_contents($this->root . '/banner.png', 'image');
        file_put_contents($this->root . '/reference/runtime.html', '<h2 id="exact-values">Exact Values</h2>');
        file_put_contents($this->root . '/index.html', <<<'HTML'
<a href="reference/runtime.html#exact-values">Runtime</a>
<a href="https://example.com/missing">External</a>
<img src="/banner.png" alt="Banner">
HTML);

        self::assertSame([], (new GeneratedDocumentationLinkChecker())->check($this->root));
    }

    public function testRejectsDirectoryWithoutHtmlFiles(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Generated documentation directory contains no HTML files.');

        (new GeneratedDocumentationLinkChecker())->check($this->root);
    }

    public function testReportsMissingTargetsAndFragmentsInStableOrder(): void
    {
        file_put_contents($this->root . '/reference/runtime.html', '<h2 id="present">Runtime</h2>');
        file_put_contents($this->root . '/index.html', <<<'HTML'
<a href="reference/runtime.html#missing">Missing fragment</a>
<a href="absent.html">Missing page</a>
HTML);

        self::assertSame(
            [
                'index.html: href="absent.html" does not exist',
                'index.html: href="reference/runtime.html#missing" references missing fragment #missing',
            ],
            (new GeneratedDocumentationLinkChecker())->check($this->root),
        );
    }
}
