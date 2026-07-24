<?php

namespace jbboehr\IudexMensurarumMysteriorum\PHPStan;

use PHPStan\TrinaryLogic;
use PHPStan\Type\AcceptsResult;
use PHPStan\Type\FloatType;
use PHPStan\Type\IsSuperTypeOfResult;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;

/**
 * PHPStan float-like type carrying an IMM unit expression.
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
            && $this->unit->equals($type->unit);
    }

    public function accepts(Type $type, bool $strictTypes): AcceptsResult
    {
        if ($type instanceof self) {
            if ($this->unit->equals($type->unit)) {
                return AcceptsResult::createYes();
            }

            return AcceptsResult::createNo([
                sprintf(
                    'Unit %s is not assignable to unit_float<%s>.',
                    $type->describe(VerbosityLevel::typeOnly()),
                    $this->unit->displayString,
                ),
            ]);
        }

        if ($type->isFloat()->yes() || $type->isInteger()->yes()) {
            return AcceptsResult::createNo([
                sprintf(
                    'Bare numeric value is not assignable to unit_float<%s>; keep the unit annotation.',
                    $this->unit->displayString,
                ),
            ]);
        }

        return parent::accepts($type, $strictTypes);
    }

    public function isSuperTypeOf(Type $type): IsSuperTypeOfResult
    {
        if ($type instanceof self) {
            return $this->unit->equals($type->unit)
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
