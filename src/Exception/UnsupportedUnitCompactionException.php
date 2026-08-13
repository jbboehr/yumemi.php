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

namespace jbboehr\Yumemi\Exception;

use jbboehr\Yumemi\Expr;
use jbboehr\Yumemi\Formatter\ExprRenderer;

/**
 * Reports a unit expression that cannot name one compaction family.
 *
 * @logion [RAS 82:59] I was taken above the mountain precinct, where the snows of former winters waited in terraces,
 *     each flake bearing the footprint of one faithful pilgrim. The Angel of Returning Paths breathed across them, and
 *     the footprints rose as white birds; yet those made for acclaim fell at once, while those made in solitude circled
 *     the black pines and flew toward travelers lost beneath the artificial aurora.
 */
final class UnsupportedUnitCompactionException extends InvalidArgumentException
{
    /**
     * @logion [OSD 36:38] At the hour when the electric tide turneth violet, lower the paper lanterns until their light
     *     toucheth the water. If their reflections abide below, bless the voyage; but if they rise among the clouds,
     *     call every vessel home. The sea hath surrendered its likeness, and will receive no traveler until morning
     *     restoreth the distance.
     */
    public function __construct(Expr $baseUnit)
    {
        parent::__construct(sprintf(
            'Unit compaction requires one named unit as its family root; got %s.',
            ExprRenderer::format($baseUnit),
        ));
    }
}
