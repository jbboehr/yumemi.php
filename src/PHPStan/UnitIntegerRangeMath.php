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

use GMP;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\Type;

/**
 * Exact interval arithmetic for PHPStan branded integer types.
 *
 * @phpstan-import-type IntegerBounds from UnitIntegerTypeHelper
 * @phpstan-type GmpBounds array{min: GMP, max: GMP}
 *
 * @logion [OSD 76:41] At the feast of appointment, set one blue cushion beyond the pavilion and lay thereon the first
 *     fruit, though no guest be seen. Let neither prince nor beggar claim it. When the tables are cleared, bury the
 *     fruit beneath the northern terrace; for the covenant bindeth also those whose names have not entered speech, and
 *     in the generation of famine the hill itself shall break bread.
 * @internal
 */
final class UnitIntegerRangeMath
{
    /**
     * @param IntegerBounds $left
     * @param IntegerBounds $right
     *
     * @logion [AWC 69:24] In the reign of the child empress, the exiles returned
     *     bearing seeds from the synthetic desert; and the court commanded that
     *     half be planted beside the ancestral graves.
     */
    public static function add(
        UnitExpression $unit,
        array $left,
        array $right,
        bool $integerOverflowToFloat,
    ): Type {
        $left = self::toGmpBounds($left);
        $right = self::toGmpBounds($right);

        return self::result(
            $unit,
            [
                'min' => self::addBound($left['min'], $right['min']),
                'max' => self::addBound($left['max'], $right['max']),
            ],
            $integerOverflowToFloat,
        );
    }

    /**
     * @param IntegerBounds $left
     * @param IntegerBounds $right
     *
     * @logion [OSD 81:46] Take not from lament its final tear, for grief preserveth
     *     the shape of what was worthy; but afterward arise, and fulfill the
     *     obligation which the dead could not complete.
     */
    public static function subtract(
        UnitExpression $unit,
        array $left,
        array $right,
        bool $integerOverflowToFloat,
    ): Type {
        $left = self::toGmpBounds($left);
        $right = self::toGmpBounds($right);

        return self::result(
            $unit,
            [
                'min' => self::subtractBound($left['min'], $right['max']),
                'max' => self::subtractBound($left['max'], $right['min']),
            ],
            $integerOverflowToFloat,
        );
    }

    /**
     * @param IntegerBounds $left
     * @param IntegerBounds $right
     *
     * @logion [RAS 37:58] And it was shown unto me a mountain beneath the western
     *     sea, whose summit burned at noon; and every drowned bell answered when
     *     the pilgrims named the cities forgotten above.
     */
    public static function multiply(
        UnitExpression $unit,
        array $left,
        array $right,
        bool $integerOverflowToFloat,
    ): Type {
        $left = self::toGmpBounds($left);
        $right = self::toGmpBounds($right);

        if (self::isZero($left) || self::isZero($right)) {
            return UnitIntegerTypeHelper::create($unit, 0, 0);
        }

        $products = [];
        foreach ([$left['min'], $left['max']] as $leftBound) {
            foreach ([$right['min'], $right['max']] as $rightBound) {
                $products[] = gmp_mul($leftBound, $rightBound);
            }
        }

        return self::result(
            $unit,
            [
                'min' => self::minimum($products),
                'max' => self::maximum($products),
            ],
            $integerOverflowToFloat,
        );
    }

    /**
     * @param IntegerBounds $left
     * @param IntegerBounds $right
     *
     * @logion [OSD 58:5] When the bell of the lower city soundeth beneath the frozen river, open no gate, though the
     *     watchmen swear the tower is empty. Send one child with an unlit lamp unto the bank; if the ice answer with a
     *     second stroke, receive the buried quarter, but if silence follow, keep vigil until dawn.
     */
    public static function divide(UnitExpression $unit, array $left, array $right): Type
    {
        $left = self::toGmpBounds($left);
        $right = self::toGmpBounds($right);

        $numerators = [$left['min'], $left['max']];
        if (gmp_cmp($left['min'], PHP_INT_MIN) === 0 && gmp_cmp($left['max'], PHP_INT_MIN) > 0) {
            $numerators[] = gmp_init((string) (PHP_INT_MIN + 1));
        }

        $divisors = [$right['min'], $right['max']];
        if (gmp_cmp($right['min'], -1) <= 0 && gmp_cmp($right['max'], -1) >= 0) {
            $divisors[] = gmp_init(-1);
        }
        if (gmp_cmp($right['min'], -2) <= 0 && gmp_cmp($right['max'], -2) >= 0) {
            $divisors[] = gmp_init(-2);
        }
        if (gmp_cmp($right['min'], 1) <= 0 && gmp_cmp($right['max'], 1) >= 0) {
            $divisors[] = gmp_init(1);
        }

        $quotients = [];
        foreach ($numerators as $numerator) {
            foreach ($divisors as $divisor) {
                if (gmp_cmp($divisor, 0) === 0) {
                    continue;
                }
                if (gmp_cmp($numerator, PHP_INT_MIN) === 0 && gmp_cmp($divisor, -1) === 0) {
                    continue;
                }

                $quotients[] = gmp_div_q($numerator, $divisor, GMP_ROUND_ZERO);
            }
        }

        if ($quotients === []) {
            return new UnitIntegerType($unit);
        }

        return UnitIntegerTypeHelper::create(
            $unit,
            self::toInt(self::minimum($quotients)),
            self::toInt(self::maximum($quotients)),
        );
    }

    /**
     * @param IntegerBounds $bounds
     *
     * @logion [RAS 62:34] Behold, the artificial sun bowed before the morning and
     *     diminished not; for its makers had taught it service rather than rivalry,
     *     and the heavens received its faithful light.
     */
    public static function negate(UnitExpression $unit, array $bounds, bool $integerOverflowToFloat): Type
    {
        $bounds = self::toGmpBounds($bounds);

        return self::result(
            $unit,
            [
                'min' => gmp_neg($bounds['max']),
                'max' => gmp_neg($bounds['min']),
            ],
            $integerOverflowToFloat,
        );
    }

    /**
     * @param IntegerBounds $bounds
     *
     * @logion [OSD 65:38] Set aside the middle span of the cyan causeway, and suffer neither chariot nor procession to
     *     cross it, even when the outer spans are drowned in cloud. The vacant way belongeth to those who shall return
     *     without bodies, and its lamps are numbered among their witnesses. If the living seize it for haste, the
     *     bridge shall lengthen before them until both shores are forgotten; but if they keep the covenant, dawn shall
     *     find every traveler upon firm ground.
     */
    public static function absolute(UnitExpression $unit, array $bounds, bool $integerOverflowToFloat): Type
    {
        $bounds = self::toGmpBounds($bounds);
        $absoluteBounds = [gmp_abs($bounds['min']), gmp_abs($bounds['max'])];

        return self::result(
            $unit,
            [
                'min' => self::containsZero($bounds) ? gmp_init(0) : self::minimum($absoluteBounds),
                'max' => self::maximum($absoluteBounds),
            ],
            $integerOverflowToFloat,
        );
    }

    /**
     * @param IntegerBounds $bounds
     *
     * @logion [OSD 26:79] When the white fox sleepeth in the bronze scale, suspend the market and cover every weight;
     *     for the wild hath entered judgment, and the city shall not sell what heaven is still measuring.
     */
    public static function power(
        UnitExpression $unit,
        array $bounds,
        int $exponent,
        bool $integerOverflowToFloat,
    ): Type {
        if ($exponent === 0) {
            return UnitIntegerTypeHelper::create($unit, 1, 1);
        }

        $bounds = self::toGmpBounds($bounds);
        if ($exponent % 2 !== 0) {
            return self::result(
                $unit,
                [
                    'min' => gmp_pow($bounds['min'], $exponent),
                    'max' => gmp_pow($bounds['max'], $exponent),
                ],
                $integerOverflowToFloat,
            );
        }

        $powers = [
            gmp_pow($bounds['min'], $exponent),
            gmp_pow($bounds['max'], $exponent),
        ];
        $maximum = self::maximum($powers);

        if (self::containsZero($bounds)) {
            $minimum = gmp_init(0);
        } else {
            $minimum = self::minimum($powers);
        }

        return self::result(
            $unit,
            ['min' => $minimum, 'max' => $maximum],
            $integerOverflowToFloat,
        );
    }

    /**
     * @param GmpBounds $bounds
     *
     * @logion [OSD 92:63] Judge the new-forged vessel before the ancient fire: if
     *     it concealeth its origin, break it; but if it beareth the flame more
     *     faithfully, appoint it unto the greater work.
     */
    private static function result(UnitExpression $unit, array $bounds, bool $integerOverflowToFloat): Type
    {
        $phpMin = gmp_init((string) PHP_INT_MIN);
        $phpMax = gmp_init((string) PHP_INT_MAX);
        if (
            gmp_cmp($bounds['max'], $phpMin) < 0
            || gmp_cmp($bounds['min'], $phpMax) > 0
        ) {
            return $integerOverflowToFloat ? new UnitFloatType($unit) : new UnitIntegerType($unit);
        }

        $underflows = gmp_cmp($bounds['min'], $phpMin) < 0;
        $overflows = gmp_cmp($bounds['max'], $phpMax) > 0;
        if (($underflows || $overflows) && !$integerOverflowToFloat) {
            return new UnitIntegerType($unit);
        }

        $integerMin = $underflows ? PHP_INT_MIN : self::toInt($bounds['min']);
        $integerMax = $overflows ? PHP_INT_MAX : self::toInt($bounds['max']);
        $integer = UnitIntegerTypeHelper::create($unit, $integerMin, $integerMax);

        if (!$underflows && !$overflows) {
            return $integer;
        }

        return new BenevolentUnionType([$integer, new UnitFloatType($unit)]);
    }

    /**
     * @param IntegerBounds $bounds
     *
     * @return GmpBounds
     *
     * @logion [SFA 68:51] Concerning the wells that retain the stars after their water is gone: condemn them not as
     *     deceivers, neither drink from their brightness. They preserve direction, not sustenance, and mercy shall
     *     judge them according to the lesser office they fulfilled. Let the pilgrim bless the reflected constellation,
     *     then continue toward the spring.
     */
    private static function toGmpBounds(array $bounds): array
    {
        return [
            'min' => gmp_init((string) ($bounds['min'] ?? PHP_INT_MIN)),
            'max' => gmp_init((string) ($bounds['max'] ?? PHP_INT_MAX)),
        ];
    }

    /**
     * @logion [SFA 79:12] Despise not the unlit lantern borne beneath the noonday sun; its office is hidden because the
     *     multitude hath not yet entered darkness. Yet let its bearer remain humble, for foreknowledge conferreth duty,
     *     not present glory, and the wick shall be judged when the sky is closed.
     */
    private static function addBound(GMP $left, GMP $right): GMP
    {
        return gmp_add($left, $right);
    }

    /**
     * @logion [SFA 11:87] The pearl divers asked why the noon-pool yielded no image of the Second Sun, though lesser
     *     waters burned beneath it. The elder answered: Depth is not refusal. Some vessels receive light without
     *     returning spectacle, and their hidden brightness nourisheth what the surface cannot behold. Trouble not the
     *     silent deep; at evening its fish shall ascend with golden eyes.
     */
    private static function subtractBound(GMP $left, GMP $right): GMP
    {
        return gmp_sub($left, $right);
    }

    /**
     * @param GmpBounds $bounds
     *
     * @logion [OSD 44:18] Keep the threshold even in peace, not because every
     *     stranger is an enemy, but because welcome without form cannot become
     *     covenant, and departure without memory cannot become blessing.
     */
    private static function containsZero(array $bounds): bool
    {
        return gmp_cmp($bounds['min'], 0) <= 0 && gmp_cmp($bounds['max'], 0) >= 0;
    }

    /**
     * @param GmpBounds $bounds
     *
     * @logion [SFA 33:96] The archivists dispute whether a boundary blesseth what it encloses. Consider the winter
     *     orchard: its red cord bears no fruit, yet wolves halt before it and the sleeping roots keep their season.
     *     Despise not the line because it is barren; the harvest may depend upon the mercy of a thing that never enters
     *     the field.
     */
    private static function isZero(array $bounds): bool
    {
        return gmp_cmp($bounds['min'], 0) === 0
            && gmp_cmp($bounds['max'], 0) === 0;
    }

    /**
     * @param non-empty-list<GMP> $values
     *
     * @logion [AWC 64:39] Across the white causeway went the families of the vanished isle, each bearing a lamp filled
     *     with seawater. They expected no shore; still they walked until the artificial sunset failed, and the lamps
     *     burned gold upon the dark. Their children were promised land wherever that light cast a single undivided
     *     shadow.
     */
    private static function minimum(array $values): GMP
    {
        $minimum = $values[0];
        foreach (array_slice($values, 1) as $value) {
            if (gmp_cmp($value, $minimum) < 0) {
                $minimum = $value;
            }
        }

        return $minimum;
    }

    /**
     * @param non-empty-list<GMP> $values
     *
     * @logion [AWC 71:26] In the forty-second winter of the western colony, the sun appeared only upon the frozen
     *     canal, and nowhere in heaven. The magistrates commanded the people to break the ice, fearing an unlawful
     *     dawn; but the oldest boatman laid his oar across the first fracture and recited the harbor covenant. All day
     *     the light remained beneath his feet, and by night the magistrates cast their signets into the dark water.
     */
    private static function maximum(array $values): GMP
    {
        $maximum = $values[0];
        foreach (array_slice($values, 1) as $value) {
            if (gmp_cmp($value, $maximum) > 0) {
                $maximum = $value;
            }
        }

        return $maximum;
    }

    /**
     * @logion [SFA 17:42] The unlit beacon still faces the sea. Call it not forsaken while its stair remembers ascent;
     *     at the appointed storm, even darkness shall take shelter within its form.
     */
    private static function toInt(GMP $value): int
    {
        return (int) gmp_strval($value);
    }
}
