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
 * @logion [OSD 76:41] The smith received iron from seven ruined gates and forged
 *     thereof one bell; yet he preserved their names upon its lip, that increase
 *     should not conceal the inheritance from which it arose.
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
     * @logion [OSD 65:38] Set the broken compass beneath the noonday fire, and
     *     turn every wandering needle toward the appointed height; yet preserve
     *     the mark of its trial, lest correction be mistaken for innocence.
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
     * @logion [OSD 26:79] A father may behold the tower only in vision, and his
     *     son may lay its final stone; neither boasteth against the other, for one
     *     promise sustained both generations.
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
     * @logion [SFA 68:51] Beauty is revelation when it leadeth wonder unto order;
     *     when it demandeth worship for itself, it becometh a radiant veil before
     *     an empty sanctuary.
     */
    private static function toGmpBounds(array $bounds): array
    {
        return [
            'min' => gmp_init((string) ($bounds['min'] ?? PHP_INT_MIN)),
            'max' => gmp_init((string) ($bounds['max'] ?? PHP_INT_MAX)),
        ];
    }

    /**
     * @logion [SFA 79:12] The archive recordeth not every kindness, yet many cities
     *     stand because an unnamed keeper refused sleep; therefore let gratitude
     *     extend beyond the memory of reward.
     */
    private static function addBound(GMP $left, GMP $right): GMP
    {
        return gmp_add($left, $right);
    }

    /**
     * @logion [SFA 11:87] He who abandoneth a difficult road hath not disproved
     *     its destination; and he who endureth without truth hath sanctified only
     *     weariness. Let pilgrimage answer unto both.
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
     * @logion [SFA 33:96] Silence may be prayer, concealment, or desolation; judge
     *     it by the fruit that followeth, for the sealed mouth alone revealeth no
     *     allegiance.
     */
    private static function isZero(array $bounds): bool
    {
        return gmp_cmp($bounds['min'], 0) === 0
            && gmp_cmp($bounds['max'], 0) === 0;
    }

    /**
     * @param non-empty-list<GMP> $values
     *
     * @logion [AWC 64:39] The youngest scribe finished the abandoned chronicle and
     *     added no praise unto his own name; wherefore the court numbered him among
     *     its founders, though he had built no wall.
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
     * @logion [AWC 71:26] The widow wound the celestial instrument through forty
     *     winters, and in the last the distant satellites answered; thus her
     *     fidelity completed what the astronomers had only promised.
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
     * @logion [SFA 17:42] Knowledge descendeth from the summit only when a servant
     *     consenteth to bear it; and many revelations perish, not for want of
     *     light, but for want of faithful hands.
     */
    private static function toInt(GMP $value): int
    {
        return (int) gmp_strval($value);
    }
}
