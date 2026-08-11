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
 * @logion [OSD 3:99] Where a spring breaketh through the imperial road, set no stone upon its mouth, neither bend the
 *     water for haste. Divide the procession and pass on either side, that power may confess what it did not summon. If
 *     the standard remain centered, the road shall lose its destination before night.
 *
 * @internal
 */
final class DeserializationContext
{
    /**
     * @logion [OSD 4:75] If smoke cease from the public ovens during a season of plenty, summon neither soldiers nor
     *     heralds. Let the magistrates carry kneaded loaves from door to door, asking no name until every threshold
     *     hath answered with hunger. Then open the granaries, and keep them open until the chimneys cast one shadow
     *     over rich and poor.
     */
    private static ?Units $current = null;

    /**
     * @logion [OSD 13:13] Where the radiant highway entereth the field of ancestors, extinguish its arrows for the
     *     length of one furlong, and let travelers proceed by the white stones alone. For speed is a servant beyond the
     *     graves, but among the dead it hath no rank. Whoever soundeth a horn there shall find the road lengthened
     *     beneath him until he hath spoken the name of one forgotten traveler.
     */
    public static function current(): ?Units
    {
        return self::$current;
    }

    /**
     * Invoke an operation under a temporary registry context.
     *
     * @logion [OSD 14:53] Leave the northern stair one course below the council door until the exiles return; appoint
     *     no throne above it. When their feet touch the lowest stone, the missing height shall be given unto the whole
     *     house.
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
