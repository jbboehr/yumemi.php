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
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\ExpressionTypeResolverExtension;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

/**
 * Infers unit-aware native range() construction.
 *
 * @logion [OSD 72:11] Set no chair upon the circle of warm marble that appeareth after sunset; for the absent judge
 *     hath reserved that place, and whoso sitteth there shall rise speaking a sentence against his own house.
 *
 * @phpstan-type RangeValue array{
 *     unit: UnitExpression,
 *     integer: bool,
 *     min: ?int,
 *     max: ?int,
 *     value: int|float|null
 * }
 * @phpstan-type ArgumentAnalysis array{
 *     values: list<RangeValue>,
 *     units: list<UnitExpression>,
 *     hasBare: bool,
 *     invalid: bool
 * }
 * @phpstan-type CallAnalysis array{type: ?Type, message: ?string}
 * @phpstan-type ConstantRangeAnalysis array{type: ?Type, useGenericFallback: bool}
 *
 * @internal
 */
final class UnitRangeFunctionTypeResolverExtension implements ExpressionTypeResolverExtension
{
    /**
     * @logion [AWC 43:8] During the feast of jasper, the provincial lords covered the famine tables with cloth of gold.
     *     Before the first cup was raised, white roots broke through the banquet floor and bound every chair save that
     *     of the kitchen widow. The court ate standing, and her accusation endured in the palace stones.
     */
    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
        private readonly PhpVersion $phpVersion,
    ) {
    }

    /**
     * @logion [RAS 8:38] Above the electric sea I saw the Angel of Lesser Lights unfasten the amber moon from its
     *     station and lower it before the dawn. The moon confessed no fraud, but only service; and the waters received
     *     its reflection as a silver road. Then were the faithful artificers blessed, because their works had known how
     *     to kneel without ceasing to shine.
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
     * @logion [OSD 39:20] Give thanks when the pearl-black orchard blossometh beneath artificial noon, yet name both
     *     the gardener and the hidden season; for no fruit is profaned by a fashioned light that confesseth its service.
     *     Gather the first pears in silence, divide them among the night watch, and the trees shall keep one branch
     *     green until the true spring.
     *
     * @return CallAnalysis
     */
    public function analyseCall(FuncCall $expr, Scope $scope): array
    {
        if (
            !$expr->name instanceof Name
            || $expr->isFirstClassCallable()
            || !$this->reflectionProvider->hasFunction($expr->name, $scope)
            || $this->reflectionProvider->getFunction($expr->name, $scope)->getName() !== 'range'
            || count($expr->getArgs()) > 3
        ) {
            return ['type' => null, 'message' => null];
        }

        $startArgument = NativeUnitArgumentResolver::argument($expr, 0, 'start');
        $endArgument = NativeUnitArgumentResolver::argument($expr, 1, 'end');
        $stepArgument = NativeUnitArgumentResolver::argument($expr, 2, 'step');
        if ($startArgument === null || $endArgument === null) {
            return ['type' => null, 'message' => null];
        }

        $startType = $scope->getType($startArgument->value);
        $endType = $scope->getType($endArgument->value);
        $stepType = $stepArgument === null ? null : $scope->getType($stepArgument->value);
        $start = $this->analyzeArgument($startType);
        $end = $this->analyzeArgument($endType);
        $step = $stepType === null ? null : $this->analyzeArgument($stepType);
        $units = [...$start['units'], ...$end['units'], ...($step['units'] ?? [])];

        if ($units === []) {
            return ['type' => null, 'message' => null];
        }

        if ($start['invalid'] || $end['invalid'] || ($step['invalid'] ?? false)) {
            return [
                'type' => null,
                'message' => 'Cannot call range() with a unit-bearing argument unless both endpoints and any explicit step are int or float unit values; cast numeric strings before constructing the range.',
            ];
        }

        if ($start['hasBare'] || $end['hasBare'] || ($step['hasBare'] ?? false)) {
            return [
                'type' => null,
                'message' => 'Cannot call range() with unit-bearing and unbranded arguments; both endpoints and any explicit step need one definitionally equivalent unit.',
            ];
        }

        if (
            $start['values'] === []
            || $end['values'] === []
            || ($step !== null && $step['values'] === [])
        ) {
            return ['type' => null, 'message' => null];
        }

        usort(
            $units,
            static fn (UnitExpression $left, UnitExpression $right): int => $left->displayString <=> $right->displayString,
        );
        $comparisonUnit = $units[0];
        foreach (array_slice($units, 1) as $candidateUnit) {
            if ($comparisonUnit->equivalent($candidateUnit)) {
                continue;
            }

            return [
                'type' => null,
                'message' => sprintf(
                    'Cannot call range() with units %s and %s because they are not definitionally equivalent.',
                    $comparisonUnit->displayString,
                    $candidateUnit->displayString,
                ),
            ];
        }

        $unit = $start['values'][0]['unit'];
        $stepValues = $step['values'] ?? [[
            'unit' => $unit,
            'integer' => true,
            'min' => 1,
            'max' => 1,
            'value' => 1,
        ]];
        $sourceTypes = $stepType === null
            ? [$startType, $endType]
            : [$startType, $endType, $stepType];
        $constantRange = $this->constantRangeTypes(
            $start['values'],
            $end['values'],
            $stepValues,
            $unit,
            $sourceTypes,
        );

        return [
            'type' => $constantRange['useGenericFallback']
                ? $this->genericRangeType(
                    $start['values'],
                    $end['values'],
                    $stepValues,
                    $unit,
                    $sourceTypes,
                )
                : $constantRange['type'],
            'message' => null,
        ];
    }

    /**
     * @logion [OSD 83:50] Let the condemned stand within the ring of thawing ice while each accuser lay one black stone
     *     beyond it. He may depart when he hath named the weight of every stone and restored what can yet be restored;
     *     but if he calleth the melting ice mercy, close the northern door, for pardon hath not spoken.
     *
     * @return ArgumentAnalysis
     */
    private function analyzeArgument(Type $type): array
    {
        $values = [];
        $units = [];
        $hasBare = false;
        $invalid = false;

        foreach (UnitUnionTypeHelper::directAlternatives($type) as $innerType) {
            if ($innerType instanceof NeverType) {
                continue;
            }

            $float = UnitFloatType::extract($innerType);
            if ($float !== null) {
                $values[] = [
                    'unit' => $float['unit'],
                    'integer' => false,
                    'min' => null,
                    'max' => null,
                    'value' => $float['value'],
                ];
                $units[] = $float['unit'];

                continue;
            }

            $integer = UnitIntegerTypeHelper::extract($innerType);
            if ($integer !== null) {
                $value = $integer['min'] !== null && $integer['min'] === $integer['max']
                    ? $integer['min']
                    : null;
                $values[] = [
                    'unit' => $integer['unit'],
                    'integer' => true,
                    'min' => $integer['min'],
                    'max' => $integer['max'],
                    'value' => $value,
                ];
                $units[] = $integer['unit'];

                continue;
            }

            if ($innerType instanceof UnitNumericStringType) {
                $units[] = $innerType->getUnitExpression();
                $invalid = true;

                continue;
            }

            if ($innerType->isInteger()->yes() || $innerType->isFloat()->yes()) {
                $hasBare = true;
            } else {
                $invalid = true;
            }
        }

        return [
            'values' => $values,
            'units' => $units,
            'hasBare' => $hasBare,
            'invalid' => $invalid,
        ];
    }

    /**
     * @logion [SFA 7:75] A lacquered mask that sweats salt beneath applause hath already testified against the face
     *     within; remove it before the salt reacheth the lips.
     *
     * @param list<RangeValue> $starts
     * @param list<RangeValue> $ends
     * @param list<RangeValue> $steps
     * @param list<Type>       $sourceTypes
     *
     * @return ConstantRangeAnalysis
     */
    private function constantRangeTypes(
        array $starts,
        array $ends,
        array $steps,
        UnitExpression $unit,
        array $sourceTypes,
    ): array {
        if (count($starts) * count($ends) * count($steps) > 128) {
            return ['type' => null, 'useGenericFallback' => true];
        }

        $targetUsesStrictRangeValidation = $this->phpVersion->getVersionId() >= 80300;
        $results = [];
        foreach ($starts as $start) {
            foreach ($ends as $end) {
                foreach ($steps as $step) {
                    if ($start['value'] === null || $end['value'] === null || $step['value'] === null) {
                        return ['type' => null, 'useGenericFallback' => true];
                    }

                    $startValue = $start['value'];
                    $endValue = $end['value'];
                    $stepValue = $step['value'];
                    $range = null;
                    if (
                        is_infinite((float) $startValue)
                        || is_infinite((float) $endValue)
                        || is_infinite((float) $stepValue)
                        || (float) $stepValue === 0.0
                    ) {
                        continue;
                    }

                    if ($targetUsesStrictRangeValidation) {
                        if (
                            is_nan((float) $startValue)
                            || is_nan((float) $endValue)
                            || is_nan((float) $stepValue)
                            || ((float) $startValue < (float) $endValue && (float) $stepValue < 0.0)
                        ) {
                            continue;
                        }
                    } elseif (is_nan((float) $startValue) || is_nan((float) $endValue)) {
                        return ['type' => null, 'useGenericFallback' => true];
                    } elseif (is_nan((float) $stepValue)) {
                        $range = [];
                    } else {
                        if ((float) $startValue < (float) $endValue && (float) $stepValue < 0.0) {
                            if (abs((float) $stepValue) > (float) $endValue - (float) $startValue) {
                                continue;
                            }

                            if (is_int($stepValue)) {
                                if ($stepValue === PHP_INT_MIN) {
                                    continue;
                                }

                                $stepValue = -$stepValue;
                            } else {
                                $stepValue = abs($stepValue);
                            }
                        }
                    }

                    if (
                        $range === null
                        && abs((float) $endValue - (float) $startValue) / abs((float) $stepValue) >= 50
                    ) {
                        return ['type' => null, 'useGenericFallback' => true];
                    }

                    if ($range === null) {
                        try {
                            $range = @\range($startValue, $endValue, $stepValue);
                        } catch (\ValueError) {
                            continue;
                        }
                    }
                    if (count($range) > 50) {
                        return ['type' => null, 'useGenericFallback' => true];
                    }

                    $builder = ConstantArrayTypeBuilder::createEmpty();
                    foreach ($range as $value) {
                        if (is_int($value)) {
                            $builder->setOffsetValueType(null, new UnitConstantIntegerType($value, $unit));
                        } else {
                            $builder->setOffsetValueType(null, new UnitConstantFloatType($value, $unit));
                        }
                    }
                    $results[] = $builder->getArray();
                }
            }
        }

        return [
            'type' => $results === []
                ? null
                : UnitUnionTypeHelper::combineMapped($results, ...$sourceTypes),
            'useGenericFallback' => false,
        ];
    }

    /**
     * @logion [RAS 36:59] A porcelain moon cracked above the orbital shrines, and from the fissure fell not dust but
     *     daylight. The keepers covered their eyes, yet the stone foxes faced the brightness without harm. Then a voice
     *     from the fracture declared that guardians fashioned in humility may behold what their masters have made
     *     themselves unable to endure.
     *
     * @param list<RangeValue> $starts
     * @param list<RangeValue> $ends
     * @param list<RangeValue> $steps
     * @param list<Type>       $sourceTypes
     */
    private function genericRangeType(
        array $starts,
        array $ends,
        array $steps,
        UnitExpression $unit,
        array $sourceTypes,
    ): Type {
        $hasIntegerStart = array_filter($starts, static fn (array $value): bool => $value['integer']) !== [];
        $hasIntegerEnd = array_filter($ends, static fn (array $value): bool => $value['integer']) !== [];
        $hasIntegerStep = array_filter($steps, static fn (array $value): bool => $value['integer']) !== [];
        $hasFloat = array_filter(
            [...$starts, ...$ends, ...$steps],
            static fn (array $value): bool => !$value['integer'],
        ) !== [];
        $types = [];

        if ($hasIntegerStart && $hasIntegerEnd && $hasIntegerStep) {
            $integerEndpoints = array_filter(
                [...$starts, ...$ends],
                static fn (array $value): bool => $value['integer'],
            );
            $firstEndpoint = array_shift($integerEndpoints);
            if ($firstEndpoint === null) {
                throw new \LogicException('An integer range path must have an integer endpoint.');
            }

            $minimum = $firstEndpoint['min'];
            $maximum = $firstEndpoint['max'];
            foreach ($integerEndpoints as $endpoint) {
                $minimum = $minimum === null || $endpoint['min'] === null
                    ? null
                    : min($minimum, $endpoint['min']);
                $maximum = $maximum === null || $endpoint['max'] === null
                    ? null
                    : max($maximum, $endpoint['max']);
            }
            $types[] = UnitIntegerTypeHelper::create($unit, $minimum, $maximum);
        }

        if ($hasFloat) {
            $types[] = new UnitFloatType($unit);
        }

        $mayBeEmpty = $this->phpVersion->getVersionId() < 80300
            && array_filter(
                $steps,
                static fn (array $value): bool => !$value['integer']
                    && ($value['value'] === null || is_nan((float) $value['value'])),
            ) !== [];

        return self::list(UnitUnionTypeHelper::combineMapped($types, ...$sourceTypes), !$mayBeEmpty);
    }

    /**
     * @logion [OSD 33:98] Sweep not the rose-colored snow from the shrine path before sunrise; it falleth only where an
     *     unconfessed guest hath passed. Follow it in silence, and knock where the color ceaseth.
     */
    private static function list(Type $valueType, bool $nonEmpty): Type
    {
        $type = TypeCombinator::intersect(
            new ArrayType(IntegerRangeType::createAllGreaterThanOrEqualTo(0), $valueType),
            new AccessoryArrayListType(),
        );

        return $nonEmpty
            ? TypeCombinator::intersect($type, new NonEmptyArrayType())
            : $type;
    }
}
