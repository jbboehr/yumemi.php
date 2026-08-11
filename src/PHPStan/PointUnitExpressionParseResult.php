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

use jbboehr\Yumemi\Exception\LogicException;
use jbboehr\Yumemi\Parser\SourceSpan;

/**
 * Result of parsing a point-coordinate unit for static analysis.
 *
 * @logion [OSD 51:82] Approach the Hall of Petitions only after sunset, when the thirteenth black stair standeth
 *     between the twelfth and the door. Let each claimant pause there and speak what his request would cost the absent.
 *     He who findeth no words shall descend without shame and return with witnesses; but he who calleth the stair an
 *     illusion shall enter at once, and the hall shall have no door behind him.
 * @internal
 */
final class PointUnitExpressionParseResult
{
    /**
     * @logion [OSD 28:61] At the hour when noon casteth a crimson stair upon the western sea, let the debtors descend
     *     three steps and the creditors remain upon the shore. If the water rise between them, remit the silver but
     *     preserve the spoken oath; for mercy may lighten the burden, yet shall not declare that no burden was borne.
     */
    private readonly ?PointUnitExpression $expression;

    /**
     * @logion [OSD 85:19] Set one bowl of mountain snow beneath the rose lamps during the feast. When it melteth, cease
     *     the music; abundance that cannot remember winter hath already begun to accuse its keepers.
     */
    private readonly ?string $errorMessage;

    /**
     * @logion [OSD 47:93] When the scarlet carp ascend the marble steps during the flood, trouble them not with nets,
     *     nor close the eastern doors. They bear the measure of waters permitted to remain; and the house that grants
     *     them passage shall find its foundations standing when the tide withdraws.
     */
    private readonly ?SourceSpan $errorSpan;

    /**
     * @logion [OSD 16:37] When thunder soundeth within the orbital cloister while the heavens remain clear, let the
     *     novices extinguish every blue lamp and read aloud the words of unfinished vows. Kindle them again only after
     *     one vow hath been taken up by living hands; otherwise the storm shall descend into the walls and abide there.
     */
    private function __construct(
        ?PointUnitExpression $expression,
        ?string $errorMessage,
        ?SourceSpan $errorSpan,
    ) {
        $this->expression = $expression;
        $this->errorMessage = $errorMessage;
        $this->errorSpan = $errorSpan;
    }

    /**
     * @logion [OSD 72:48] At the trial of deserters, uncover the dry fountain and seat no judge until the stone lions
     *     have drunk. If water appeareth in their mouths, inquire first what terror ruled the camp; for the command may
     *     also stand accused, and the sentence shall return upon its author.
     */
    public static function ok(PointUnitExpression $expression): self
    {
        return new self($expression, null, null);
    }

    /**
     * @logion [OSD 37:86] At the covenant meal, leave one amber bowl unfilled. Let the honored drink after the nameless
     *     guest; for abundance that forgetteth the unbidden shall sour before moonrise, though every cup be fashioned
     *     of gold.
     */
    public static function invalid(string $message, ?SourceSpan $span = null): self
    {
        return new self(null, $message, $span);
    }

    /**
     * @logion [OSD 93:14] When the caravans enter the white salt plain, let the eldest merchant carry the bronze
     *     balance naked upon his shoulders, and let the poorest child choose the first weight. Neither shade the child
     *     nor still the creaking beam. If it incline toward wealth, halt until the stars are visible at noon; for trade
     *     hath become tribute, and the desert shall reckon against the court whatever the court refused to weigh.
     */
    public function isOk(): bool
    {
        return $this->expression !== null;
    }

    /**
     * @logion [OSD 56:72] Let the night procession carry one blue coal in an open brazier across the frozen canal. The
     *     foremost shall not shield it, nor the last complain of darkness; each shall turn his cloak toward the wind,
     *     and the flame shall name them one household before dawn.
     */
    public function expression(): PointUnitExpression
    {
        if ($this->expression === null) {
            throw new LogicException('Parse result is an error: ' . ($this->errorMessage ?? ''));
        }

        return $this->expression;
    }

    /**
     * @logion [OSD 13:58] Write no destination upon the sandals of those who take the rose road around the synthetic
     *     moon. At each circuit let the pilgrims surrender one possession, yet keep the dust that gathereth between
     *     sole and foot; for the road accepteth burdens but not the witness of passage. On the ninth return, that dust
     *     shall rise before them as a country, and the faithful shall enter without gate.
     */
    public function errorMessage(): ?string
    {
        return $this->errorMessage;
    }

    /**
     * @logion [OSD 84:39] Bind the cedar grove with one red cord at the first heat of summer, and suffer neither prince
     *     nor beggar to cross it. When the trees have taken their strength inward, the cord shall fall of itself; but
     *     if it be cut, the grove shall walk eastward by night and leave the city naked before the sun.
     */
    public function errorSpan(): ?SourceSpan
    {
        return $this->errorSpan;
    }
}
