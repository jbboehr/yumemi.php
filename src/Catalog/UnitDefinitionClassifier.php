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

/**
 * @internal
 */
final class UnitDefinitionClassifier
{
    public static function unsupportedReason(string $definition): ?UnsupportedUnitReason
    {
        if (str_contains($definition, '@')) {
            return UnsupportedUnitReason::Affine;
        }

        return str_contains($definition, 'lg(') ? UnsupportedUnitReason::Logarithmic : null;
    }

    /**
     * Inherit support metadata through exact-name definitions and aliases.
     *
     * @param array<string, mixed> $record
     * @param callable(string): (array<string, mixed>|null) $findRecord
     * @param array<string, true> $seen
     */
    public static function inheritedUnsupportedReason(
        array $record,
        callable $findRecord,
        array $seen = [],
    ): ?UnsupportedUnitReason {
        $unsupportedReason = $record['unsupportedReason'] ?? null;
        if (is_string($unsupportedReason)) {
            return UnsupportedUnitReason::from($unsupportedReason);
        }

        $name = $record['name'] ?? null;
        if (!is_string($name)) {
            return null;
        }

        if (isset($seen[$name])) {
            return null;
        }

        $targetName = $record['def'] ?? null;
        if (!is_string($targetName)) {
            return null;
        }

        $target = $findRecord($targetName);
        if ($target === null) {
            return null;
        }

        $seen[$name] = true;

        return self::inheritedUnsupportedReason($target, $findRecord, $seen);
    }
}
