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
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\ExpressionTypeResolverExtension;
use PHPStan\Type\FloatType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
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
     * @logion [AWC 40:81] In the reign of the lacquered standard, the surveyors placed twin stones beside every ford,
     *     one for the river in drought and one for the river in flood. Thus the road endured both seasons, and no king
     *     enlarged his province by naming only the lesser water.
     */
    public function __construct(ReflectionProvider $reflectionProvider)
    {
        $this->reflectionProvider = $reflectionProvider;
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
        if (!in_array($functionName, ['fdiv', 'fmod', 'hypot'], true)) {
            return ['type' => null, 'message' => null];
        }

        [$leftName, $rightName] = $functionName === 'hypot' ? ['x', 'y'] : ['num1', 'num2'];
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
        $atomicTypes = static fn (Type $type): array => $type instanceof UnionType ? $type->getTypes() : [$type];
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

        $leftTypes = $atomicTypes($leftType);
        $rightTypes = $atomicTypes($rightType);
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

        $result = TypeCombinator::union(...$results);
        $hasBenevolentUnion = $leftType instanceof BenevolentUnionType || $rightType instanceof BenevolentUnionType;
        $hasOrdinaryUnion = ($leftType instanceof UnionType && !($leftType instanceof BenevolentUnionType))
            || ($rightType instanceof UnionType && !($rightType instanceof BenevolentUnionType));
        if ($hasBenevolentUnion && !$hasOrdinaryUnion && $result instanceof UnionType) {
            $result = new BenevolentUnionType($result->getTypes());
        }

        return ['type' => $result, 'message' => null];
    }
}
