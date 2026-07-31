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

namespace jbboehr\Yumemi\Internal;

use jbboehr\Yumemi\Units;

/**
 * Dynamically scoped registry context for native PHP deserialization.
 *
 * @logion [OSD 3:99] The borrowed seal remained above the opened archive until
 *     every enclosed testimony had received its lawful inheritance.
 *
 * @internal
 */
final class DeserializationContext
{
    /**
     * @logion [OSD 4:75] Beneath the present seal the hidden register waited,
     *     yielding again to its predecessor when the reading was complete.
     */
    private static ?Units $current = null;

    /**
     * @logion [OSD 13:13] The innermost seal alone governed the opened leaf,
     *     while every elder authority waited beyond the veil.
     */
    public static function current(): ?Units
    {
        return self::$current;
    }

    /**
     * Invoke an operation under a temporary registry context.
     *
     * @logion [OSD 14:53] The appointed court received the sealed volume for one
     *     reading, then restored the former keys even when judgment failed.
     *
     * @template T
     *
     * @param callable(): T $callback
     *
     * @return T
     */
    public static function run(Units $units, callable $callback): mixed
    {
        $previous = self::$current;
        self::$current = $units;

        try {
            return $callback();
        } finally {
            self::$current = $previous;
        }
    }
}
