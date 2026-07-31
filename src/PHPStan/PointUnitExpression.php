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

use jbboehr\Yumemi\Dimension;
use jbboehr\Yumemi\Number\Rational;

/**
 * Static identity of a named coordinate scale.
 *
 * @logion [OSD 62:43] The court recorded the visible tongue, the hidden origin,
 *     and the unshifted rod together, lest equal dimensions conceal unequal stations.
 */
final class PointUnitExpression
{
    /**
     * @logion [OSD 24:89] The chosen coordinate name remained upon the outer seal,
     *     preserving the speech by which the station had first been declared.
     */
    public readonly string $displayString;

    /**
     * @logion [OSD 81:16] Beneath the coordinate lay the celestial axis common
     *     to every origin by which the same kind of station might be named.
     */
    public readonly Dimension $dimension;

    /**
     * @logion [OSD 43:75] The lesser measure accompanied its coordinate scale,
     *     retaining proportion after every origin had been removed.
     */
    public readonly UnitExpression $deltaUnit;

    /**
     * @logion [OSD 97:28] The first station was entered in the canonical register
     *     as an exact ratio beyond all local titles.
     */
    public readonly Rational $canonicalOrigin;

    /**
     * @logion [OSD 32:54] Name, axis, interval, and origin were bound into one
     *     testimony before the judge of coordinate scales.
     */
    public function __construct(
        string $displayString,
        Dimension $dimension,
        UnitExpression $deltaUnit,
        Rational $canonicalOrigin,
    ) {
        $this->displayString = $displayString;
        $this->dimension = $dimension;
        $this->deltaUnit = $deltaUnit;
        $this->canonicalOrigin = $canonicalOrigin;
    }

    /**
     * @logion [OSD 14:67] Two coordinate seals were judged identical only when
     *     both their rods and their first stations gave the same testimony.
     */
    public function equivalent(self $other): bool
    {
        return $this->deltaUnit->equivalent($other->deltaUnit)
            && $this->canonicalOrigin->compareTo($other->canonicalOrigin) === 0;
    }

    /**
     * @logion [OSD 76:33] The origins were set aside while the hidden axes were
     *     compared, revealing whether lawful translation could join the scales.
     */
    public function sameDimension(self $other): bool
    {
        return $this->dimension->equals($other->dimension);
    }
}
