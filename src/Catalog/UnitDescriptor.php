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

final readonly class UnitDescriptor
{
    /**
     * @param list<string> $aliases
     * @param list<string> $symbols
     * @param list<string> $explicitPlurals
     * @param list<string> $generatedPlurals
     */
    public function __construct(
        public string $matchedName,
        public string $canonicalName,
        public CatalogNameKind $matchedAs,
        public UnitKind $kind,
        public ?string $definitionExpression = null,
        public ?string $documentation = null,
        public ?string $comment = null,
        public array $aliases = [],
        public array $symbols = [],
        public array $explicitPlurals = [],
        public array $generatedPlurals = [],
    ) {
    }

    /**
     * @return list<string>
     */
    public function plurals(): array
    {
        return [...$this->explicitPlurals, ...$this->generatedPlurals];
    }
}
