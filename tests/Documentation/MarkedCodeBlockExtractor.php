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

final class MarkedCodeBlockExtractor
{
    public static function extract(string $file, string $id): string
    {
        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $id) !== 1) {
            throw new \InvalidArgumentException('Invalid documentation example identifier: ' . $id);
        }

        $contents = file_get_contents($file);
        if ($contents === false) {
            throw new \RuntimeException('Unable to read documentation file ' . $file);
        }

        $marker = '<!--\s*yumemi-example:\s*' . preg_quote($id, '/') . '\s*-->';
        $pattern = '/' . $marker . '[^\S\r\n]*(?:\R[^\S\r\n]*)*```php[^\S\r\n]*\R(.*?)\R```/s';
        preg_match_all($pattern, $contents, $matches);

        if (count($matches[1]) !== 1) {
            throw new \RuntimeException(sprintf(
                'Expected exactly one documentation example %s in %s; found %d.',
                $id,
                $file,
                count($matches[1]),
            ));
        }

        return $matches[1][0] . "\n";
    }
}
