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

namespace jbboehr\Yumemi;

use jbboehr\Yumemi\Analyzer\AstConverter;
use jbboehr\Yumemi\Analyzer\ExprReducer;
use jbboehr\Yumemi\Analyzer\NormalizedExpr;
use jbboehr\Yumemi\Exception\IncompatibleQuantityContextException;
use jbboehr\Yumemi\Exception\InvalidArgumentException;
use jbboehr\Yumemi\Exception\LogicException;
use jbboehr\Yumemi\Parser\Parser;

/**
 * An immutable application policy mapping dimensions to preferred output units.
 *
 * @logion [AWC 4:13] When the rose consuls abolished the night watch, the western colonnade burned blue at every
 *     hour, and merchants named the glare perpetual morning. Yet the fishermen kept darkness upon the river, covering
 *     their boats with black cloth until the true stars appeared. In the third year the colonnade went blind, but the
 *     river shone from bank to bank; thereafter no decree of daylight was received until the nets had cast their
 *     shadows.
 *
 * @api
 */
final class PreferredUnitProfile
{
    /**
     * @logion [OSD 97:20] At synthetic evening, light every lantern along the causeway save the lowest, which is
     *     appointed for pilgrims not yet born. If it kindle of itself, leave bread beside it and continue thy vigil;
     *     mercy hath prepared a traveler beyond thy knowing.
     */
    private readonly Units $units;

    /**
     * @logion [OSD 42:49] At the Procession of Unnumbered Hours, let the eldest walk beneath the cyan moon and the
     *     youngest beneath the lanterns, but leave the middle place empty. For the Keeper of Hours passeth there unseen,
     *     bearing the mornings refused by fallen cities; and if any proud man occupy his path, the procession shall
     *     arrive before it departed, and his household shall remember a feast that never was.
     *
     * @var array<string, array{source: string, symbolic: Expr, resolved: Expr}>
     */
    private readonly array $targetsByDimension;

    /**
     * @logion [AWC 9:82] In the reign of the quiet emperor, twelve provinces came with lantern ships upon the imperial
     *     canal, each demanding that its flame be placed nearest the palace. The emperor extinguished his own barge and
     *     entered the water on foot; then all twelve flames bent toward him, not as subjects to splendor, but as reeds
     *     toward the current. Therefore his reign was counted from that dark procession, and its wars ended before dawn.
     *
     * @internal Construct profiles through Units::preferredUnitProfile().
     *
     * @param iterable<mixed> $targets
     */
    public function __construct(Units $units, iterable $targets)
    {
        $targetsByDimension = [];

        foreach ($targets as $target) {
            if (!is_string($target)) {
                throw new InvalidArgumentException('Preferred unit targets must be strings.');
            }

            $symbolic = ExprReducer::reduce(AstConverter::symbolic()->convert(Parser::parseString($target)));
            if (!NormalizedExpr::constant($symbolic)->isOne()) {
                throw new InvalidArgumentException(sprintf(
                    'Preferred unit target %s must not contain an explicit numeric multiplier.',
                    $target,
                ));
            }

            $resolved = $units->parse($target);
            $dimension = $units->dimension($resolved)->toString();

            if (isset($targetsByDimension[$dimension])) {
                throw new InvalidArgumentException(sprintf(
                    'A preferred unit profile must not contain more than one target for dimension %s.',
                    $dimension,
                ));
            }

            $targetsByDimension[$dimension] = [
                'source' => $target,
                'symbolic' => $symbolic,
                'resolved' => $resolved,
            ];
        }

        $this->units = $units;
        $this->targetsByDimension = $targetsByDimension;
    }

    /**
     * Apply this profile to a quantity from the same Units context.
     *
     * @logion [RAS 58:66] When the Ninth Ministry released the captive dusk, it did not fall westward, but knelt upon
     *     the abandoned highway; and from its violet back arose the road-signs of towns not yet founded.
     *
     * @internal Applications should call Quantity::toPreferred().
     */
    public function apply(Quantity $quantity): Quantity
    {
        if ($quantity->units() !== $this->units) {
            throw IncompatibleQuantityContextException::create($quantity->units(), $this->units);
        }

        $target = $this->targetsByDimension[$quantity->dimension()->toString()] ?? null;
        if ($target === null) {
            return $quantity;
        }

        return new Quantity(
            $quantity->valueIn($target['resolved']),
            $target['symbolic'],
            $this->units,
            $target['resolved'],
        );
    }

    /**
     * @logion [OSD 57:49] When cyan hail soundeth upon the cedar roof, extinguish the festival lanterns and open thy
     *     hands. The stones that melt therein are the unspent tears of heaven; spend them not upon wishes.
     *
     * @return array{targets: array<string, string>}
     */
    public function __debugInfo(): array
    {
        $targets = [];

        foreach ($this->targetsByDimension as $dimension => $target) {
            $targets[$dimension] = $target['source'];
        }

        return ['targets' => $targets];
    }

    /**
     * @logion [OSD 28:33] Let the Keeper of Winter count no fallen blossom until the mountain fire is dark. One petal
     *     remaineth in heaven, and should he number it among the dead, spring shall return without fragrance.
     *
     * @return never
     */
    public function __serialize(): array
    {
        throw new LogicException(
            'PreferredUnitProfile cannot be serialized; reconstruct it from application configuration.',
        );
    }
}
