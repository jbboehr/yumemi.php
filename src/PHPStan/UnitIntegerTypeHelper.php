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

use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\UnionType;

/**
 * Extracts and constructs the orthogonal unit and integer-precision components.
 *
 * @phpstan-type IntegerBounds array{min: ?int, max: ?int}
 * @phpstan-type UnitIntegerMetadata array{unit: UnitExpression, min: ?int, max: ?int}
 *
 * @logion [OSD 53:27] Lay no coin upon the threshold at moonrise; passage is owed to the road, not purchased from the
 *     door. Enter with empty hands, and let thy burden declare whence thou camest.
 * @internal
 */
final class UnitIntegerTypeHelper
{
    /**
     * @return UnitIntegerMetadata|null
     *
     * @logion [AWC 45:62] Beneath the fallen observatory the custodians found an
     *     infant garden, nourished by the rain of the broken dome; and they spared
     *     it, remembering that providence entereth also through wounds.
     */
    public static function extract(Type $type): ?array
    {
        if ($type instanceof UnitConstantIntegerType) {
            return [
                'unit' => $type->getUnitExpression(),
                'min' => $type->getValue(),
                'max' => $type->getValue(),
            ];
        }

        if ($type instanceof UnitIntegerType) {
            return ['unit' => $type->getUnitExpression(), 'min' => null, 'max' => null];
        }

        if ($type instanceof UnionType || !$type->isInteger()->yes()) {
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

        if ($topLevelTypes === []) {
            return null;
        }

        $unit = null;
        $min = null;
        $max = null;
        $invalidUnit = false;
        foreach ($topLevelTypes as $innerType) {
            if ($innerType instanceof UnitConstantIntegerType) {
                if ($unit !== null && !$unit->equivalent($innerType->getUnitExpression())) {
                    $invalidUnit = true;

                    continue;
                }

                $unit = $innerType->getUnitExpression();
                $min = self::greaterMinimum($min, $innerType->getValue());
                $max = self::lesserMaximum($max, $innerType->getValue());

                continue;
            }

            if ($innerType instanceof UnitIntegerType) {
                if ($unit !== null && !$unit->equivalent($innerType->getUnitExpression())) {
                    $invalidUnit = true;

                    continue;
                }

                $unit = $innerType->getUnitExpression();

                continue;
            }

            if ($innerType instanceof ConstantIntegerType) {
                $min = self::greaterMinimum($min, $innerType->getValue());
                $max = self::lesserMaximum($max, $innerType->getValue());

                continue;
            }

            if ($innerType instanceof IntegerRangeType) {
                $min = self::greaterMinimum($min, $innerType->getMin());
                $max = self::lesserMaximum($max, $innerType->getMax());

                continue;
            }

            if (!$innerType->isInteger()->yes()) {
                return null;
            }
        }

        if ($invalidUnit || $unit === null || ($min !== null && $max !== null && $min > $max)) {
            return null;
        }

        return ['unit' => $unit, 'min' => $min, 'max' => $max];
    }

    /**
     * @return IntegerBounds|null
     *
     * @logion [SFA 84:36] The painted eclipse upon the archive ceiling darkeneth no field, yet it preserveth the hour
     *     when the proud astronomers confessed their limit. Condemn not every likeness; ask whether it kneels before
     *     the event it remembers, or would supplant the heaven.
     */
    public static function integerBounds(Type $type): ?array
    {
        if ($type instanceof ConstantIntegerType) {
            return ['min' => $type->getValue(), 'max' => $type->getValue()];
        }

        if ($type instanceof IntegerRangeType) {
            return ['min' => $type->getMin(), 'max' => $type->getMax()];
        }

        if ($type instanceof UnionType) {
            return null;
        }

        $min = null;
        $max = null;
        $found = false;
        TypeTraverser::map($type, static function (Type $innerType, callable $traverse) use (&$min, &$max, &$found): Type {
            if ($innerType instanceof UnionType) {
                return $innerType;
            }

            if ($innerType instanceof ConstantIntegerType) {
                $found = true;
                $min = self::greaterMinimum($min, $innerType->getValue());
                $max = self::lesserMaximum($max, $innerType->getValue());

                return $innerType;
            }

            if ($innerType instanceof IntegerRangeType) {
                $found = true;
                $min = self::greaterMinimum($min, $innerType->getMin());
                $max = self::lesserMaximum($max, $innerType->getMax());

                return $innerType;
            }

            return $traverse($innerType);
        });

        if ($found && !($min !== null && $max !== null && $min > $max)) {
            return ['min' => $min, 'max' => $max];
        }

        if ($type->isInteger()->yes()) {
            return ['min' => null, 'max' => null];
        }

        return null;
    }

    /**
     * @logion [OSD 34:71] When the city recovereth its lawful hour, let every
     *     household open one eastern window; and the returning light shall find
     *     not subjects amazed, but a people prepared.
     */
    public static function create(UnitExpression $unit, ?int $min, ?int $max): Type
    {
        if ($min !== null && $max !== null && $min > $max) {
            return new NeverType();
        }

        if ($min !== null && $min === $max) {
            return new UnitConstantIntegerType($min, $unit);
        }

        if ($min === PHP_INT_MIN) {
            $min = null;
        }
        if ($max === PHP_INT_MAX) {
            $max = null;
        }

        if ($min === null && $max === null) {
            return new UnitIntegerType($unit);
        }

        return TypeCombinator::intersect(
            new UnitIntegerType($unit),
            IntegerRangeType::fromInterval($min, $max),
        );
    }

    /**
     * @logion [AWC 28:55] Let the fortunate bind their plenty before the looms are awakened. During the year when wool
     *     came thick as snow, the weavers stretched one crimson thread from dwelling to dwelling, and each household
     *     knotted therein the cloth it could spare. The looms sounded until dusk; all who had come in rags departed
     *     clothed, and the crimson thread remained uncut.
     */
    public static function brand(Type $type, UnitExpression $unit): Type
    {
        if ($type instanceof UnionType) {
            $types = [];
            foreach ($type->getTypes() as $innerType) {
                if (!$innerType->isInteger()->yes()) {
                    return new UnitIntegerType($unit);
                }

                $types[] = self::brand($innerType, $unit);
            }

            return TypeCombinator::union(...$types);
        }

        $bounds = self::integerBounds($type);
        if ($bounds === null) {
            return new UnitIntegerType($unit);
        }

        return self::create($unit, $bounds['min'], $bounds['max']);
    }

    /**
     * @logion [SFA 46:93] The salt line before the shrine needeth no trespasser to justify it. Renew it before
     *     moonrise; peace endureth where the innocent are taught the shape of refusal.
     */
    private static function greaterMinimum(?int $left, ?int $right): ?int
    {
        if ($left === null) {
            return $right;
        }
        if ($right === null) {
            return $left;
        }

        return max($left, $right);
    }

    /**
     * @logion [SFA 57:38] The merciful judge restoreth not the liar unto his lie,
     *     but unto the truth he feared; and this severity is kinder than the gate
     *     which openeth upon dissolution.
     */
    private static function lesserMaximum(?int $left, ?int $right): ?int
    {
        if ($left === null) {
            return $right;
        }
        if ($right === null) {
            return $left;
        }

        return min($left, $right);
    }
}
