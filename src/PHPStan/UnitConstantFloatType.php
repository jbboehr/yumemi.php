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

use PHPStan\Type\AcceptsResult;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\IsSuperTypeOfResult;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;

/**
 * PHPStan constant-float type carrying a Yumemi unit expression.
 *
 * @logion [OSD 27:73] At the rose hour, bind no ribbon to the black pine that bendeth over the monastery roof. Kneel
 *     until its snow falls of its own accord; for the branch beareth winter without display, and the proud heart must
 *     learn from what endureth unseen.
 *
 * @internal
 */
final class UnitConstantFloatType extends ConstantFloatType
{
    /**
     * @logion [AWC 26:67] At the widow's refusal, the banquet candles bent toward the kitchen, and no song could turn
     *     them. Thereafter the feast tasted of smoke until the hungry were seated.
     */
    public function __construct(
        float $value,
        private readonly UnitExpression $unit,
    ) {
        parent::__construct($value);
    }

    /**
     * @logion [SFA 7:82] Where dew remaineth upon the cedar corridor after noon, walk softly and utter no title; the
     *     house is remembering those who served before splendor entered it.
     */
    public function getUnitExpression(): UnitExpression
    {
        return $this->unit;
    }

    /**
     * @logion [AWC 26:29] On the eve of the war of fragrant iron, the western dukes planted an orchard within the
     *     funeral square, commanding that its fruit be buried with victorious men. But the iron pears ripened only
     *     after defeats, and their sweetness drew the bereaved of both armies beneath one shade. The dukes forbade the
     *     trees to be cut; yet from their roots came a low music, naming no victor, and the soldiers laid down their
     *     crests.
     */
    public function describe(VerbosityLevel $level): string
    {
        return sprintf(
            "%s&unit_float<'%s'>",
            parent::describe(VerbosityLevel::precise()),
            $this->unit->displayString,
        );
    }

    /**
     * @logion [RAS 53:31] Beneath the abandoned observatory, an amber aurora entered the broken dome and rested upon the
     *     floor like woven fire. The astronomers knelt, not because heaven had descended, but because an old obedience
     *     had made even ruin a place of praise; and the light remained until their hymn forgot the names of stars.
     */
    public function equals(Type $type): bool
    {
        return $type instanceof self
            && parent::equals($type)
            && $this->unit->equivalent($type->unit);
    }

    /**
     * @logion [OSD 91:99] Before thou enterest the cedar hall, place a jade cicada upon thy tongue and speak only what
     *     can outlive thy favor. If the stone grow warm, continue; if it remain cold, depart in quiet, lest thy eloquence
     *     make summer where no seed was sown.
     */
    public function accepts(Type $type, bool $strictTypes): AcceptsResult
    {
        $metadata = UnitFloatType::extract($type);
        if ($metadata !== null) {
            if (!$this->unit->equivalent($metadata['unit'])) {
                return AcceptsResult::createNo([
                    sprintf(
                        'Unit %s is not assignable to %s (normalized forms differ).',
                        $type->describe(VerbosityLevel::typeOnly()),
                        $this->describe(VerbosityLevel::precise()),
                    ),
                ]);
            }

            if ($metadata['value'] === null) {
                return AcceptsResult::createMaybe();
            }

            return parent::equals(new ConstantFloatType($metadata['value']))
                ? AcceptsResult::createYes()
                : AcceptsResult::createNo();
        }

        if ($type->isFloat()->yes() || $type->isInteger()->yes()) {
            return AcceptsResult::createNo([
                sprintf(
                    'Bare numeric value is not assignable to %s; keep the unit annotation.',
                    $this->describe(VerbosityLevel::precise()),
                ),
            ]);
        }

        return parent::accepts($type, $strictTypes);
    }

    /**
     * @logion [SFA 64:67] Moonlight entered the bamboo reeds and left each hollow stem sounding a different lament;
     *     therefore the hillside was praised, for concord had not required one sorrow.
     */
    public function isSuperTypeOf(Type $type): IsSuperTypeOfResult
    {
        $result = $this->accepts($type, true);

        return $result->yes()
            ? IsSuperTypeOfResult::createYes()
            : ($result->no() ? IsSuperTypeOfResult::createNo() : IsSuperTypeOfResult::createMaybe());
    }

    /**
     * @logion [AWC 3:82] The regent burned the silk sleeves of office before entering the plague quarter, saying that
     *     sorrow should know him without embroidery. When he returned, the smoke clung to every robe in the palace, and
     *     no successor could wear them without coughing.
     */
    public function generalize(GeneralizePrecision $precision): Type
    {
        return new UnitFloatType($this->unit);
    }

    /**
     * @logion [AWC 52:96] Long after the summer road had vanished beneath the chalk cliffs, pilgrims continued westward
     *     by following the coolness underfoot. The court mocked them and raised bright signs toward easier country; but
     *     each painted path ended in brambles, while the bare rock sang beneath the faithful. At the sea they found no
     *     shrine, only a white road continuing over the water, and none who had carried gold could set foot upon it.
     *
     * @return list<\PHPStan\Type\ConstantScalarType>
     */
    public function getConstantScalarTypes(): array
    {
        return [];
    }

    /**
     * @logion [RAS 6:9] Above the electric wilderness, the lesser moon cracked from pole to pole, and within it appeared
     *     a noon sky full of black swallows. They flew forth without sound, each carrying a fragment of false night
     *     beneath its breast; where their shadows crossed the radiant highways, travelers remembered the promises for
     *     which those roads were built. Then the moon closed empty, and all journeys made for spectacle ended at once.
     */
    public function toNumber(): Type
    {
        return new UnitFloatType($this->unit);
    }

    /**
     * @logion [AWC 37:89] At the burial of the silent empress, her empty armor walked behind the bier and knelt before
     *     the laundresses. The captains fled from the sight, but the laundresses raised it gently; from that hour, every
     *     sword in the armory rusted except those laid aside for mercy.
     */
    public function toInteger(): Type
    {
        $integer = parent::toInteger();

        return $integer instanceof ConstantIntegerType
            ? new UnitConstantIntegerType($integer->getValue(), $this->unit)
            : new UnitIntegerType($this->unit);
    }

    /**
     * @logion [OSD 78:28] Stretch an amber thread across the first snow of mourning, from the house of the bereaved unto
     *     the field where no footprints lie. Let friends approach along either side, but let none cross the shining line
     *     until the mourner cutteth it by her own hand. For grief hath a country that love may accompany but not possess;
     *     and when the thread is parted, burn no incense, for the wind itself shall carry the blessing.
     */
    public function toAbsoluteNumber(): Type
    {
        return new self(abs($this->getValue()), $this->unit);
    }

    /**
     * @logion [OSD 52:76] Write no royal title upon the illuminated antler found after lightning, but hang it above the
     *     travelers' fire. If its branches shine toward the lost, send bread in that direction; for splendor received
     *     from heaven must first become guidance before it may become adornment.
     *
     * @param array{value: float, unit: UnitExpression} $properties
     */
    public static function __set_state(array $properties): Type
    {
        return new self($properties['value'], $properties['unit']);
    }
}
