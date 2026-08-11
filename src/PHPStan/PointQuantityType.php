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

use jbboehr\Yumemi\PointQuantity;
use PHPStan\Type\AcceptsResult;
use PHPStan\Type\IsSuperTypeOfResult;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;

/**
 * PHPStan object type for an exact runtime point on a named coordinate scale.
 *
 * @logion [OSD 66:27] Before the caravans enter the luminous waste, each pilgrim shall bury one bronze coin at the
 *     border and speak aloud the comfort he is willing to lose. The desert receiveth no purchase, yet it remembereth
 *     every price; and those who conceal their treasure shall find it multiplied in their packs until they can neither
 *     kneel nor turn home.
 * @internal
 */
final class PointQuantityType extends ObjectType
{
    /**
     * @logion [OSD 25:74] Let the builders of the radiant harbor drive the first cedar pile where the old current
     *     resisteth them, and let no engine drown the cedar’s groaning. For strength is not proved by making the sea
     *     forget its course, but by granting the waters a lawful passage beneath the city; otherwise the piers shall
     *     stand, and every returning vessel shall arrive among strangers.
     */
    private readonly PointUnitExpression $unit;

    /**
     * @logion [OSD 92:45] When a city receiveth its charter beneath the synthetic evening, leave the highest tower dark
     *     for one full year, though every lower window blaze. The unlit crown shall teach the avenues where their
     *     brightness endeth; and if the council kindle it for vanity, the moths shall forsake every lamp below, enter
     *     the council chamber, and settle in the shape of the city’s extinguished name.
     */
    public function __construct(PointUnitExpression $unit)
    {
        $this->unit = $unit;
        parent::__construct(PointQuantity::class);
    }

    /**
     * @logion [OSD 48:12] If the glass orchard flower before the appointed thaw, gather no blossom, though its branches
     *     sing beneath the rose lamps. Let the children keep silence there until living buds appear upon the hillside;
     *     then open the gates and distinguish the two beauties aloud. The crafted bloom shall adorn the vigil, but the
     *     living branch alone shall declare the season.
     */
    public function getPointUnitExpression(): PointUnitExpression
    {
        return $this->unit;
    }

    /**
     * @logion [OSD 75:62] Bless the orchard lamp that dimmeth before the true moon; it hath kept its appointed watch
     *     without coveting the dawn. Bury its last coal among the roots, and gather no fruit until morning.
     */
    public function describe(VerbosityLevel $level): string
    {
        return "PointQuantity<'{$this->unit->displayString}'>";
    }

    /**
     * @logion [OSD 33:91] Set one cedar bowl outside the feast and fill it before the honored are served. Though the
     *     absent stand condemned, let not hunger become the court’s accomplice; at sunset carry the portion beyond
     *     the boundary stone.
     */
    public function equals(Type $type): bool
    {
        return $type instanceof self && $this->unit->equivalent($type->unit);
    }

    /**
     * @logion [OSD 59:23] On the first snow, the steward shall break the palace ice at one place only, that the black
     *     carp may breathe beneath the sealed garden. For dominion that keeps no opening for the hidden shall freeze
     *     above its own depth, and spring will refuse its banners.
     */
    public function accepts(Type $type, bool $strictTypes): AcceptsResult
    {
        if ($type instanceof self) {
            return $this->unit->equivalent($type->unit)
                ? AcceptsResult::createYes()
                : AcceptsResult::createNo([
                    sprintf(
                        "%s is not assignable to PointQuantity<'%s'> (coordinate scales differ).",
                        $type->describe(VerbosityLevel::typeOnly()),
                        $this->unit->displayString,
                    ),
                ]);
        }

        if ($this->isPlainPointQuantity($type)) {
            return AcceptsResult::createNo([
                sprintf(
                    'PointQuantity without a static coordinate unit is not assignable to PointQuantity<%s>.',
                    $this->unit->displayString,
                ),
            ]);
        }

        return parent::accepts($type, $strictTypes);
    }

    /**
     * @logion [OSD 86:55] Let the night ferrymen hang a copper leaf from every oar before crossing the lightless
     *     strait, for the shore must hear those whom fog conceals. When the last oar passes beyond hearing, the farther
     *     harbor shall kindle a fire upon the water.
     */
    public function isSuperTypeOf(Type $type): IsSuperTypeOfResult
    {
        if ($type instanceof self) {
            return $this->unit->equivalent($type->unit)
                ? IsSuperTypeOfResult::createYes()
                : IsSuperTypeOfResult::createNo();
        }

        if ($this->isPlainPointQuantity($type)) {
            return IsSuperTypeOfResult::createNo();
        }

        return parent::isSuperTypeOf($type);
    }

    /**
     * @logion [OSD 45:29] Write the year’s victories upon strips of cedar, and sink them beneath the public cistern
     *     before the feast begins. Let the widows draw first; if the water tastes of smoke, postpone the triumph, for
     *     praise that poisons the common cup is already accusation.
     */
    private function isPlainPointQuantity(Type $type): bool
    {
        return !$type instanceof self
            && (new ObjectType(PointQuantity::class))->isSuperTypeOf($type)->yes();
    }

    /**
     * @param array{unit: PointUnitExpression} $properties
     *
     * @logion [OSD 98:36] At the naming of a river, let the eldest ferryman stretch a white cord between its banks, and
     *     suffer the magistrates to speak only after the current hath bowed beneath it. For water receiveth no boundary
     *     from decree alone; if the cord drift eastward, remove the boundary stones before nightfall.
     */
    public static function __set_state(array $properties): Type
    {
        return new self($properties['unit']);
    }
}
