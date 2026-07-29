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
    public static function classify(string $definition): UnitSemantics
    {
        if (str_contains($definition, '@')) {
            return UnitSemantics::Affine;
        }

        return str_contains($definition, 'lg(')
            ? UnitSemantics::Logarithmic
            : UnitSemantics::Multiplicative;
    }

    /**
     * Inherit non-multiplicative semantics through exact-name definitions and aliases.
     *
     * @param array<string, mixed> $record
     * @param callable(string): (array<string, mixed>|null) $findRecord
     * @param array<string, true> $seen
     */
    public static function inheritedSemantics(
        array $record,
        callable $findRecord,
        array $seen = [],
    ): UnitSemantics {
        $semantics = $record['semantics'] ?? null;
        if (is_string($semantics)) {
            return UnitSemantics::from($semantics);
        }

        $name = $record['name'] ?? null;
        if (!is_string($name)) {
            return UnitSemantics::Multiplicative;
        }

        if (isset($seen[$name])) {
            return UnitSemantics::Multiplicative;
        }

        $targetName = $record['def'] ?? null;
        if (!is_string($targetName)) {
            return UnitSemantics::Multiplicative;
        }

        $directSemantics = self::classify($targetName);
        if ($directSemantics === UnitSemantics::Affine || $directSemantics === UnitSemantics::Logarithmic) {
            return $directSemantics;
        }

        $target = $findRecord($targetName);
        if ($target === null) {
            return UnitSemantics::Multiplicative;
        }

        $seen[$name] = true;

        return self::inheritedSemantics($target, $findRecord, $seen);
    }
}
