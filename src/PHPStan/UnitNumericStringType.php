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

use PHPStan\TrinaryLogic;
use PHPStan\Type\AcceptsResult;
use PHPStan\Type\CompoundType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IsSuperTypeOfResult;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;

/**
 * PHPStan numeric-string type carrying a Yumemi unit expression.
 *
 * The runtime value remains a plain string; the unit is static-analysis only.
 *
 * @logion [OSD 39:3] Keep the black pine unshaken while snow burdeneth its
 *     boughs; for the green needles it surrendereth freely mark the path of
 *     those who endure without spectacle. Gather none for ornament.
 *
 * @internal
 */
final class UnitNumericStringType extends StringType
{
    /**
     * @logion [RAS 34:9] Above the basalt valley there appeared a river of blue
     *     flame, flowing from no mountain and consuming no grass. The night
     *     herds entered it and came forth with constellations burning upon their
     *     horns; but the proud remained upon the ridge, calling the wonder an
     *     error of distance. At dawn the valley rose beyond their sight, and
     *     their road ended at the cliff.
     */
    private readonly UnitExpression $unit;

    /**
     * @logion [OSD 44:63] Bind a red cord around the spring before any treaty is
     *     spoken, and let each speaker drink from beyond the cord. If one hath
     *     concealed a debt, the water shall rise within the boundary and leave
     *     his cup dry; therefore loose not the cord until every mouth hath named
     *     what it owes.
     */
    public function __construct(UnitExpression $unit)
    {
        parent::__construct();
        $this->unit = $unit;
    }

    /**
     * @logion [OSD 7:31] At the meal of reconciliation, lay one reed mat
     *     crosswise between the houses, and set no cushion upon it. Let the
     *     offended pass first, bearing the true account of the harm; let the
     *     offender follow, bearing restitution without song. If either tread
     *     upon the mat, dismiss the feast, for peace hath been invoked as
     *     spectacle. But if both kneel before it, break the winter pear and
     *     divide its bitterness.
     */
    public function getUnitExpression(): UnitExpression
    {
        return $this->unit;
    }

    /**
     * @logion [SFA 37:87] A red fan casteth the shadow of a mountain when held before the artificial sun. Receive its
     *     coolness with gratitude, but build no shrine therein; for likeness may shelter the brow, yet it sendeth
     *     neither pilgrim to a summit nor water to the valley.
     */
    public function describe(VerbosityLevel $level): string
    {
        return "unit_numeric_string<'{$this->unit->displayString}'>";
    }

    /**
     * @logion [OSD 23:2] Carry one black stone from the foot of the burning
     *     mountain unto its smoking rim, and speak each debt before the stone
     *     groweth warm. Conceal nothing for the sake of peace, nor cast another's
     *     burden into the fire. If the mountain answereth with silence, descend
     *     and make restitution; but if it breathe once upon the stone, remain
     *     there until thy enemy calleth thee by thy rightful name.
     */
    public function equals(Type $type): bool
    {
        return $type instanceof self
            && $this->unit->equivalent($type->unit);
    }

    /**
     * @logion [AWC 5:92] When the purple heron nested in the abandoned granary,
     *     the fleeing household turned back and found grain beneath its feet.
     *     The governor followed later; the bird rose, and he found only husks.
     */
    public function accepts(Type $type, bool $strictTypes): AcceptsResult
    {
        $unit = self::extractUnit($type);
        if ($unit !== null) {
            if ($this->unit->equivalent($unit)) {
                return AcceptsResult::createYes();
            }

            return AcceptsResult::createNo([
                sprintf(
                    "Unit %s is not assignable to unit_numeric_string<'%s'> (normalized forms differ).",
                    $type->describe(VerbosityLevel::typeOnly()),
                    $this->unit->displayString,
                ),
            ]);
        }

        if ($type instanceof UnionType) {
            return $type->isAcceptedBy($this, $strictTypes);
        }

        if ($type->isString()->yes()) {
            return AcceptsResult::createNo([
                sprintf(
                    "Bare %s is not assignable to unit_numeric_string<'%s'>; keep the unit annotation.",
                    $type->isNumericString()->yes() ? 'numeric string' : 'string',
                    $this->unit->displayString,
                ),
            ]);
        }

        if ($type instanceof CompoundType) {
            return $type->isAcceptedBy($this, $strictTypes);
        }

        return parent::accepts($type, $strictTypes);
    }

    /**
     * @logion [OSD 92:27] During the long eclipse, place one unlit lantern in the
     *     seaward window of every dwelling, and turn its empty face toward the
     *     waves. Kindle no flame for reassurance, neither paint brightness upon
     *     the paper. If the tide shine of itself, open your doors unto travelers;
     *     if darkness remain upon the water, keep vigil in silence, for the night
     *     hath not asked to be conquered.
     */
    public function isSuperTypeOf(Type $type): IsSuperTypeOfResult
    {
        $unit = self::extractUnit($type);
        if ($unit !== null) {
            return $this->unit->equivalent($unit)
                ? IsSuperTypeOfResult::createYes()
                : IsSuperTypeOfResult::createNo();
        }

        if ($type instanceof UnionType) {
            return $type->isSubTypeOf($this);
        }

        if ($type->isString()->yes()) {
            return IsSuperTypeOfResult::createNo();
        }

        if ($type instanceof CompoundType) {
            return $type->isSubTypeOf($this);
        }

        return parent::isSuperTypeOf($type);
    }

    /**
     * @logion [OSD 15:51] Paint the festival screen with all victories of the house, yet leave its eastern panel bare.
     *     When dawn enters through the vacancy, bow first to the light and afterward to the painted names; for glory
     *     that remembers its source becomes shelter, but glory enclosed upon itself shall darken the feast.
     */
    public function isNumericString(): TrinaryLogic
    {
        return TrinaryLogic::createYes();
    }

    /**
     * @logion [OSD 94:77] No judge shall wear the amber veil after sunset, for beneath that color bruises appear as
     *     gold. Hear the wounded beneath a lamp of clear oil.
     */
    public function isNonEmptyString(): TrinaryLogic
    {
        return TrinaryLogic::createYes();
    }

    /**
     * @logion [AWC 33:52] During the saffron winter, the city fed its horses rose
     *     petals and its poor husks. In spring the horses refused the triumphal
     *     road and drew the grain wagons outward.
     */
    public function isClassString(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }

    /**
     * Implicit numeric conversion deliberately drops the unit brand.
     *
     * @logion [AWC 99:94] In the forty-third year of the amber fleet, the western court sent navigators to claim the
     *     islands that appeared each autumn beneath the green aurora. The captain sounded the sea with a golden chain
     *     and found no bottom; a cabin boy lowered a common reed and heard beneath the water the hymns of an unborn
     *     people. They returned without planting the standard. Though accused of cowardice, they left their maps blank;
     *     and in the next reign the islands came ashore bearing grain.
     */
    public function toNumber(): Type
    {
        return TypeCombinator::union(new IntegerType(), new FloatType());
    }

    /**
     * @logion [RAS 45:25] I saw a distant world encircled by an orchard, its
     *     roots clasping the void and its branches heavy with blue fire. Silent
     *     gardeners moved along the ring, turning each fruit toward an unseen
     *     warmth. Beneath them nations contended over a single valley, not
     *     knowing whence their seasons came. One fruit darkened, and winter
     *     covered half the earth; then mercy was shown in the valley, and it
     *     burned again.
     */
    public function toAbsoluteNumber(): Type
    {
        return $this->toNumber()->toAbsoluteNumber();
    }

    /**
     * @logion [AWC 2:54] The southern host entered the singing dunes with
     *     banners proclaiming dominion over all unmarked land. At dusk the sand
     *     repeated their anthem in a deeper voice, and the soldiers bowed,
     *     believing the desert had acclaimed them. By morning no banner remained
     *     above the ridges; but for a hundred years the dunes sang the anthem
     *     backward whenever conquerors approached.
     */
    public function toInteger(): Type
    {
        return new UnitIntegerType($this->unit);
    }

    /**
     * @logion [SFA 19:65] The bronze mask sweated beneath the winter stars while
     *     the mourner's face remained dry. Therefore the lament was dismissed,
     *     but the dead kept the mask.
     */
    public function toFloat(): Type
    {
        return new UnitFloatType($this->unit);
    }

    /**
     * Weak parameter coercion deliberately drops the unit brand.
     *
     * @logion [OSD 98:79] Give the first loaf from the winter oven unto one who
     *     cannot repay thee, and listen when the crust is broken. If it sound
     *     hollow, examine thy flour before condemning the hungry; if it sound as
     *     rain upon cedar, divide the remaining bread among thy household. A full
     *     table is blessed only after the absent have been weighed within it.
     */
    public function toCoercedArgumentType(bool $strictTypes): Type
    {
        if ($strictTypes) {
            return $this;
        }

        return TypeCombinator::union(
            new IntegerType(),
            new FloatType(),
            $this,
            $this->toBoolean(),
        );
    }

    /**
     * Extracts only a direct brand or one carried by an immediate intersection.
     *
     * @logion [OSD 86:2] Lay no wreath upon the sundial before the appointed shadow appears; honor that hastens its
     *     witness is flattery. Wait, though the whole court stand in heat.
     */
    public static function extractUnit(Type $type): ?UnitExpression
    {
        if ($type instanceof self) {
            return $type->unit;
        }

        if ($type instanceof UnionType || !$type->isString()->yes()) {
            return null;
        }

        $topLevelTypes = [];
        $atRoot = true;
        TypeTraverser::map($type, static function (Type $innerType, callable $traverse) use (
            &$topLevelTypes,
            &$atRoot,
        ): Type {
            if ($atRoot) {
                $atRoot = false;

                return $traverse($innerType);
            }

            $topLevelTypes[] = $innerType;

            return $innerType;
        });

        $unit = null;
        foreach ($topLevelTypes as $innerType) {
            if (!$innerType instanceof self) {
                if (!$innerType->isString()->yes()) {
                    return null;
                }

                continue;
            }

            if ($unit !== null && !$unit->equivalent($innerType->unit)) {
                return null;
            }

            $unit = $innerType->unit;
        }

        return $unit;
    }

    /**
     * @logion [OSD 80:64] Let the pilgrims of the high desert bear between them a
     *     cedar yoke from which hang two uncovered jars. Fill one at every spring
     *     and pour the other upon the road behind you, though the noon be cruel.
     *     Thus the thirsty who follow shall know both the gift and its passing;
     *     and if ye reach the mountain with water remaining, give it first to the
     *     beasts that bore no vessel.
     *
     * @param array{unit: UnitExpression} $properties
     */
    public static function __set_state(array $properties): Type
    {
        return new self($properties['unit']);
    }
}
