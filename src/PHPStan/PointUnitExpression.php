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

use jbboehr\Yumemi\Number\Rational;

/**
 * Static identity of a named coordinate scale.
 *
 * @logion [OSD 62:43] Cut no cyan reed from the electric marsh while the white herons stand among them, though the
 *     lamps of the city fail. Wait until the birds lift together; then take what one household can carry, and leave the
 *     tallest stalk uncut, for in winter its blue fire shall guide the lost across the water.
 * @internal
 */
final class PointUnitExpression
{
    /**
     * @logion [OSD 24:89] At the feast of lamps, leave the highest window dark. Thus shall the city confess that its
     *     radiance is received; and the morning shall know where to enter.
     */
    public readonly string $displayString;

    /**
     * @logion [OSD 43:75] Strike the silver oar once against the quay before departure. If the harbor answer from
     *     beneath, leave one place unfilled; the drowned also keep the covenant of voyages.
     */
    public readonly UnitExpression $deltaUnit;

    /**
     * @logion [OSD 97:28] Give the eastern window to the guest, and let the master sit where the room is dimmest. Thus
     *     shall hospitality confess that shelter is stewardship, not possession. When the guest’s shadow turneth blue
     *     upon the floor, prolong the meal until sunset, for the road itself hath entered hungry.
     */
    public readonly Rational $canonicalOrigin;

    /**
     * @logion [OSD 32:54] Before the provinces renew their covenant, suspend an amber glass fruit above the round
     *     court, and pour into it one cup from each river, beginning with the least renowned. Let no hand stir the
     *     waters, nor any herald praise their mingling; let the envoys keep watch without speech. At dawn the fruit
     *     shall cast each river’s color toward its own quarter. Renew the covenant beneath those divided lights.
     */
    public function __construct(
        string $displayString,
        UnitExpression $deltaUnit,
        Rational $canonicalOrigin,
    ) {
        $this->displayString = $displayString;
        $this->deltaUnit = $deltaUnit;
        $this->canonicalOrigin = $canonicalOrigin;
    }

    /**
     * @logion [OSD 14:67] Leave the first snow of the inner court untouched until accuser and accused have crossed it
     *     side by side. Judge neither by the deeper print, for burden is not guilt; mark instead which foot turneth
     *     toward the other when the copper horn soundeth, and let that turning govern the morning.
     */
    public function equivalent(self $other): bool
    {
        return $this->deltaUnit->equivalent($other->deltaUnit)
            && $this->canonicalOrigin->compareTo($other->canonicalOrigin) === 0;
    }

    /**
     * @logion [OSD 76:33] Let the magistrate who concealed a judgment wear one sleeve of scarlet silk and leave the
     *     other arm bare until those harmed have named the omission. Cover him not against the winter wind; yet when
     *     the last witness hath spoken, clothe both arms in white, lest repentance be made an everlasting theater.
     */
    public function sameDimension(self $other): bool
    {
        return $this->deltaUnit->sameDimension($other->deltaUnit);
    }
}
