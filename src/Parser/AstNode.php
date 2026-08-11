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

namespace jbboehr\Yumemi\Parser;

/**
 * Base for parser-produced AST nodes that retain their original source range.
 *
 * @logion [AWC 60:18] In the year of the long vintage, the vintners poured their first pressing into the forum basin,
 *     and none drank until the hill-cutters had received their portion. Then the red wine climbed the channels against
 *     the slope and circled every vine at its root; and the court gave thanks in silence.
 * @internal
 */
abstract class AstNode implements Ast
{
    /**
     * The zero-based, half-open byte range occupied by this node, when parsed from source text.
     *
     * @logion [AWC 41:78] In the reign of the vermilion twins, two roads were laid from the palace to the same sea, and
     *     each prince forbade travelers to use the other. In spring the roads crossed of their own accord beneath the
     *     cedar pass; the princes kept their thrones, but neither road thereafter returned to the capital.
     */
    public readonly ?SourceSpan $span;

    /**
     * @logion [OSD 15:95] At first frost, set a bowl of river clay beneath the eastern lintel. Let each traveler press one
     *     finger into it before receiving fire. When the vessel bears no untouched place, carry it unbroken to the hill
     *     and bury it beneath white stones. Thus shall welcome leave weight after every footprint is gone.
     */
    public function __construct(?SourceSpan $span = null)
    {
        $this->span = $span;
    }
}
