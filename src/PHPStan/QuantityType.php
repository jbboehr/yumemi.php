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

use jbboehr\Yumemi\Quantity;
use PHPStan\Type\AcceptsResult;
use PHPStan\Type\IsSuperTypeOfResult;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;

/**
 * PHPStan object type for a runtime {@see Quantity} carrying an IMM unit expression.
 *
 * The runtime value is a real {@see Quantity}; the unit is tracked statically so it can propagate
 * through the fluent method chain and be checked at assignment/parameter boundaries. This is the
 * object-path analogue of {@see UnitIntegerType} / {@see UnitFloatType}: same normalized-equivalence
 * semantics ({@see UnitExpression::equivalent()} for compatibility, {@see UnitExpression::equals()}
 * for the structural fast path), matching runtime {@see Quantity::add()} / assertSameUnit().
 */
final class QuantityType extends ObjectType
{
    public function __construct(
        private readonly UnitExpression $unit,
    ) {
        parent::__construct(Quantity::class);
    }

    public function getUnitExpression(): UnitExpression
    {
        return $this->unit;
    }

    public function describe(VerbosityLevel $level): string
    {
        return "Quantity<'{$this->unit->displayString}'>";
    }

    public function equals(Type $type): bool
    {
        return $type instanceof self
            && $this->unit->equivalent($type->unit);
    }

    public function accepts(Type $type, bool $strictTypes): AcceptsResult
    {
        if ($type instanceof self) {
            if ($this->unit->equivalent($type->unit)) {
                return AcceptsResult::createYes();
            }

            return AcceptsResult::createNo([
                sprintf(
                    "Unit %s is not assignable to Quantity<'%s'> (normalized forms differ).",
                    $type->describe(VerbosityLevel::typeOnly()),
                    $this->unit->displayString,
                ),
            ]);
        }

        if ($this->isPlainQuantity($type)) {
            return AcceptsResult::createNo([
                sprintf(
                    'Quantity without a static unit is not assignable to Quantity<%s>; keep the unit annotation.',
                    $this->unit->displayString,
                ),
            ]);
        }

        return parent::accepts($type, $strictTypes);
    }

    public function isSuperTypeOf(Type $type): IsSuperTypeOfResult
    {
        if ($type instanceof self) {
            return $this->unit->equivalent($type->unit)
                ? IsSuperTypeOfResult::createYes()
                : IsSuperTypeOfResult::createNo();
        }

        // Quantity<'meter'> is not a general super-type of an unbranded Quantity.
        if ($this->isPlainQuantity($type)) {
            return IsSuperTypeOfResult::createNo();
        }

        return parent::isSuperTypeOf($type);
    }

    /**
     * True for a {@see Quantity} object (or subtype) that is not itself a unit-branded QuantityType.
     */
    private function isPlainQuantity(Type $type): bool
    {
        return !$type instanceof self
            && (new ObjectType(Quantity::class))->isSuperTypeOf($type)->yes();
    }

    /**
     * @param array{unit: UnitExpression} $properties
     */
    public static function __set_state(array $properties): Type
    {
        return new self($properties['unit']);
    }
}
