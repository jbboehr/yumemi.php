<?php

namespace jbboehr\IudexMensurarumMysteriorum\PHPStan;

use jbboehr\IudexMensurarumMysteriorum\Formatter\ExprFormatter;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\OperatorTypeSpecifyingExtension;
use PHPStan\Type\Type;

/**
 * Infers types for +, -, *, /, **, % when at least one operand is unit_int or unit_float.
 *
 * Rules (exact unit mode):
 * - + / - / %: both sides must be unit types with the same reduced unit
 * - * /: combine unit expressions (IMM Expr algebra)
 * - **: left unit raised to a constant integer exponent
 * - unit op bare numeric: treat bare value as dimensionless (* / only)
 * - int / int → unit_float (PHP division always yields float)
 */
final class UnitOperatorTypeSpecifyingExtension implements OperatorTypeSpecifyingExtension
{
    private const SUPPORTED = ['+', '-', '*', '/', '**', '%'];

    public function isOperatorSupported(string $operatorSigil, Type $leftSide, Type $rightSide): bool
    {
        if (!in_array($operatorSigil, self::SUPPORTED, true)) {
            return false;
        }

        return $this->asUnit($leftSide) !== null || $this->asUnit($rightSide) !== null;
    }

    public function specifyType(string $operatorSigil, Type $leftSide, Type $rightSide): Type
    {
        $leftUnit = $this->asUnit($leftSide);
        $rightUnit = $this->asUnit($rightSide);

        return match ($operatorSigil) {
            '+', '-', '%' => $this->specifyAddSub($operatorSigil, $leftUnit, $rightUnit, $leftSide, $rightSide),
            '*', '/' => $this->specifyMulDiv($operatorSigil, $leftUnit, $rightUnit, $leftSide, $rightSide),
            '**' => $this->specifyPow($leftUnit, $rightUnit, $leftSide, $rightSide),
            default => new ErrorType('Unsupported unit operator: ' . $operatorSigil),
        };
    }

    private function specifyAddSub(
        string $operatorSigil,
        UnitIntegerType|UnitFloatType|null $leftUnit,
        UnitIntegerType|UnitFloatType|null $rightUnit,
        Type $leftSide,
        Type $rightSide,
    ): Type {
        if ($leftUnit === null || $rightUnit === null) {
            return new ErrorType(sprintf(
                'Cannot use %s between a unit type and a bare numeric value; both operands need units.',
                $operatorSigil,
            ));
        }

        if (!$leftUnit->getUnitExpression()->equals($rightUnit->getUnitExpression())) {
            return new ErrorType(sprintf(
                'Cannot use %s with incompatible units %s and %s.',
                $operatorSigil,
                $leftUnit->getUnitExpression()->displayString,
                $rightUnit->getUnitExpression()->displayString,
            ));
        }

        return $this->makeMagnitudeType(
            $this->resultIsFloat($operatorSigil, $leftSide, $rightSide),
            $leftUnit->getUnitExpression(),
        );
    }

    private function specifyMulDiv(
        string $operatorSigil,
        UnitIntegerType|UnitFloatType|null $leftUnit,
        UnitIntegerType|UnitFloatType|null $rightUnit,
        Type $leftSide,
        Type $rightSide,
    ): Type {
        if ($leftUnit !== null && $rightUnit !== null) {
            $unit = $operatorSigil === '*'
                ? $this->multiplyUnits($leftUnit->getUnitExpression(), $rightUnit->getUnitExpression())
                : $this->divideUnits($leftUnit->getUnitExpression(), $rightUnit->getUnitExpression());

            return $this->makeMagnitudeType(
                $this->resultIsFloat($operatorSigil, $leftSide, $rightSide),
                $unit,
            );
        }

        if ($leftUnit !== null && $this->isBareNumeric($rightSide)) {
            // unit *| / scalar → same unit
            return $this->makeMagnitudeType(
                $this->resultIsFloat($operatorSigil, $leftSide, $rightSide),
                $leftUnit->getUnitExpression(),
            );
        }

        if ($rightUnit !== null && $this->isBareNumeric($leftSide)) {
            if ($operatorSigil === '*') {
                return $this->makeMagnitudeType(
                    $this->resultIsFloat($operatorSigil, $leftSide, $rightSide),
                    $rightUnit->getUnitExpression(),
                );
            }

            // scalar / unit → inverse unit
            return $this->makeMagnitudeType(
                true, // division
                $this->invertUnit($rightUnit->getUnitExpression()),
            );
        }

        return new ErrorType(sprintf(
            'Cannot use %s with these operand types for unit values.',
            $operatorSigil,
        ));
    }

    private function multiplyUnits(UnitExpression $left, UnitExpression $right): UnitExpression
    {
        $expr = $left->expr->mul($right->expr);

        return new UnitExpression(
            $expr,
            ExprFormatter::format($expr),
            $left->dimension->mul($right->dimension),
        );
    }

    private function divideUnits(UnitExpression $left, UnitExpression $right): UnitExpression
    {
        $expr = $left->expr->div($right->expr);

        return new UnitExpression(
            $expr,
            ExprFormatter::format($expr),
            $left->dimension->div($right->dimension),
        );
    }

    private function invertUnit(UnitExpression $unit): UnitExpression
    {
        $expr = $unit->expr->pow(-1);

        return new UnitExpression(
            $expr,
            ExprFormatter::format($expr),
            $unit->dimension->pow(-1),
        );
    }

    private function specifyPow(
        UnitIntegerType|UnitFloatType|null $leftUnit,
        UnitIntegerType|UnitFloatType|null $rightUnit,
        Type $leftSide,
        Type $rightSide,
    ): Type {
        if ($rightUnit !== null) {
            return new ErrorType('Cannot raise a value to a unit power; the exponent must be a bare integer.');
        }

        if ($leftUnit === null) {
            return new ErrorType('Cannot raise a bare numeric value to a power involving units.');
        }

        if (!$rightSide instanceof ConstantIntegerType) {
            return new ErrorType(
                'Unit exponentiation requires a constant integer exponent (e.g. $length ** 2).',
            );
        }

        $exponent = $rightSide->getValue();
        $unit = $this->powerUnit($leftUnit->getUnitExpression(), $exponent);

        // PHP: negative exponents yield float; also promote when the base is float-like.
        $float = $exponent < 0 || $this->resultIsFloat('**', $leftSide, $rightSide);

        return $this->makeMagnitudeType($float, $unit);
    }

    private function powerUnit(UnitExpression $unit, int $exponent): UnitExpression
    {
        $expr = $unit->expr->pow($exponent);

        return new UnitExpression(
            $expr,
            ExprFormatter::format($expr),
            $unit->dimension->pow($exponent),
        );
    }

    private function makeMagnitudeType(bool $float, UnitExpression $unit): UnitIntegerType|UnitFloatType
    {
        return $float ? new UnitFloatType($unit) : new UnitIntegerType($unit);
    }

    /**
     * PHP: / always returns float. Otherwise float if either side is float-like.
     */
    private function resultIsFloat(string $operatorSigil, Type $leftSide, Type $rightSide): bool
    {
        if ($operatorSigil === '/') {
            return true;
        }

        if ($leftSide instanceof UnitFloatType || $rightSide instanceof UnitFloatType) {
            return true;
        }

        return $leftSide->isFloat()->yes() || $rightSide->isFloat()->yes();
    }

    private function isBareNumeric(Type $type): bool
    {
        if ($this->asUnit($type) !== null) {
            return false;
        }

        return $type->isInteger()->yes() || $type->isFloat()->yes();
    }

    private function asUnit(Type $type): UnitIntegerType|UnitFloatType|null
    {
        if ($type instanceof UnitIntegerType || $type instanceof UnitFloatType) {
            return $type;
        }

        return null;
    }
}
