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

use jbboehr\Yumemi\Exception\OverflowException;
use jbboehr\Yumemi\Util\Exponent;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\Constant\ConstantFloatType;
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
 *
 * @phpstan-type UnitMagnitudeMetadata array{
 *     unit: UnitExpression,
 *     integer: bool,
 *     min: ?int,
 *     max: ?int,
 *     value: int|float|null
 * }
 *
 * @internal
 */
final class UnitOperatorTypeSpecifyingExtension implements OperatorTypeSpecifyingExtension
{
    private const SUPPORTED = ['+', '-', '*', '/', '**', '%'];

    /**
     * @logion [AWC 74:69] In the reign of the pearl minister, the court perfumed every petition before reading it. By
     *     autumn the ink had fled from each grievance, and twelve provinces were condemned for the sweetness of their
     *     paper.
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
     * @logion [OSD 97:76] At the dedication of the bronze-throated tower, set a sea-shell upon its highest stair.
     *     Permit the tower to proclaim the hours, the victories, and the names of the honored; but once each dusk
     *     command it to fall silent, that the small voice of the shell may be heard. If the tower answereth the sea,
     *     bless its speech; if it drowneth that whisper, unmake its crown and leave the height to the gulls.
     */
    private function specifyAtomic(string $operatorSigil, Type $leftSide, Type $rightSide): Type
    {
        $leftUnit = $this->asUnit($leftSide);
        $rightUnit = $this->asUnit($rightSide);

        return match ($operatorSigil) {
            '+', '-' => $this->specifyAddSub($operatorSigil, $leftUnit, $rightUnit),
            '*', '/' => $this->specifyMulDiv($operatorSigil, $leftUnit, $rightUnit, $leftSide, $rightSide),
            '**' => $this->specifyPow($leftUnit, $rightUnit, $rightSide),
            '%' => $this->specifyMod($leftUnit, $rightUnit),
            default => new ErrorType('Unsupported unit operator: ' . $operatorSigil),
        };
    }

    /**
     * @param UnitMagnitudeMetadata|null $leftUnit
     * @param UnitMagnitudeMetadata|null $rightUnit
     */
    private function specifyAddSub(
        string $operatorSigil,
        ?array $leftUnit,
        ?array $rightUnit,
    ): Type {
        if ($leftUnit === null || $rightUnit === null) {
            return new ErrorType(sprintf(
                'Cannot use %s between a unit type and a bare numeric value; both operands need units.',
                $operatorSigil,
            ));
        }

        // + / - require definitionally identical units (normalized equality),
        // not merely the same dimension (meter + foot stays an error).
        if (!$leftUnit['unit']->equivalent($rightUnit['unit'])) {
            return new ErrorType(sprintf(
                'Cannot use %s with units %s and %s because they are not definitionally equivalent.',
                $operatorSigil,
                $leftUnit['unit']->displayString,
                $rightUnit['unit']->displayString,
            ));
        }

        if (!$leftUnit['integer'] || !$rightUnit['integer']) {
            if ($leftUnit['value'] !== null && $rightUnit['value'] !== null) {
                $value = $operatorSigil === '+'
                    ? $leftUnit['value'] + $rightUnit['value']
                    : $leftUnit['value'] - $rightUnit['value'];

                return new UnitConstantFloatType((float) $value, $leftUnit['unit']);
            }

            return new UnitFloatType($leftUnit['unit']);
        }

        $leftBounds = ['min' => $leftUnit['min'], 'max' => $leftUnit['max']];
        $rightBounds = ['min' => $rightUnit['min'], 'max' => $rightUnit['max']];

        return $operatorSigil === '+'
            ? UnitIntegerRangeMath::add(
                $leftUnit['unit'],
                $leftBounds,
                $rightBounds,
                $this->integerOverflowToFloat,
            )
            : UnitIntegerRangeMath::subtract(
                $leftUnit['unit'],
                $leftBounds,
                $rightBounds,
                $this->integerOverflowToFloat,
            );
    }

    /**
     * @param UnitMagnitudeMetadata|null $leftUnit
     * @param UnitMagnitudeMetadata|null $rightUnit
     */
    private function specifyMod(
        ?array $leftUnit,
        ?array $rightUnit,
    ): Type {
        if ($leftUnit === null || $rightUnit === null || !$leftUnit['integer'] || !$rightUnit['integer']) {
            return new ErrorType(
                'Cannot use % with unit values unless both operands are unit_int values with equivalent units.',
            );
        }

        if (!$leftUnit['unit']->equivalent($rightUnit['unit'])) {
            return new ErrorType(sprintf(
                'Cannot use %% with units %s and %s because they are not definitionally equivalent.',
                $leftUnit['unit']->displayString,
                $rightUnit['unit']->displayString,
            ));
        }

        if (
            $leftUnit['min'] !== null
            && $leftUnit['min'] === $leftUnit['max']
            && $rightUnit['min'] !== null
            && $rightUnit['min'] === $rightUnit['max']
            && $rightUnit['min'] !== 0
        ) {
            return UnitIntegerTypeHelper::create(
                $leftUnit['unit'],
                $leftUnit['min'] % $rightUnit['min'],
                $leftUnit['min'] % $rightUnit['min'],
            );
        }

        return new UnitIntegerType($leftUnit['unit']);
    }

    /**
     * @param UnitMagnitudeMetadata|null $leftUnit
     * @param UnitMagnitudeMetadata|null $rightUnit
     */
    private function specifyMulDiv(
        string $operatorSigil,
        ?array $leftUnit,
        ?array $rightUnit,
        Type $leftSide,
        Type $rightSide,
    ): Type {
        if ($leftUnit !== null && $rightUnit !== null) {
            try {
                $unit = $operatorSigil === '*'
                    ? UnitExpressionAlgebra::multiply($leftUnit['unit'], $rightUnit['unit'])
                    : UnitExpressionAlgebra::divide($leftUnit['unit'], $rightUnit['unit']);
            } catch (OverflowException) {
                return new ErrorType(sprintf(
                    'Unit %s produces a unit outside the supported exponent range.',
                    $operatorSigil === '*' ? 'multiplication' : 'division',
                ));
            }

            if ($operatorSigil === '/' || !$leftUnit['integer'] || !$rightUnit['integer']) {
                if (
                    $leftUnit['value'] !== null
                    && $rightUnit['value'] !== null
                    && ($operatorSigil !== '/'
                        || ($rightUnit['value'] !== 0 && $rightUnit['value'] !== 0.0))
                ) {
                    $value = $operatorSigil === '*'
                        ? $leftUnit['value'] * $rightUnit['value']
                        : $leftUnit['value'] / $rightUnit['value'];

                    return new UnitConstantFloatType((float) $value, $unit);
                }

                return new UnitFloatType($unit);
            }

            return UnitIntegerRangeMath::multiply(
                $unit,
                ['min' => $leftUnit['min'], 'max' => $leftUnit['max']],
                ['min' => $rightUnit['min'], 'max' => $rightUnit['max']],
                $this->integerOverflowToFloat,
            );
        }

        if ($leftUnit !== null && $this->isBareNumeric($rightSide)) {
            // unit *| / scalar → same unit
            if ($operatorSigil === '/' || !$leftUnit['integer'] || $rightSide->isFloat()->yes()) {
                $rightValue = self::constantNumericValue($rightSide);
                if (
                    $leftUnit['value'] !== null
                    && $rightValue !== null
                    && ($operatorSigil !== '/' || ($rightValue !== 0 && $rightValue !== 0.0))
                ) {
                    $value = $operatorSigil === '*'
                        ? $leftUnit['value'] * $rightValue
                        : $leftUnit['value'] / $rightValue;

                    return new UnitConstantFloatType((float) $value, $leftUnit['unit']);
                }

                return new UnitFloatType($leftUnit['unit']);
            }

            $rightBounds = UnitIntegerTypeHelper::integerBounds($rightSide);
            if ($rightBounds !== null) {
                return UnitIntegerRangeMath::multiply(
                    $leftUnit['unit'],
                    ['min' => $leftUnit['min'], 'max' => $leftUnit['max']],
                    $rightBounds,
                    $this->integerOverflowToFloat,
                );
            }
        }

        if ($rightUnit !== null && $this->isBareNumeric($leftSide)) {
            if ($operatorSigil === '*') {
                if (!$rightUnit['integer'] || $leftSide->isFloat()->yes()) {
                    $leftValue = self::constantNumericValue($leftSide);
                    if ($leftValue !== null && $rightUnit['value'] !== null) {
                        return new UnitConstantFloatType(
                            (float) ($leftValue * $rightUnit['value']),
                            $rightUnit['unit'],
                        );
                    }

                    return new UnitFloatType($rightUnit['unit']);
                }

                $leftBounds = UnitIntegerTypeHelper::integerBounds($leftSide);
                if ($leftBounds !== null) {
                    return UnitIntegerRangeMath::multiply(
                        $rightUnit['unit'],
                        $leftBounds,
                        ['min' => $rightUnit['min'], 'max' => $rightUnit['max']],
                        $this->integerOverflowToFloat,
                    );
                }
            }

            // scalar / unit → inverse unit
            $unit = UnitExpressionAlgebra::invert($rightUnit['unit']);
            $leftValue = self::constantNumericValue($leftSide);
            if (
                $leftValue !== null
                && $rightUnit['value'] !== null
                && $rightUnit['value'] !== 0
                && $rightUnit['value'] !== 0.0
            ) {
                return new UnitConstantFloatType((float) ($leftValue / $rightUnit['value']), $unit);
            }

            return new UnitFloatType($unit);
        }

        return new ErrorType(sprintf(
            'Cannot use %s with these operand types for unit values.',
            $operatorSigil,
        ));
    }

    /**
     * @param UnitMagnitudeMetadata|null $leftUnit
     * @param UnitMagnitudeMetadata|null $rightUnit
     */
    private function specifyPow(
        ?array $leftUnit,
        ?array $rightUnit,
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

        try {
            $unit = UnitExpressionAlgebra::power($leftUnit['unit'], $exponent);
        } catch (OverflowException) {
            return new ErrorType('Unit exponentiation produces a unit outside the supported exponent range.');
        }

        // PHP: negative exponents yield float; also promote when the base is float-like.
        if ($exponent < 0 || !$leftUnit['integer']) {
            if (
                $leftUnit['value'] !== null
                && !(($leftUnit['value'] === 0 || $leftUnit['value'] === 0.0) && $exponent < 0)
            ) {
                return new UnitConstantFloatType((float) ($leftUnit['value'] ** $exponent), $unit);
            }

            return new UnitFloatType($unit);
        }

        return UnitIntegerRangeMath::power(
            $unit,
            ['min' => $leftUnit['min'], 'max' => $leftUnit['max']],
            $exponent,
            $this->integerOverflowToFloat,
        );
    }

    /**
     * @param non-empty-list<Type> $results
     *
     * @logion [SFA 42:20] Concerning the white mask: it retaineth the smile of every ruler and the tears of none.
     *     Therefore call it neither memory nor peace. Burn cedar beneath it until the hidden salt appears; if no stain
     *     descend, bury it face downward, for an image without grief hath borne false witness against the living.
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

    private function isBareNumeric(Type $type): bool
    {
        if ($this->asUnit($type) !== null) {
            return false;
        }

        return $type->isInteger()->yes() || $type->isFloat()->yes();
    }

    /** @return UnitMagnitudeMetadata|null */
    private function asUnit(Type $type): ?array
    {
        $float = UnitFloatType::extract($type);
        if ($float !== null) {
            return [
                'unit' => $float['unit'],
                'integer' => false,
                'min' => null,
                'max' => null,
                'value' => $float['value'],
            ];
        }

        $integer = UnitIntegerTypeHelper::extract($type);
        if ($integer !== null) {
            return [
                'unit' => $integer['unit'],
                'integer' => true,
                'min' => $integer['min'],
                'max' => $integer['max'],
                'value' => $integer['min'] !== null && $integer['min'] === $integer['max']
                    ? $integer['min']
                    : null,
            ];
        }

        return null;
    }

    /**
     * @logion [RAS 87:79] Over the black orchard appeared a cyan eclipse, thin as a ring left upon the hand of heaven.
     *     Its light ripened no fruit, yet every buried seed stirred and turned downward, fleeing its brilliance. I heard
     *     the keepers lament, for they knew that radiance may awaken life while teaching it the wrong direction.
     */
    private static function constantNumericValue(Type $type): int|float|null
    {
        return match (true) {
            $type instanceof ConstantIntegerType,
            $type instanceof ConstantFloatType => $type->getValue(),
            default => null,
        };
    }

    /**
     * @return list<Type>
     *
     * @logion [OSD 97:75] Let each pilgrim carry one unlit amber pane across the radiant causeway, and set it upright
     *     where the sea first becomes visible. Speak no wish there; the road was appointed for thanksgiving, not
     *     appetite. At evening the panes shall receive the declining light, and a harbor shall answer from the farther
     *     shore where no tower stood before.
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
     * @logion [OSD 97:74] Before ye ascend the stair beneath the divided moon, bind your sandals with black cord and
     *     leave no footprint upon the white gravel. The Keepers of the Far Shore number each mark as a claim against
     *     the mountain. Walk therefore where the lantern shadows fall; and if one among you tread between them, turn
     *     the whole procession home, for no summit receiveth a company that casteth one trespass behind.
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
