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

namespace jbboehr\Yumemi\Catalog;

use Brick\VarExporter\VarExporter;

/**
 * @internal
 */
final class PhpCatalogExporter
{
    /**
     * @param array<string, mixed> $catalog
     */
    public function export(array $catalog, string $header = ''): string
    {
        $header = str_replace(["\r\n", "\r"], "\n", $header);

        if ($header !== '') {
            $header = "\n" . $header . "\n";
        }

        $tokens = token_get_all('<?php ' . VarExporter::export($catalog));
        array_shift($tokens);

        $export = '';
        foreach ($tokens as $token) {
            if (is_array($token)) {
                [$id, $text] = $token;
                if ($id === T_WHITESPACE || $id === T_COMMENT || $id === T_DOC_COMMENT) {
                    $text = str_replace(["\r\n", "\r"], "\n", $text);
                }
            } else {
                $text = $token;
            }

            $export .= $text;
        }

        return "<?php\n" . $header . "\nreturn " . $export . ";\n";
    }
}
