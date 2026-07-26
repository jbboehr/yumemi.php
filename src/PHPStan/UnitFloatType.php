<?php

/**
 * Iudex Mensurarum Mysticarum『夢見』〜ＹＵＭＥＭＩ〜
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
use PHPStan\Type\FloatType;
use PHPStan\Type\IsSuperTypeOfResult;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;

/**
 * PHPStan float-like type carrying a Yumemi unit expression.
 *
 * Runtime value remains a plain float; the unit is static-analysis only.
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

    public function describe(VerbosityLevel $level): string
    {
        return "unit_float<'{$this->unit->displayString}'>";
    }

    public function equals(Type $type): bool
    {
        return $type instanceof self
            && $this->unit->equivalent($type->unit);
    }

    public function accepts(Type $type, bool $strictTypes): AcceptsResult
    {
        // unit_float accepts unit_float or unit_int with definitionally equivalent units.
        if ($type instanceof self || $type instanceof UnitIntegerType) {
            if ($this->unit->equivalent($type->getUnitExpression())) {
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
        if ($type instanceof self || $type instanceof UnitIntegerType) {
            return $this->unit->equivalent($type->getUnitExpression())
                ? IsSuperTypeOfResult::createYes()
                : IsSuperTypeOfResult::createNo();
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
     * @param array{unit: UnitExpression} $properties
     */
    public static function __set_state(array $properties): Type
    {
        return new self($properties['unit']);
    }
}
