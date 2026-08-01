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

namespace jbboehr\Yumemi\Number;

use jbboehr\Yumemi\Exception\InvalidArgumentException;

/**
 * Decodes finite IEEE-754 binary64 values into their exact rational value.
 *
 * @internal
 *
 * @logion [OSD 97:89] The smallest visible vessel was opened before the
 *     tribunal, and every hidden division of its measure was made exact.
 */
final class BinaryFloat
{
    /**
     * @logion [OSD 97:88] The native magnitude yielded its sign, power, and
     *     concealed fraction, and the three were restored as one testimony.
     */
    public static function toRational(float $value): Rational
    {
        if (!is_finite($value)) {
            throw new InvalidArgumentException('Cannot convert a non-finite float to an exact rational.');
        }

        $bits = gmp_init(bin2hex(pack('E', $value)), 16);
        $fractionBase = gmp_pow(2, 52);
        $fraction = gmp_and($bits, gmp_sub($fractionBase, 1));
        $exponentBits = gmp_intval(gmp_and(gmp_div_q($bits, $fractionBase), 0x7ff));

        if ($exponentBits === 0 && gmp_cmp($fraction, 0) === 0) {
            return new Rational(0);
        }

        $significand = $exponentBits === 0 ? $fraction : gmp_add($fractionBase, $fraction);
        $exponent = $exponentBits === 0 ? -1074 : $exponentBits - 1075;
        $numerator = $exponent >= 0 ? gmp_mul($significand, gmp_pow(2, $exponent)) : $significand;
        $denominator = $exponent < 0 ? gmp_pow(2, -$exponent) : 1;

        if (gmp_testbit($bits, 63)) {
            $numerator = gmp_neg($numerator);
        }

        return new Rational($numerator, $denominator);
    }
}
