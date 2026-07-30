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
    /** @var list<string> */
    private const DOCUMENTS = [
        'README.md',
        'docs/pages/getting-started.md',
        'docs/pages/core-concepts.md',
        'docs/pages/reference/phpstan.md',
        'docs/pages/reference/unit-syntax.md',
        'docs/pages/reference/runtime.md',
        'docs/pages/reference/catalog.md',
    ];

    /**
     * @return list<array{id: string, label: string, code: string}>
     */
    public static function phpBlocks(): array
    {
        $blocks = [];

        foreach (self::DOCUMENTS as $document) {
            $path = self::projectRoot() . '/' . $document;
            $contents = file_get_contents($path);

            if ($contents === false) {
                throw new \RuntimeException('Unable to read ' . $path);
            }

            preg_match_all('/```php\s*\R(.*?)\R```/s', $contents, $matches, PREG_SET_ORDER);

            if ($matches === []) {
                throw new \RuntimeException($document . ' must contain at least one PHP example.');
            }

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

        return $blocks;
    }

    public static function projectRoot(): string
    {
        return dirname(__DIR__, 2);
    }
}
