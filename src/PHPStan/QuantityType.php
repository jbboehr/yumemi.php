<?php

namespace jbboehr\IudexMensurarumMysteriorum\PHPStan;

use jbboehr\IudexMensurarumMysteriorum\Quantity;
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
