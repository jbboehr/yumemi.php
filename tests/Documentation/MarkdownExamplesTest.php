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

final class MarkdownExamplesTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/yumemi-markdown-examples-' . bin2hex(random_bytes(6));
        self::assertTrue(mkdir($this->root . '/docs/pages/reference', 0o777, true));
    }

    protected function tearDown(): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $path) {
            if (!$path instanceof \SplFileInfo) {
                continue;
            }

            $path->isDir() ? rmdir($path->getPathname()) : unlink($path->getPathname());
        }

        rmdir($this->root);
    }

    public function testDocumentsDiscoversNestedPublicPagesAndExcludesSummary(): void
    {
        file_put_contents($this->root . '/README.md', '# Project');
        file_put_contents($this->root . '/docs/pages/README.md', '# Introduction');
        file_put_contents($this->root . '/docs/pages/SUMMARY.md', '# Summary');
        file_put_contents($this->root . '/docs/pages/reference/runtime.md', '# Runtime');
        file_put_contents($this->root . '/docs/pages/reference/notes.txt', 'Not Markdown');

        self::assertSame(
            ['README.md', 'docs/pages/README.md', 'docs/pages/reference/runtime.md'],
            MarkdownExamples::documents($this->root),
        );
    }

    public function testMarkedCodeBlockExtractorReturnsOneIdentifiedPhpFence(): void
    {
        $file = $this->root . '/docs/pages/example.md';
        file_put_contents($file, <<<'MARKDOWN'
# Example

<!-- yumemi-example: selected-example -->

```php
<?php

echo 'selected';
```

```php
<?php

echo 'unselected';
```
MARKDOWN);

        self::assertSame(
            "<?php\n\necho 'selected';\n",
            MarkedCodeBlockExtractor::extract($file, 'selected-example'),
        );
    }

    public function testMarkedCodeBlockExtractorRejectsMissingExample(): void
    {
        $file = $this->root . '/docs/pages/example.md';
        file_put_contents($file, '# Example');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Expected exactly one documentation example missing-example');

        MarkedCodeBlockExtractor::extract($file, 'missing-example');
    }
}
