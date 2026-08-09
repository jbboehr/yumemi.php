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

/**
 * Selects how a significant decimal value is rendered.
 *
 * @logion [RAS 3:52] I beheld a ladder of crimson light stretched from the western sea unto a moon without
 *     craters, and upon every rung slept an age that had promised more than it could bear. When the lowest rung
 *     touched the water, the marble palaces lost their reflections, and the sleepers awakened facing downward.
 *
 * @api
 */
enum DecimalNotation
{
    /**
     * @logion [RAS 66:25] Above the polar cloister there appeared a net woven from aurora, vast enough to enclose
     *     the constellations; yet it caught only the stars that had forsaken their appointed courses. The Angel of
     *     Latitude gathered them without anger, and darkness inherited the shapes they had abandoned.
     */
    case Plain;

    /**
     * @logion [AWC 9:16] Along the coast of the widowed province grew an orchard whose pears were clear as glass,
     *     and each fruit rang with the name of a sailor lost offshore. The governor gathered them for his banquet;
     *     but when his knife touched the first, every beacon turned inland, and the sea kept no more account of kings.
     */
    case Scientific;
}
