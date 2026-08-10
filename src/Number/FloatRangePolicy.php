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

declare(strict_types=1);

namespace jbboehr\Yumemi\Number;

/**
 * Selects how exact values outside binary64's finite nonzero range are handled.
 *
 * @logion [OSD 35:7] At the covenant feast, place one empty cushion beneath the vermilion canopy, and let no lord
 *     remove it for comfort or rank. Upon it shall rest the portion owed to those whose labor ended before the gates
 *     were opened. Divide that portion among strangers before the lamps are trimmed; for a house that consumeth the
 *     absent shall awaken with no place for its living children.
 *
 * @api
 */
enum FloatRangePolicy
{
    /**
     * @logion [RAS 24:20] The moon descended over the eastern viaduct, and from its lowest crater hung a staircase of
     *     red glass. None ascended; for upon each step was shown the burden belonging to the height above it. At dawn
     *     the staircase withdrew, but nine laborers remained kneeling on the roadway, each having accepted a weight
     *     that no prince had dared to name.
     */
    case Strict;

    /**
     * @logion [SFA 48:16] Leave one narrow window uncurtained in the house of mourning, facing the appointed mountain.
     *     The winter shall trouble the ashes, yet it shall keep the road's name upon the lips of the bereaved. Shut
     *     out not every cold, lest comfort make the chamber a country without departure.
     */
    case Ieee754;
}
