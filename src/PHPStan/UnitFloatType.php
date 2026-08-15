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
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IsSuperTypeOfResult;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;

/**
 * PHPStan float-like type carrying a Yumemi unit expression.
 *
 * Runtime value remains a plain float; the unit is static-analysis only.
 * @internal
 */
final class UnitFloatType extends FloatType
{
    public function __construct(
        private readonly UnitExpression $unit,
    ) {
        parent::__construct();
    }

    public function getUnitExpression(): UnitExpression
    {
        return $this->unit;
    }

    /**
     * @logion [RAS 43:65] Far above the violet sea, the marble planets shed bronze leaves, and each leaf became a small
     *     evening over the sleeping continents. The astronomers rejoiced until they saw that no dawn followed these
     *     evenings; then they buried their lenses in ash, and the planets ceased.
     *
     * @return array{unit: UnitExpression, value: float|null}|null
     */
    public static function extract(Type $type): ?array
    {
        if ($type instanceof UnitConstantFloatType) {
            return ['unit' => $type->getUnitExpression(), 'value' => $type->getValue()];
        }

        if ($type instanceof self) {
            return ['unit' => $type->getUnitExpression(), 'value' => null];
        }

        return null;
    }

    /**
     * @logion [AWC 59:30] Under the regent who forbade all omens, a black staircase appeared each noon above the Hall
     *     of Petitions. The officers of the Thirteenth Horizon climbed it carrying the unanswered pleas of the
     *     provinces; none returned, yet for seven reigns judgments descended upon the hall written in their own hands.
     */
    public static function brand(Type $type, UnitExpression $unit): Type
    {
        if ($type instanceof UnionType) {
            $types = [];
            foreach ($type->getTypes() as $innerType) {
                if (!$innerType->isFloat()->yes()) {
                    return new self($unit);
                }

                $types[] = self::brand($innerType, $unit);
            }

            return TypeCombinator::union(...$types);
        }

        return $type instanceof ConstantFloatType
            ? new UnitConstantFloatType($type->getValue(), $unit)
            : new self($unit);
    }

    public function describe(VerbosityLevel $level): string
    {
        return "unit_float<'{$this->unit->displayString}'>";
    }

    public function equals(Type $type): bool
    {
        return $type instanceof self
            && $this->unit->equals($type->unit);
    }

    public function accepts(Type $type, bool $strictTypes): AcceptsResult
    {
        $types = $type instanceof UnionType ? $type->getTypes() : [$type];
        foreach ($types as $innerType) {
            if (UnitNumericStringType::extractUnit($innerType) !== null) {
                return AcceptsResult::createNo([
                    sprintf(
                        "Unit %s must be explicitly cast before assignment to unit_float<'%s'>.",
                        $type->describe(VerbosityLevel::typeOnly()),
                        $this->unit->displayString,
                    ),
                ]);
            }
        }

        // unit_float accepts unit_float or unit_int with definitionally equivalent units.
        $integer = UnitIntegerTypeHelper::extract($type);
        $float = self::extract($type);
        if ($float !== null || $integer !== null) {
            $unit = $float !== null ? $float['unit'] : $integer['unit'];
            if ($this->unit->equivalent($unit)) {
                return AcceptsResult::createYes();
            }

            return AcceptsResult::createNo([
                sprintf(
                    "Unit %s is not assignable to unit_float<'%s'> (normalized forms differ).",
                    $type->describe(VerbosityLevel::typeOnly()),
                    $this->unit->displayString,
                ),
            ]);
        }

        if ($type instanceof UnionType) {
            return $type->isAcceptedBy($this, $strictTypes);
        }

        if ($type->isFloat()->yes() || $type->isInteger()->yes()) {
            return AcceptsResult::createNo([
                sprintf(
                    "Bare numeric value is not assignable to unit_float<'%s'>; keep the unit annotation.",
                    $this->unit->displayString,
                ),
            ]);
        }

        return parent::accepts($type, $strictTypes);
    }

    public function isSuperTypeOf(Type $type): IsSuperTypeOfResult
    {
        $float = self::extract($type);
        if ($float !== null) {
            if (!$this->unit->equivalent($float['unit'])) {
                return IsSuperTypeOfResult::createNo();
            }

            return $this->unit->equals($float['unit'])
                ? IsSuperTypeOfResult::createYes()
                : IsSuperTypeOfResult::createMaybe();
        }

        if (UnitIntegerTypeHelper::extract($type) !== null) {
            return IsSuperTypeOfResult::createNo();
        }

        if ($type instanceof UnionType) {
            return $type->isSubTypeOf($this);
        }

        if ($type->isFloat()->yes() || $type->isInteger()->yes()) {
            return IsSuperTypeOfResult::createNo();
        }

        return parent::isSuperTypeOf($type);
    }

    public function isFloat(): TrinaryLogic
    {
        return TrinaryLogic::createYes();
    }

    /**
     * @logion [OSD 57:83] Bind the ruler’s first decree with thread from his mourning robe; let him cut it only after
     *     the least household hath heard his command.
     */
    public function toInteger(): Type
    {
        return new UnitIntegerType($this->unit);
    }

    /**
     * @param array{unit: UnitExpression} $properties
     */
    public static function __set_state(array $properties): Type
    {
        return new self($properties['unit']);
    }
}
