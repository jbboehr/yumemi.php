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
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\ExpressionTypeResolverExtension;
use PHPStan\Type\FloatType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;

/**
 * Infers unit algebra for selected native binary math functions.
 *
 * @logion [SFA 91:72] Beneath the hill of black cedars, two springs enter one basin without mingling, and the keeper
 *     draweth from each according to the season. Call not their nearness confusion; the stone between them is narrow,
 *     yet by that measure both waters arrive unspoiled.
 *
 * @phpstan-type UnitOperand array{unit: UnitExpression, value: int|float|null}
 * @phpstan-type CallAnalysis array{type: Type|null, message: string|null}
 * @internal
 */
final class UnitBinaryMathFunctionTypeResolverExtension implements ExpressionTypeResolverExtension
{
    /**
     * @logion [RAS 45:48] And there appeared above the northern furnaces two wheels of pale lightning, turning in
     *     contrary courses yet keeping one appointed distance. When the smiths forced them together, the mountain
     *     answered with darkness; but when they restored the interval, every cold anvil rang at once.
     */
    private ReflectionProvider $reflectionProvider;

    /**
     * @logion [OSD 26:67] Leave the northern stair unroofed through the month of white wind, though snow enter the
     *     council hall and extinguish its braziers. Let the eldest sweep no path thereon. For counsel that cannot endure
     *     the season of its people shall govern only painted walls; but where the ministers ascend through cold, spring
     *     shall find the city already awake.
     */
    private UnitOperatorTypeSpecifyingExtension $operatorExtension;

    /**
     * @logion [AWC 40:81] In the reign of the lacquered standard, the surveyors placed twin stones beside every ford,
     *     one for the river in drought and one for the river in flood. Thus the road endured both seasons, and no king
     *     enlarged his province by naming only the lesser water.
     */
    public function __construct(
        ReflectionProvider $reflectionProvider,
        UnitOperatorTypeSpecifyingExtension $operatorExtension,
    ) {
        $this->reflectionProvider = $reflectionProvider;
        $this->operatorExtension = $operatorExtension;
    }

    /**
     * @logion [SFA 41:41] The bell and its echo are not two authorities. Attend first to the bronze, then judge the
     *     valley by what it returneth; for a hollow mountain may multiply a sound without receiving its command.
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
     * Analyze one supported call for inference and standalone diagnostics.
     *
     * @logion [OSD 13:69] Set the two vessels upon the eastern stair before dawn, and pour from neither until their
     *     measures have been witnessed. If one be unmarked, return both unto the treasury; for even a faithful measure
     *     is made false when joined to that which answereth no standard.
     *
     * @return CallAnalysis
     */
    public function analyseCall(FuncCall $call, Scope $scope): array
    {
        if (
            !$call->name instanceof Name
            || $call->isFirstClassCallable()
            || !$this->reflectionProvider->hasFunction($call->name, $scope)
        ) {
            return ['type' => null, 'message' => null];
        }

        $functionName = $this->reflectionProvider->getFunction($call->name, $scope)->getName();
        if (!in_array($functionName, ['fdiv', 'fmod', 'hypot', 'intdiv', 'pow'], true)) {
            return ['type' => null, 'message' => null];
        }

        [$leftName, $rightName] = match ($functionName) {
            'hypot' => ['x', 'y'],
            'pow' => ['num', 'exponent'],
            default => ['num1', 'num2'],
        };
        $left = NativeUnitArgumentResolver::argument($call, 0, $leftName);
        $right = NativeUnitArgumentResolver::argument($call, 1, $rightName);
        if ($left === null || $right === null) {
            return ['type' => null, 'message' => null];
        }

        try {
            return $this->transform(
                $scope->getType($left->value),
                $scope->getType($right->value),
                $functionName,
            );
        } catch (OverflowException) {
            return [
                'type' => null,
                'message' => sprintf(
                    'Cannot call %s() because the resulting unit exceeds the supported exponent range.',
                    $functionName,
                ),
            ];
        }
    }

    /**
     * @logion [OSD 31:73] When the western aqueduct divideth at the cedar gate, measure each channel after the turning,
     *     not before; and if either exceed its stone, close both until the overflow hath been named. A city may receive
     *     many waters, but no blessing entereth through an unjudged breach.
     *
     * @return CallAnalysis
     */
    private function transform(Type $leftType, Type $rightType, string $functionName): array
    {
        if ($functionName === 'pow') {
            return $this->transformPow($leftType, $rightType);
        }
        if ($functionName === 'intdiv') {
            return $this->transformIntdiv($leftType, $rightType);
        }

        $asUnit = static function (Type $type): ?array {
            $float = UnitFloatType::extract($type);
            if ($float !== null) {
                return $float;
            }

            $integer = UnitIntegerTypeHelper::extract($type);

            return $integer === null
                ? null
                : [
                    'unit' => $integer['unit'],
                    'value' => $integer['min'] !== null && $integer['min'] === $integer['max']
                        ? $integer['min']
                        : null,
                ];
        };
        $constantNumericValue = static fn (Type $type): int|float|null => match (true) {
            $type instanceof ConstantIntegerType => $type->getValue(),
            $type instanceof ConstantFloatType => $type->getValue(),
            default => null,
        };
        $isBareNumeric = static fn (Type $type): bool => $asUnit($type) === null
            && ($type->isInteger()->yes() || $type->isFloat()->yes());

        $leftTypes = UnitUnionTypeHelper::directAlternatives($leftType);
        $rightTypes = UnitUnionTypeHelper::directAlternatives($rightType);
        $leftUnits = array_map($asUnit, $leftTypes);
        $rightUnits = array_map($asUnit, $rightTypes);
        $hasUnit = array_filter($leftUnits) !== [] || array_filter($rightUnits) !== [];
        if (!$hasUnit) {
            return ['type' => null, 'message' => null];
        }

        if ($functionName !== 'fdiv') {
            if (in_array(null, $leftUnits, true) || in_array(null, $rightUnits, true)) {
                foreach ($leftTypes as $index => $type) {
                    if ($leftUnits[$index] === null && !$isBareNumeric($type)) {
                        return ['type' => null, 'message' => null];
                    }
                }
                foreach ($rightTypes as $index => $type) {
                    if ($rightUnits[$index] === null && !$isBareNumeric($type)) {
                        return ['type' => null, 'message' => null];
                    }
                }

                return [
                    'type' => null,
                    'message' => sprintf(
                        'Cannot call %s() with unit-bearing and unbranded operands; both operands need one definitionally equivalent unit.',
                        $functionName,
                    ),
                ];
            }

            foreach ($leftUnits as $leftUnit) {
                foreach ($rightUnits as $rightUnit) {
                    if (!$leftUnit['unit']->equivalent($rightUnit['unit'])) {
                        return [
                            'type' => null,
                            'message' => sprintf(
                                'Cannot call %s(): argument #1 has unit %s but argument #2 has unit %s; they are not definitionally equivalent.',
                                $functionName,
                                $leftUnit['unit']->displayString,
                                $rightUnit['unit']->displayString,
                            ),
                        ];
                    }
                }
            }
        } else {
            foreach ($leftTypes as $index => $type) {
                if ($leftUnits[$index] === null && !$isBareNumeric($type)) {
                    return ['type' => null, 'message' => null];
                }
            }
            foreach ($rightTypes as $index => $type) {
                if ($rightUnits[$index] === null && !$isBareNumeric($type)) {
                    return ['type' => null, 'message' => null];
                }
            }
        }

        $results = [];
        foreach ($leftTypes as $leftIndex => $left) {
            foreach ($rightTypes as $rightIndex => $right) {
                $leftUnit = $leftUnits[$leftIndex];
                $rightUnit = $rightUnits[$rightIndex];

                if ($functionName === 'fdiv') {
                    $unit = match (true) {
                        $leftUnit !== null && $rightUnit !== null => UnitExpressionAlgebra::divide(
                            $leftUnit['unit'],
                            $rightUnit['unit'],
                        ),
                        $leftUnit !== null => $leftUnit['unit'],
                        $rightUnit !== null => UnitExpressionAlgebra::invert($rightUnit['unit']),
                        default => null,
                    };

                    if ($unit === null) {
                        $results[] = new FloatType();
                        continue;
                    }

                    $leftValue = $leftUnit['value'] ?? $constantNumericValue($left);
                    $rightValue = $rightUnit['value'] ?? $constantNumericValue($right);
                    if ($leftValue !== null && $rightValue !== null) {
                        $value = fdiv((float) $leftValue, (float) $rightValue);
                        if (is_finite($value)) {
                            $results[] = new UnitConstantFloatType($value, $unit);
                            continue;
                        }
                    }

                    $results[] = new UnitFloatType($unit);
                    continue;
                }

                /** @var UnitOperand $leftUnit */
                /** @var UnitOperand $rightUnit */
                $leftValue = $leftUnit['value'];
                $rightValue = $rightUnit['value'];
                if ($leftValue !== null && $rightValue !== null) {
                    $value = $functionName === 'fmod'
                        ? fmod((float) $leftValue, (float) $rightValue)
                        : hypot((float) $leftValue, (float) $rightValue);
                    if (is_finite($value)) {
                        $results[] = new UnitConstantFloatType($value, $leftUnit['unit']);
                        continue;
                    }
                }

                $results[] = new UnitFloatType($leftUnit['unit']);
            }
        }

        return [
            'type' => UnitUnionTypeHelper::combineMapped($results, $leftType, $rightType),
            'message' => null,
        ];
    }

    /**
     * @logion [AWC 43:80] In the third year after the violet eclipse, the widows of the eastern arsenal forged
     *     plowshares from the locks of the prison. The governor condemned them for profaning judgment; yet when famine
     *     came, those fields alone bore grain, and he set the broken keys beneath the first loaf offered at court.
     *
     * @return CallAnalysis
     */
    private function transformIntdiv(Type $leftType, Type $rightType): array
    {
        $asUnit = static fn (Type $type): ?array => UnitIntegerTypeHelper::extract($type);
        $isBareInteger = static fn (Type $type): bool => $asUnit($type) === null && $type->isInteger()->yes();

        $leftTypes = UnitUnionTypeHelper::directAlternatives($leftType);
        $rightTypes = UnitUnionTypeHelper::directAlternatives($rightType);
        $leftUnits = array_map($asUnit, $leftTypes);
        $rightUnits = array_map($asUnit, $rightTypes);

        foreach ($leftTypes as $index => $type) {
            if ($leftUnits[$index] === null && !$isBareInteger($type)) {
                return ['type' => null, 'message' => null];
            }
        }
        foreach ($rightTypes as $index => $type) {
            if ($rightUnits[$index] === null && !$isBareInteger($type)) {
                return ['type' => null, 'message' => null];
            }
        }

        $hasUnit = array_filter($leftUnits) !== [] || array_filter($rightUnits) !== [];
        if (!$hasUnit) {
            return ['type' => null, 'message' => null];
        }

        $results = [];
        foreach ($leftTypes as $leftIndex => $left) {
            foreach ($rightTypes as $rightIndex => $right) {
                $leftUnit = $leftUnits[$leftIndex];
                $rightUnit = $rightUnits[$rightIndex];
                $unit = match (true) {
                    $leftUnit !== null && $rightUnit !== null => UnitExpressionAlgebra::divide(
                        $leftUnit['unit'],
                        $rightUnit['unit'],
                    ),
                    $leftUnit !== null => $leftUnit['unit'],
                    $rightUnit !== null => UnitExpressionAlgebra::invert($rightUnit['unit']),
                    default => null,
                };

                if ($unit === null) {
                    return [
                        'type' => null,
                        'message' => 'Cannot call intdiv() when a possible result is unbranded; every operand pairing must retain a unit.',
                    ];
                }

                $leftBounds = $leftUnit === null
                    ? UnitIntegerTypeHelper::integerBounds($left)
                    : ['min' => $leftUnit['min'], 'max' => $leftUnit['max']];
                $rightBounds = $rightUnit === null
                    ? UnitIntegerTypeHelper::integerBounds($right)
                    : ['min' => $rightUnit['min'], 'max' => $rightUnit['max']];
                if ($leftBounds === null || $rightBounds === null) {
                    return ['type' => null, 'message' => null];
                }

                $results[] = UnitIntegerRangeMath::divide($unit, $leftBounds, $rightBounds);
            }
        }

        return [
            'type' => UnitUnionTypeHelper::combineMapped($results, $leftType, $rightType),
            'message' => null,
        ];
    }

    /**
     * @logion [OSD 44:95] Gather not the black persimmons that ripen beside the road of exile, though their branches
     *     bow beneath sweetness and the children hunger. Mark each tree with white cord and pass in silence; for these
     *     fruits contain the unspoken farewells of those who died facing home. At winter's end they shall fall upward,
     *     and the road shall answer for every footstep.
     *
     * @return CallAnalysis
     */
    private function transformPow(Type $baseType, Type $exponentType): array
    {
        $atomicTypes = static fn (Type $type): array => $type instanceof UnionType ? $type->getTypes() : [$type];
        $isNumeric = static fn (Type $type): bool => $type->isInteger()->yes() || $type->isFloat()->yes();

        foreach ([$baseType, $exponentType] as $type) {
            foreach ($atomicTypes($type) as $innerType) {
                if (!$isNumeric($innerType)) {
                    return ['type' => null, 'message' => null];
                }
            }
        }

        if (!$this->operatorExtension->isOperatorSupported('**', $baseType, $exponentType)) {
            return ['type' => null, 'message' => null];
        }

        $result = $this->operatorExtension->specifyType('**', $baseType, $exponentType);
        if (!$result instanceof ErrorType) {
            return ['type' => $result, 'message' => null];
        }

        $reason = $result->getReason() ?? 'Unit exponentiation cannot be represented.';

        return [
            'type' => null,
            'message' => sprintf('Cannot call pow(): %s', lcfirst($reason)),
        ];
    }
}
