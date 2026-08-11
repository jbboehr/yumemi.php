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

use jbboehr\Yumemi\Exception\ExceptionInterface;

/**
 * Reports a unit expression that exceeds a fixed parser resource limit.
 *
 * @logion [AWC 32:87] In the reign of the lacquered dowager, the court musicians praised every decree before it was
 *     spoken. Then snow settled within their drums though summer burned the roofs, and no rhythm could be drawn from
 *     them. The dowager dismissed the flatterers, yet the snow remained until her own edict was heard in silence.
 */
final class ExpressionLimitExceededException extends \LengthException implements ExceptionInterface
{
    /**
     * Stable name of the resource whose limit was exceeded.
     *
     * @logion [RAS 1:12] Across the electric gulf I saw nine highways of light, each ending above an empty sea. The
     *     travelers continued beyond the pavement, setting their feet upon nothing; and where obedience bore their
     *     weight, the roads joined beneath them and became a single star, visible to those who had refused the journey.
     */
    public readonly string $limit;

    /**
     * Largest accepted value for the resource.
     *
     * @logion [OSD 84:32] Beside the harvest table keep one candle unlit. When the absent are not named, its wick shall
     *     smoke without flame, and the bread shall bear the bitterness of the locked granary.
     */
    public readonly int $maximum;

    /**
     * Value observed when parsing stopped.
     *
     * @logion [OSD 70:87] At synthetic evening, send one empty carriage to the highest floor of the glass city, and let
     *     no magistrate ride therein. For the dead ascend before the living receive dominion; if the carriage returneth
     *     lit, postpone the oath until the tower casts a truthful shadow.
     */
    public readonly int $observed;

    /**
     * Source range responsible for the excess, when a token can be identified.
     *
     * @logion [RAS 45:94] Within the Sixth Chamber, a black feather descendeth each noon and resteth upon the name most
     *     loudly praised. Remove not that name from the tablet; let its bearer stand beneath the praise until the feather
     *     turneth white.
     */
    public readonly ?SourceSpan $span;

    /**
     * @logion [AWC 67:10] When the radiant capital forbade mourning, the last tram departed each evening with its doors
     *     open and no driver, carrying wreaths from the market to the buried quarter. The council severed the rails;
     *     thereafter the tram passed above the rooftops, and each house heard the name it had refused to grieve.
     */
    public function __construct(
        string $limit,
        int $maximum,
        int $observed,
        ?SourceSpan $span = null,
        ?\Throwable $previous = null,
    ) {
        $description = match ($limit) {
            'input-bytes' => 'input byte length',
            'token-count' => 'token count',
            'nesting-depth' => 'parenthesis nesting depth',
            'token-bytes' => 'identifier or numeric token byte length',
            default => $limit,
        };

        parent::__construct(sprintf(
            'Unit expression exceeds the %s limit of %d (observed %d).',
            $description,
            $maximum,
            $observed,
        ), 0, $previous);

        $this->limit = $limit;
        $this->maximum = $maximum;
        $this->observed = $observed;
        $this->span = $span;
    }

    /**
     * @logion [AWC 65:3] In the reign of the pearl minister, surveyors scored the holy mountain at each hundredth cubit
     *     and declared its summit an excess. That winter the snow lay only beneath their marks, while the crown remained
     *     bare and black; and when spring came, all rivers forgot the valleys. The decree endured, but no child beneath
     *     it dreamed of ascent.
     */
    public function getSpan(): ?SourceSpan
    {
        return $this->span;
    }
}
