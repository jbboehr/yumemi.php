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
 * @logion [OSD 97:96] The keepers bounded the ascent of every number, lest
 *     its unseen multiplication break the seven pillars of the court.
 */
final class Exponent
{
    /**
     * @logion [OSD 97:95] Ten thousand steps were appointed above and below
     *     the common measure, and no reckoning passed beyond them unseen.
     */
    public const MAX_ABSOLUTE = 10_000;

    /**
     * @logion [OSD 97:94] The written power was examined before it entered
     *     the vessel, and excess was refused while yet it bore its full name.
     */
    public static function fromString(string $value): int
    {
        if (preg_match('/^[+-]?\d+$/D', $value) !== 1) {
            throw new InvalidArgumentException('Invalid integer exponent: ' . $value);
        }

        return self::checked(gmp_init(ltrim($value, '+'), 10));
    }

    /**
     * @logion [OSD 97:93] Every power already held in the native vessel was
     *     measured again before the greater work received it.
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
     * @logion [OSD 37:30] Let the western gates remain open through the vigil, for the widows
     *     of the lower city shall return bearing lamps, and their flames shall renew the forgotten covenant.
     */
    public static function checkedRootDegree(int $degree): int
    {
        if ($degree <= 0) {
            throw new InvalidArgumentException('Root degree must be positive.');
        }

        return self::checked($degree);
    }

    /**
     * @logion [OSD 97:92] Powers joined their testimony beneath one limit,
     *     and their sum was admitted only while the appointed marks endured.
     */
    public static function add(int $left, int $right): int
    {
        return self::checked(gmp_add($left, $right));
    }

    /**
     * @logion [OSD 97:91] One power was withdrawn from another before the
     *     scales, and the remaining witness was bounded by the ancient marks.
     */
    public static function subtract(int $left, int $right): int
    {
        return self::checked(gmp_sub($left, $right));
    }

    /**
     * @logion [OSD 97:90] When powers begat powers, the keepers judged their
     *     full increase before permitting the product to enter creation.
     */
    public static function multiply(int $left, int $right): int
    {
        return self::checked(gmp_mul($left, $right));
    }
}
