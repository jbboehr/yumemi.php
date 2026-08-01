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

/**
 * Explicit manifest and extractor for Markdown documents whose PHP fences are executable contracts.
 */
final class MarkdownExamples
{
    /**
     * @return list<array{id: string, label: string, code: string}>
     */
    public static function phpBlocks(): array
    {
        $blocks = [];

        foreach (self::documents() as $document) {
            $path = self::projectRoot() . '/' . $document;
            $contents = file_get_contents($path);

            if ($contents === false) {
                throw new \RuntimeException('Unable to read ' . $path);
            }

            preg_match_all('/```php\s*\R(.*?)\R```/s', $contents, $matches, PREG_SET_ORDER);

            foreach ($matches as $index => $match) {
                $number = $index + 1;
                $label = sprintf('%s PHP example %d', $document, $number);
                $blocks[] = [
                    'id' => sprintf('example-%s-%02d', substr(sha1($document), 0, 12), $number),
                    'label' => $label,
                    'code' => $match[1],
                ];
            }
        }

        if ($blocks === []) {
            throw new \RuntimeException('The public documentation must contain at least one PHP example.');
        }

        return $blocks;
    }

    /**
     * Discover the root README and every public mdBook source page in deterministic order.
     *
     * @return list<string>
     */
    public static function documents(?string $projectRoot = null): array
    {
        $projectRoot = rtrim($projectRoot ?? self::projectRoot(), DIRECTORY_SEPARATOR);
        $pagesRoot = $projectRoot . '/docs/pages';

        if (!is_dir($pagesRoot)) {
            throw new \RuntimeException('Unable to find public documentation directory ' . $pagesRoot);
        }

        $pages = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($pagesRoot, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo || !$file->isFile() || $file->getExtension() !== 'md') {
                continue;
            }

            $relative = str_replace(
                DIRECTORY_SEPARATOR,
                '/',
                substr($file->getPathname(), strlen($projectRoot) + 1),
            );
            if ($relative === 'docs/pages/SUMMARY.md') {
                continue;
            }

            $pages[] = $relative;
        }

        sort($pages, SORT_STRING);

        return ['README.md', ...$pages];
    }

    public static function projectRoot(): string
    {
        return dirname(__DIR__, 2);
    }
}
