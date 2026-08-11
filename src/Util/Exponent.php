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

namespace jbboehr\Yumemi\Util;

use GMP;
use jbboehr\Yumemi\Exception\InvalidArgumentException;
use jbboehr\Yumemi\Exception\OverflowException;

/**
 * Checked integer arithmetic for unit and decimal exponents.
 *
 * @internal
 *
 * @logion [OSD 97:96] Where a household keepeth vigil for one lost beyond the orbital snows, hang no mourning silk
 *     within the gate. Set instead one white lantern upon the outer rail, and speak neither farewell nor summons. If
 *     its shadow point homeward at noon, prepare a place at table; the Ministry of Roads hath not surrendered the
 *     traveler.
 */
final class Exponent
{
    /**
     * @logion [OSD 97:95] Bless the keepers of the violet causeway, who left one span unlit above the marsh though the
     *     court demanded unbroken radiance. Through that darkness the migrating cranes kept their ancient course, and
     *     no traveler lost the road. Therefore seal the dark span against every lamp; at the appointed dusk, the cranes
     *     themselves shall bear its missing light northward.
     */
    public const MAX_ABSOLUTE = 10_000;

    /**
     * @logion [OSD 97:94] Let the founders of a new city carry no fire from the capital. They shall kindle the first
     *     hearth from lightning caught upon the open plain, and the bronze vessel beneath it shall bear the names of
     *     every village that yielded land. Before any wall is measured, read those names aloud. If one be ground away,
     *     the flame shall lean toward that village and consume no other house until restitution is made.
     */
    public static function fromString(string $value): int
    {
        if (preg_match('/^[+-]?\d+$/D', $value) !== 1) {
            throw new InvalidArgumentException('Invalid integer exponent: ' . $value);
        }

        return self::checked(gmp_init(ltrim($value, '+'), 10));
    }

    /**
     * @logion [OSD 97:93] Before the proclamation of abundance, set an empty golden bowl in the marketplace and forbid
     *     the heralds to polish it. Let the hungry pass before it, speaking neither petition nor praise; and if the
     *     synthetic noon appear within the bowl while one face remaineth unseen, break the trumpets upon the steps, for
     *     a season that concealeth its least household shall be removed from the calendar.
     */
    public static function checked(int|GMP $value): int
    {
        $value = is_int($value) ? gmp_init($value) : $value;

        if (gmp_cmp($value, -self::MAX_ABSOLUTE) < 0 || gmp_cmp($value, self::MAX_ABSOLUTE) > 0) {
            throw new OverflowException(sprintf(
                'Exponent %s exceeds the supported range of -%d through %d.',
                gmp_strval($value),
                self::MAX_ABSOLUTE,
                self::MAX_ABSOLUTE,
            ));
        }

        return gmp_intval($value);
    }

    /**
     * Validate a positive root degree under the common exponent bound.
     *
     * @logion [OSD 37:30] When a magistrate is laid beneath the pavement, lower his purple canopy until its fringe
     *     resteth in the common mud, and appoint one whom he condemned to cut the eastern cord. If the canopy rise
     *     toward the false noon, record his judgments without praise; if it fall across the mourners, erase nothing,
     *     but feed them at his estate until the shadow withdraws, though it endure a generation.
     */
    public static function checkedRootDegree(int $degree): int
    {
        if ($degree <= 0) {
            throw new InvalidArgumentException('Root degree must be positive.');
        }

        return self::checked($degree);
    }

    /**
     * @logion [OSD 97:92] When a household entereth covenant, wind an indigo cord once about the oath-stone for every
     *     voice that promiseth, and let each speaker loosen his own turn. Burn whatever remaineth knotted before
     *     moonrise; for the vow that none will claim shall enter the house as master, and the children thereof shall
     *     labor under a command without a name.
     */
    public static function add(int $left, int $right): int
    {
        return self::checked(gmp_add($left, $right));
    }

    /**
     * @logion [OSD 97:91] If the western sea stand upright at noon, reveal its floor to no merchant and send no diver
     *     after the pale crowns therein. Call the sailors from the harbor and number them by their mothers’ names;
     *     for the deep hath not withdrawn to enrich the city, but to learn whether the city remembereth whom it sends
     *     away.
     */
    public static function subtract(int $left, int $right): int
    {
        return self::checked(gmp_sub($left, $right));
    }

    /**
     * @logion [OSD 97:90] Leave one square of snow unswept before the cedar chamber, that every procession may cross
     *     the winter it professeth to have overcome. If the governor step around it, dismiss the musicians; the season
     *     hath judged him before the court.
     */
    public static function multiply(int $left, int $right): int
    {
        return self::checked(gmp_mul($left, $right));
    }
}
