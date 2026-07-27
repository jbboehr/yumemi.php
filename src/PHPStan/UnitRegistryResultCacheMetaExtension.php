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

namespace jbboehr\Yumemi\PHPStan;

use jbboehr\Yumemi\Registry\UnitRegistry;
use PHPStan\Analyser\ResultCache\ResultCacheMetaExtension;

/**
 * Makes changes in configured registry data invalidate PHPStan's result cache.
 *
 * @internal
 */
final class UnitRegistryResultCacheMetaExtension implements ResultCacheMetaExtension
{
    public function __construct(
        private readonly UnitRegistry $registry,
    ) {
    }

    public function getKey(): string
    {
        return 'yumemi.unitRegistry';
    }

    public function getHash(): string
    {
        $names = $this->registry->names();
        sort($names, SORT_STRING);

        $entries = [];

        foreach ($names as $name) {
            $unit = $this->registry->lookup($name);

            $entries[$name] = [
                'record' => $this->normalize($this->registry->record($name)),
                'unit' => $unit === null ? null : [
                    'name' => $unit->name,
                    'definition' => $unit->definition?->toString(),
                ],
            ];
        }

        $prefixes = $this->registry->prefixes();
        ksort($prefixes, SORT_STRING);

        return hash('sha256', serialize([
            'entries' => $entries,
            'prefixes' => $prefixes,
        ]));
    }

    /**
     * @return mixed
     */
    private function normalize(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        if (!array_is_list($value)) {
            ksort($value, SORT_STRING);
        }

        return array_map($this->normalize(...), $value);
    }
}
