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
 * @logion [AWC 60:18] After the northern hall burned, its people found one blue thread unconsumed beneath the dais. For
 *     thirty winters each household added a strand, until the banner again crossed the square. No name was woven upon
 *     it. So the dispossessed entered together, and remembrance denied every claimant a solitary crown.
 */
abstract class AstNode implements Ast
{
    /**
     * The zero-based, half-open byte range occupied by this node, when parsed from source text.
     *
     * @logion [AWC 41:78] When the coast city was taken, its judges buried the bronze bells beneath the steps of the
     *     petition hall. Returning generations heard them ringing under roots, though no hand moved them. They raised the
     *     bells above the market, and their first voice each morning belonged forever to the unnamed dead.
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
