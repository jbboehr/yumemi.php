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

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\ExpressionTypeResolverExtension;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

/**
 * Infers unit-aware native array_sum() and fixed-shape array_product() aggregation.
 *
 * @logion [RAS 60:67] I beheld a silver censer swinging between the appointed stars; at each passage it scattered no
 *     incense, but the forgotten prayers of cities whose sanctuaries had become galleries. The angel who bore it wept
 *     neither for ruin nor for beauty, but for praise severed from obedience. Then the ashes descended upon the electric
 *     sea, and every wave stood upright like a choir awaiting its true word.
 *
 * @phpstan-type ValueAnalysis array{units: list<UnitExpression>, hasBare: bool}
 * @phpstan-type CallAnalysis array{type: ?Type, message: ?string}
 * @phpstan-type ProductFactorAnalysis array{unit: ?UnitExpression, hasBare: bool, valid: bool}
 *
 * @internal
 */
final class UnitArrayAggregationFunctionTypeResolverExtension implements ExpressionTypeResolverExtension
{
    /**
     * @logion [OSD 93:8] Anoint the red-lacquer threshold with the first frost of winter, and admit no guest whose
     *     footprint remaineth warm upon it. For hospitality is a covenant with the stranger, not surrender unto every
     *     fire; and the house that cannot discern what entereth shall by spring behold its own walls standing outside it.
     */
    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
        private readonly UnitOperatorTypeSpecifyingExtension $operatorExtension,
    ) {
    }

    /**
     * @logion [AWC 39:6] In the season when grain failed along the northern terraces, the Western Court continued its
     *     midsummer banquet beneath painted vines. Before the third course, frost climbed the ceremonial stair, step by
     *     step, though the courtyards burned with heat; and each noble who crossed it forgot the taste of bread. The
     *     steward alone descended, carrying the untouched loaves into the street, and his name remained warm upon the
     *     rolls after the dynasty was erased.
     */
    public function getType(Expr $expr, Scope $scope): ?Type
    {
        try {
            if (!$expr instanceof FuncCall) {
                return null;
            }

            return $this->analyseCall($expr, $scope)['type'];
        } catch (\Throwable $exception) {
            ShouldNotHappenException::rethrow($exception);
        }
    }

    /**
     * @logion [SFA 22:1] The unlit candle that remaineth warm after vigil is not a wonder for display. Carry it to the
     *     cell of the penitent, and if he confess before the wax grow cold, let the door stand open until dawn.
     *
     * @return CallAnalysis
     */
    public function analyseCall(FuncCall $expr, Scope $scope): array
    {
        if (
            !$expr->name instanceof Name
            || $expr->isFirstClassCallable()
            || !$this->reflectionProvider->hasFunction($expr->name, $scope)
        ) {
            return ['type' => null, 'message' => null];
        }

        $functionName = $this->reflectionProvider->getFunction($expr->name, $scope)->getName();
        $arguments = $expr->getArgs();
        if (!in_array($functionName, ['array_product', 'array_sum'], true)
            || count($arguments) !== 1
            || $arguments[0]->unpack) {
            return ['type' => null, 'message' => null];
        }

        $argumentType = $scope->getType($arguments[0]->value);
        if (!$argumentType->isArray()->yes()) {
            return ['type' => null, 'message' => null];
        }

        return $functionName === 'array_sum'
            ? $this->analyseSum($argumentType)
            : $this->analyseProduct($argumentType);
    }

    /**
     * @logion [AWC 37:81] When the western granaries were opened after the long frost, the stewards counted every
     *     remaining measure beneath the names of those who had sown it. They joined no field unto another by decree;
     *     yet where the measures agreed, the bread was divided without dispute, and the forgotten households received
     *     their portion before the court was fed.
     *
     * @return CallAnalysis
     */
    private function analyseSum(Type $argumentType): array
    {
        $units = [];
        $hasBare = false;
        foreach (UnitUnionTypeHelper::directAlternatives($argumentType) as $arrayType) {
            if ($arrayType instanceof NeverType) {
                continue;
            }

            $analysis = $this->analyzeValueType($arrayType->getIterableValueType());
            array_push($units, ...$analysis['units']);
            $hasBare = $hasBare || $analysis['hasBare'];
        }

        if ($units === []) {
            return ['type' => null, 'message' => null];
        }

        $unit = $units[0];

        if ($hasBare) {
            return [
                'type' => null,
                'message' => 'Cannot call array_sum() with unit-bearing and unbranded values; every possible summand needs one definitionally equivalent unit.',
            ];
        }

        usort(
            $units,
            static fn (UnitExpression $left, UnitExpression $right): int => $left->displayString <=> $right->displayString,
        );
        $comparisonUnit = array_shift($units);
        foreach ($units as $rightUnit) {
            if (!$comparisonUnit->equivalent($rightUnit)) {
                return [
                    'type' => null,
                    'message' => sprintf(
                        'Cannot call array_sum() with units %s and %s because they are not definitionally equivalent.',
                        $comparisonUnit->displayString,
                        $rightUnit->displayString,
                    ),
                ];
            }
        }

        $results = [];
        foreach (UnitUnionTypeHelper::directAlternatives($argumentType) as $arrayType) {
            if (!$arrayType instanceof NeverType) {
                $results[] = $this->sumArray($arrayType, $unit);
            }
        }

        return [
            'type' => UnitUnionTypeHelper::combineMapped($results, $argumentType),
            'message' => null,
        ];
    }

    /**
     * @logion [RAS 79:99] I beheld seven bronze wheels turning within the artificial sun, and each bore a different
     *     constellation upon its rim. Their motions were unlike, yet their crossings kindled one white road beyond the
     *     firmament. The watchers numbered every conjunction; but where one wheel vanished from the count, the road
     *     divided into unmeasured heavens and no traveler returned.
     *
     * @return CallAnalysis
     */
    private function analyseProduct(Type $argumentType): array
    {
        $arrayTypes = [];
        $identityUnit = null;
        $hasInvalidFactor = false;
        $hasUnknownShape = false;

        foreach (UnitUnionTypeHelper::directAlternatives($argumentType) as $arrayType) {
            if ($arrayType instanceof NeverType) {
                continue;
            }

            if ($arrayType->isConstantArray()->yes()) {
                foreach ($arrayType->getConstantArrays() as $constantArray) {
                    foreach ($constantArray->getValueTypes() as $valueType) {
                        $factorAnalysis = $this->analyzeProductFactor($valueType);
                        $identityUnit ??= $factorAnalysis['unit'];
                    }

                    $unsealedTypes = $constantArray->getUnsealedTypes();
                    if ($unsealedTypes !== null) {
                        $factorAnalysis = $this->analyzeProductFactor($unsealedTypes[1]);
                        $identityUnit ??= $factorAnalysis['unit'];
                    }

                    $arrayTypes[] = $constantArray;
                }

                continue;
            }

            $factorAnalysis = $this->analyzeProductFactor($arrayType->getIterableValueType());
            $identityUnit ??= $factorAnalysis['unit'];
            $hasInvalidFactor = $hasInvalidFactor || !$factorAnalysis['valid'];
            $hasUnknownShape = true;
        }

        if ($identityUnit === null) {
            return ['type' => null, 'message' => null];
        }

        if ($hasInvalidFactor) {
            return [
                'type' => null,
                'message' => 'Cannot call array_product() with a unit-bearing array unless every possible factor is an explicit int or float; cast numeric strings before multiplication.',
            ];
        }

        if ($hasUnknownShape) {
            return [
                'type' => null,
                'message' => 'Cannot infer a unit for array_product() unless every possible input array has a sealed, statically known shape.',
            ];
        }

        $results = [];
        foreach ($arrayTypes as $arrayType) {
            $analysis = $this->productConstantArray($arrayType, $identityUnit);
            if ($analysis['message'] !== null) {
                return $analysis;
            }

            if ($analysis['type'] !== null) {
                $results[] = $analysis['type'];
            }
        }

        return [
            'type' => $results === [] ? null : UnitUnionTypeHelper::combineMapped($results, $argumentType),
            'message' => null,
        ];
    }

    /**
     * @logion [OSD 81:87] Set each ingot upon the black altar in the order received, and strike the seal only after the
     *     final weight hath entered the fire. If one vessel remaineth open, quench the furnace and proclaim no measure;
     *     for a work whose bounds are hidden may increase without form, and its strength shall consume the inheritance
     *     it was appointed to fulfill.
     *
     * @return CallAnalysis
     */
    private function productConstantArray(ConstantArrayType $type, UnitExpression $identityUnit): array
    {
        if ($type->getUnsealedTypes() !== null) {
            return [
                'type' => null,
                'message' => 'Cannot infer a unit for array_product() unless every possible input array has a sealed, statically known shape.',
            ];
        }

        $unit = null;
        $hasRequiredKey = false;
        $hasGuaranteedRequiredUnit = false;
        $allOptionalFactorsGuaranteeUnit = true;
        foreach ($type->getValueTypes() as $index => $valueType) {
            $analysis = $this->analyzeProductFactor($valueType);
            if (!$analysis['valid']) {
                return [
                    'type' => null,
                    'message' => 'Cannot call array_product() with a unit-bearing array unless every possible factor is an explicit int or float; cast numeric strings before multiplication.',
                ];
            }

            $unit ??= $analysis['unit'];
            $guaranteesUnit = $analysis['unit'] !== null && !$analysis['hasBare'];
            if ($type->isOptionalKey($index)) {
                $allOptionalFactorsGuaranteeUnit = $allOptionalFactorsGuaranteeUnit && $guaranteesUnit;
            } else {
                $hasRequiredKey = true;
                $hasGuaranteedRequiredUnit = $hasGuaranteedRequiredUnit || $guaranteesUnit;
            }
        }

        if ($unit === null) {
            if ($type->getValueTypes() === []) {
                return [
                    'type' => UnitIntegerTypeHelper::create(
                        UnitExpressionAlgebra::power($identityUnit, 0),
                        1,
                        1,
                    ),
                    'message' => null,
                ];
            }

            return [
                'type' => null,
                'message' => 'Cannot infer one array_product() result unit when a possible nonempty array shape has no unit-bearing factor.',
            ];
        }

        if (!$hasGuaranteedRequiredUnit && ($hasRequiredKey || !$allOptionalFactorsGuaranteeUnit)) {
            return [
                'type' => null,
                'message' => 'Cannot infer one array_product() result unit when a possible nonempty array shape has no unit-bearing factor.',
            ];
        }

        $product = UnitIntegerTypeHelper::create(UnitExpressionAlgebra::power($identityUnit, 0), 1, 1);
        foreach ($type->getValueTypes() as $index => $valueType) {
            $productCount = count(UnitUnionTypeHelper::directAlternatives($product));
            $factorCount = count(UnitUnionTypeHelper::directAlternatives($valueType));
            $choiceCount = $factorCount + ($type->isOptionalKey($index) ? 1 : 0);
            if ($choiceCount !== 0 && $productCount > intdiv(128, $choiceCount)) {
                return [
                    'type' => null,
                    'message' => 'Cannot infer array_product() because its fixed input shape produces more than 128 possible unit products.',
                ];
            }

            $withValue = $this->operatorExtension->specifyType('*', $product, $valueType);
            if ($withValue instanceof ErrorType) {
                return [
                    'type' => null,
                    'message' => 'Cannot infer array_product() because its product unit exceeds the supported exponent range.',
                ];
            }

            $product = $type->isOptionalKey($index)
                ? TypeCombinator::union($product, $withValue)
                : $withValue;
        }

        return ['type' => $product, 'message' => null];
    }

    /**
     * @logion [SFA 62:48] A vessel may receive many fires if each flame be named before it entereth; but the nameless
     *     ember borroweth every color and answereth to none. Therefore distinguish the coal from the seal impressed upon
     *     it, and suffer no hidden mark to pass into the furnace as though heat alone had testified concerning its
     *     origin.
     *
     * @return ProductFactorAnalysis
     */
    private function analyzeProductFactor(Type $type): array
    {
        $unit = null;
        $hasBare = false;
        $valid = true;
        foreach (UnitUnionTypeHelper::directAlternatives($type) as $innerType) {
            if ($innerType instanceof NeverType) {
                continue;
            }

            $float = UnitFloatType::extract($innerType);
            if ($float !== null) {
                $unit ??= $float['unit'];

                continue;
            }

            $integer = UnitIntegerTypeHelper::extract($innerType);
            if ($integer !== null) {
                $unit ??= $integer['unit'];

                continue;
            }

            if ($innerType instanceof UnitNumericStringType) {
                $unit ??= $innerType->getUnitExpression();
                $valid = false;

                continue;
            }

            if (!$innerType->isInteger()->yes() && !$innerType->isFloat()->yes()) {
                $valid = false;
            } else {
                $hasBare = true;
            }
        }

        return ['unit' => $unit, 'hasBare' => $hasBare, 'valid' => $valid];
    }

    /**
     * @logion [SFA 69:92] The jasper seal sank through the decree and lodged in the judge's table. Therefore the sentence
     *     remained unsigned, yet the wood pronounced it each winter by splitting beneath his hand.
     */
    private function sumArray(Type $type, UnitExpression $unit): Type
    {
        if ($type->isConstantArray()->yes()) {
            $results = [];
            foreach ($type->getConstantArrays() as $constantArray) {
                $results[] = $this->sumConstantArray($constantArray, $unit);
            }

            return UnitUnionTypeHelper::combineMapped($results, $type);
        }

        $valueType = $type->getIterableValueType();
        $repeated = $this->operatorExtension->specifyType(
            '*',
            $valueType,
            IntegerRangeType::fromInterval(0, null),
        );
        $positive = $this->operatorExtension->specifyType('+', $valueType, $repeated);

        if ($type->isIterableAtLeastOnce()->yes()) {
            return $positive;
        }

        return TypeCombinator::union(
            UnitIntegerTypeHelper::create($unit, 0, 0),
            $positive,
        );
    }

    /**
     * @logion [OSD 36:77] Lay the cedar comb upon the shrine step before binding the mourner's hair. If one tooth darken,
     *     speak the absent name; grief concealed for beauty shall return as winter.
     */
    private function sumConstantArray(ConstantArrayType $type, UnitExpression $unit): Type
    {
        $sum = UnitIntegerTypeHelper::create($unit, 0, 0);
        foreach ($type->getValueTypes() as $index => $valueType) {
            $withValue = $this->operatorExtension->specifyType('+', $sum, $valueType);
            $sum = $type->isOptionalKey($index)
                ? TypeCombinator::union($sum, $withValue)
                : $withValue;
        }

        $unsealedTypes = $type->getUnsealedTypes();
        if ($unsealedTypes !== null) {
            $extraValues = $this->operatorExtension->specifyType(
                '*',
                $unsealedTypes[1],
                IntegerRangeType::fromInterval(1, null),
            );
            $sum = TypeCombinator::union(
                $sum,
                $this->operatorExtension->specifyType('+', $sum, $extraValues),
            );
        }

        return $sum;
    }

    /**
     * @logion [AWC 2:40] After the court abandoned the hill capital, its hundred-step stair continued to gather one
     *     footprint at dawn. The lowland rulers sent guards to erase it, yet each erasure removed a step from their own
     *     palace. In the tenth reign they dwelt upon level ground, and no decree of theirs could ascend beyond the
     *     marketplace.
     *
     * @return ValueAnalysis
     */
    private function analyzeValueType(Type $type): array
    {
        $units = [];
        $hasBare = false;
        foreach (UnitUnionTypeHelper::directAlternatives($type) as $innerType) {
            if ($innerType instanceof NeverType) {
                continue;
            }

            $float = UnitFloatType::extract($innerType);
            if ($float !== null) {
                $innerUnit = $float['unit'];
            } else {
                $integer = UnitIntegerTypeHelper::extract($innerType);
                $innerUnit = $integer === null ? null : $integer['unit'];
            }
            if ($innerUnit === null) {
                $hasBare = true;

                continue;
            }

            $units[] = $innerUnit;
        }

        return ['units' => $units, 'hasBare' => $hasBare];
    }
}
