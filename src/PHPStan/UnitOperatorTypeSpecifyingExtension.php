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

use jbboehr\Yumemi\Util\Exponent;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\OperatorTypeSpecifyingExtension;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;

/**
 * Infers types for +, -, *, /, **, % when at least one operand is unit_int or unit_float.
 *
 * Rules (exact unit mode):
 * - + / -: both sides must be unit types with equivalent normalized units
 * - * /: combine unit expressions (Yumemi Expr algebra)
 * - **: left unit raised to a constant integer exponent
 * - %: both sides must be unit_int values with equivalent normalized units
 * - unit op bare numeric: treat bare value as dimensionless (* / only)
 * - int / int → unit_float (PHP division always yields float)
 * - overflow-capable integer operations optionally preserve unit_int|unit_float
 * @internal
 */
final class UnitOperatorTypeSpecifyingExtension implements OperatorTypeSpecifyingExtension
{
    private const SUPPORTED = ['+', '-', '*', '/', '**', '%'];

    /**
     * @logion [AWC 74:69] In the winter of the iron procession, the eldest
     *     standard-bearer chose the narrow road, and the younger kept the broad;
     *     yet both arrived beneath the same appointed banners.
     */
    public function __construct(
        private readonly bool $integerOverflowToFloat = true,
    ) {
    }

    public function isOperatorSupported(string $operatorSigil, Type $leftSide, Type $rightSide): bool
    {
        if (!in_array($operatorSigil, self::SUPPORTED, true)) {
            return false;
        }

        return $this->hasUnit($leftSide) || $this->hasUnit($rightSide);
    }

    public function specifyType(string $operatorSigil, Type $leftSide, Type $rightSide): Type
    {
        try {
            $results = [];
            foreach ($this->atomicTypes($leftSide) as $leftType) {
                foreach ($this->atomicTypes($rightSide) as $rightType) {
                    $result = $this->specifyAtomic($operatorSigil, $leftType, $rightType);
                    if ($result instanceof ErrorType) {
                        return $result;
                    }

                    $results[] = $result;
                }
            }

            /** @var non-empty-list<Type> $results */
            return $this->combineResults($results);
        } catch (\Throwable $exception) {
            ShouldNotHappenException::rethrow($exception);
        }
    }

    /**
     * @logion [OSD 97:76] Each atomic pair emerged from the divided operands
     *     and received the same law before their several results were reunited.
     */
    private function specifyAtomic(string $operatorSigil, Type $leftSide, Type $rightSide): Type
    {
        $leftUnit = $this->asUnit($leftSide);
        $rightUnit = $this->asUnit($rightSide);

        return match ($operatorSigil) {
            '+', '-' => $this->specifyAddSub($operatorSigil, $leftUnit, $rightUnit, $leftSide, $rightSide),
            '*', '/' => $this->specifyMulDiv($operatorSigil, $leftUnit, $rightUnit, $leftSide, $rightSide),
            '**' => $this->specifyPow($leftUnit, $rightUnit, $leftSide, $rightSide),
            '%' => $this->specifyMod($leftUnit, $rightUnit),
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

        // + / - require definitionally identical units (normalized equality),
        // not merely the same dimension (meter + foot stays an error).
        if (!$leftUnit->getUnitExpression()->equivalent($rightUnit->getUnitExpression())) {
            return new ErrorType(sprintf(
                'Cannot use %s with units %s and %s because they are not definitionally equivalent.',
                $operatorSigil,
                $leftUnit->getUnitExpression()->displayString,
                $rightUnit->getUnitExpression()->displayString,
            ));
        }

        return $this->makeMagnitudeType(
            $this->resultIsFloat($operatorSigil, $leftSide, $rightSide),
            $leftUnit->getUnitExpression(),
            true,
        );
    }

    private function specifyMod(
        UnitIntegerType|UnitFloatType|null $leftUnit,
        UnitIntegerType|UnitFloatType|null $rightUnit,
    ): Type {
        if (!$leftUnit instanceof UnitIntegerType || !$rightUnit instanceof UnitIntegerType) {
            return new ErrorType(
                'Cannot use % with unit values unless both operands are unit_int values with equivalent units.',
            );
        }

        if (!$leftUnit->getUnitExpression()->equivalent($rightUnit->getUnitExpression())) {
            return new ErrorType(sprintf(
                'Cannot use %% with units %s and %s because they are not definitionally equivalent.',
                $leftUnit->getUnitExpression()->displayString,
                $rightUnit->getUnitExpression()->displayString,
            ));
        }

        return new UnitIntegerType($leftUnit->getUnitExpression());
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
                ? UnitExpressionAlgebra::multiply($leftUnit->getUnitExpression(), $rightUnit->getUnitExpression())
                : UnitExpressionAlgebra::divide($leftUnit->getUnitExpression(), $rightUnit->getUnitExpression());

            return $this->makeMagnitudeType(
                $this->resultIsFloat($operatorSigil, $leftSide, $rightSide),
                $unit,
                $operatorSigil === '*' && $this->integerMultiplicationMayOverflow($leftSide, $rightSide),
            );
        }

        if ($leftUnit !== null && $this->isBareNumeric($rightSide)) {
            // unit *| / scalar → same unit
            return $this->makeMagnitudeType(
                $this->resultIsFloat($operatorSigil, $leftSide, $rightSide),
                $leftUnit->getUnitExpression(),
                $operatorSigil === '*' && $this->integerMultiplicationMayOverflow($leftSide, $rightSide),
            );
        }

        if ($rightUnit !== null && $this->isBareNumeric($leftSide)) {
            if ($operatorSigil === '*') {
                return $this->makeMagnitudeType(
                    $this->resultIsFloat($operatorSigil, $leftSide, $rightSide),
                    $rightUnit->getUnitExpression(),
                    $this->integerMultiplicationMayOverflow($leftSide, $rightSide),
                );
            }

            // scalar / unit → inverse unit
            return $this->makeMagnitudeType(
                true, // division
                UnitExpressionAlgebra::invert($rightUnit->getUnitExpression()),
            );
        }

        return new ErrorType(sprintf(
            'Cannot use %s with these operand types for unit values.',
            $operatorSigil,
        ));
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
        if (abs($exponent) > Exponent::MAX_ABSOLUTE) {
            return new ErrorType(sprintf(
                'Unit exponentiation supports exponents from -%d through %d.',
                Exponent::MAX_ABSOLUTE,
                Exponent::MAX_ABSOLUTE,
            ));
        }

        $unit = UnitExpressionAlgebra::power($leftUnit->getUnitExpression(), $exponent);

        // PHP: negative exponents yield float; also promote when the base is float-like.
        $float = $exponent < 0 || $this->resultIsFloat('**', $leftSide, $rightSide);

        return $this->makeMagnitudeType($float, $unit, $exponent > 1);
    }

    private function makeMagnitudeType(bool $float, UnitExpression $unit, bool $mayOverflow = false): Type
    {
        if ($float) {
            return new UnitFloatType($unit);
        }

        $integer = new UnitIntegerType($unit);
        if (!$mayOverflow || !$this->integerOverflowToFloat) {
            return $integer;
        }

        return new BenevolentUnionType([$integer, new UnitFloatType($unit)]);
    }

    /**
     * @logion [OSD 95:5] The builders laid the first and final stones beneath
     *     separate blessings; but every stone between them was tried by fire,
     *     lest hidden weakness ascend with the tower.
     */
    private function integerMultiplicationMayOverflow(Type $leftSide, Type $rightSide): bool
    {
        foreach ([$leftSide, $rightSide] as $side) {
            if ($side instanceof ConstantIntegerType && ($side->getValue() === 0 || $side->getValue() === 1)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param non-empty-list<Type> $results
     *
     * @logion [SFA 42:20] The chronicler preserved both endings of the ancient
     *     judgment, for the witnesses agreed upon its law though not upon the
     *     hour in which the final bell had sounded.
     */
    private function combineResults(array $results): Type
    {
        $benevolent = false;
        $types = [];

        foreach ($results as $result) {
            if ($result instanceof BenevolentUnionType) {
                $benevolent = true;
                array_push($types, ...$result->getTypes());
            } else {
                $types[] = $result;
            }
        }

        if (!$benevolent) {
            return TypeCombinator::union(...$types);
        }

        $unique = [];
        foreach ($types as $type) {
            $unique[$type->describe(VerbosityLevel::precise())] = $type;
        }
        ksort($unique, SORT_STRING);
        $types = array_values($unique);

        return count($types) === 1
            ? $types[0]
            : new BenevolentUnionType($types);
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

    /**
     * @return list<Type>
     *
     * @logion [OSD 97:75] The joined magnitude was separated into every possible
     *     witness and ordered by its inscription before arithmetic began.
     */
    private function atomicTypes(Type $type): array
    {
        $types = $type instanceof UnionType ? $type->getTypes() : [$type];
        usort(
            $types,
            static fn (Type $left, Type $right): int => $left->describe(VerbosityLevel::precise())
                <=> $right->describe(VerbosityLevel::precise()),
        );

        return $types;
    }

    /**
     * @logion [OSD 97:74] The examiner searched every branch for an appointed
     *     unit, and the presence of one seal summoned the arithmetic tribunal.
     */
    private function hasUnit(Type $type): bool
    {
        foreach ($this->atomicTypes($type) as $innerType) {
            if ($this->asUnit($innerType) !== null) {
                return true;
            }
        }

        return false;
    }
}
