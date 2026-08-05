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

use jbboehr\Yumemi\Exception\UnitNotFoundException;
use jbboehr\Yumemi\Exception\UnsupportedSyntaxException;
use jbboehr\Yumemi\Exception\UnsupportedUnitConversionException;
use jbboehr\Yumemi\Parser\ParseException;
use jbboehr\Yumemi\Units;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\ErrorType;
use PHPStan\Type\FloatType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;

/**
 * Validates constant unit_to() calls and infers unit_float<'to'> for multiplicative targets.
 * Affine targets remain plain float because the branded unit model is multiplicative.
 * @internal
 */
final class UnitToFunctionDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{
    private const FUNCTION_NAME = 'jbboehr\\Yumemi\\unit_to';

    public function __construct(
        private readonly UnitExpressionParser $parser,
        private readonly Units $units,
    ) {
    }

    public function isFunctionSupported(FunctionReflection $functionReflection): bool
    {
        return $functionReflection->getName() === self::FUNCTION_NAME;
    }

    public function getTypeFromFunctionCall(
        FunctionReflection $functionReflection,
        FuncCall $functionCall,
        Scope $scope,
    ): ?Type {
        try {
            return $this->inferType($functionCall, $scope);
        } catch (\Throwable $exception) {
            ShouldNotHappenException::rethrow($exception);
        }
    }

    /**
     * Shared inference used by both the return-type extension and {@see InvalidUnitCallRule}.
     *
     * Returns only the type component of {@see self::analyseCall()}: null for dynamic or incomplete
     * calls, an {@see ErrorType} for invalid conversions, or the inferred multiplicative brand or
     * native float for accepted and ambiguous targets. The rule surfaces the accompanying issue and
     * message as diagnostics.
     */
    public function inferType(FuncCall $functionCall, Scope $scope): ?Type
    {
        return $this->analyseCall($functionCall, $scope)['type'];
    }

    /**
     * Analyze one unit_to() call for inference and standalone diagnostics.
     *
     * @logion [RAS 44:62] I beheld many roads descend from the measures into one radiant city,
     *     yet where the gates opened upon divided destinations the native vessel received no single name.
     *
     * @return array{
     *     type: Type|null,
     *     issue: 'invalid'|'dynamic'|'ambiguous'|null,
     *     message: string|null,
     * }
     */
    public function analyseCall(FuncCall $functionCall, Scope $scope): array
    {
        $valueArgument = NativeUnitArgumentResolver::argument($functionCall, 0, 'value');
        $fromArgument = NativeUnitArgumentResolver::argument($functionCall, 1, 'from');
        $toArgument = NativeUnitArgumentResolver::argument($functionCall, 2, 'to');
        if ($valueArgument === null || $fromArgument === null || $toArgument === null) {
            return ['type' => null, 'issue' => null, 'message' => null];
        }

        $fromType = $scope->getType($fromArgument->value);
        $toType = $scope->getType($toArgument->value);
        if (!$fromType->isString()->yes() || !$toType->isString()->yes()) {
            return ['type' => null, 'issue' => null, 'message' => null];
        }

        $fromStrings = NativeUnitArgumentResolver::constantStrings($fromType);
        if ($fromStrings === null) {
            return [
                'type' => null,
                'issue' => 'dynamic',
                'message' => 'unit_to() requires a statically known source unit expression; the source argument does not resolve to a finite set of constant strings.',
            ];
        }

        $toStrings = NativeUnitArgumentResolver::constantStrings($toType);
        if ($toStrings === null) {
            return [
                'type' => null,
                'issue' => 'dynamic',
                'message' => 'unit_to() requires a statically known target unit expression; the target argument does not resolve to a finite set of constant strings.',
            ];
        }

        $fromUnits = [];
        foreach ($fromStrings as $fromString) {
            $fromResult = $this->parser->parse($fromString);
            $fromUnits[$fromString] = $fromResult->isOk() ? $fromResult->expression() : null;
        }

        $toUnits = [];
        $toPoints = [];
        foreach ($toStrings as $toString) {
            $toResult = $this->parser->parse($toString);
            $toUnits[$toString] = $toResult->isOk() ? $toResult->expression() : null;
            if (!$toResult->isOk()) {
                $pointResult = $this->parser->parsePoint($toString);
                $toPoints[$toString] = $pointResult->isOk() ? $pointResult->expression() : null;
            }
        }

        foreach ($fromStrings as $fromString) {
            foreach ($toStrings as $toString) {
                try {
                    $compatible = $this->units->areCompatible($fromString, $toString);
                } catch (
                    UnitNotFoundException
                    | UnsupportedSyntaxException
                    | UnsupportedUnitConversionException
                    | ParseException
                    | \InvalidArgumentException $exception
                ) {
                    $message = $exception->getMessage();

                    return [
                        'type' => new ErrorType($message),
                        'issue' => 'invalid',
                        'message' => $message,
                    ];
                }

                if (!$compatible) {
                    $message = sprintf(
                        'Cannot convert with unit_to(): units %s and %s are not dimensionally compatible.',
                        isset($fromUnits[$fromString]) ? $fromUnits[$fromString]->displayString : $fromString,
                        isset($toUnits[$toString]) ? $toUnits[$toString]->displayString : $toString,
                    );

                    return [
                        'type' => new ErrorType($message),
                        'issue' => 'invalid',
                        'message' => $message,
                    ];
                }
            }
        }

        foreach ($this->unitTypes($scope->getType($valueArgument->value)) as $valueUnit) {
            foreach ($fromStrings as $fromString) {
                $fromUnit = $fromUnits[$fromString];
                if ($fromUnit === null) {
                    $message = sprintf(
                        'unit_to() cannot use a unit-branded value with affine from unit %s.',
                        $fromString,
                    );

                    return [
                        'type' => new ErrorType($message),
                        'issue' => 'invalid',
                        'message' => $message,
                    ];
                }

                if (!$valueUnit->equivalent($fromUnit)) {
                    $message = sprintf(
                        "unit_to() value unit %s does not match from unit %s (normalized forms differ).",
                        $valueUnit->displayString,
                        $fromUnit->displayString,
                    );

                    return [
                        'type' => new ErrorType($message),
                        'issue' => 'invalid',
                        'message' => $message,
                    ];
                }
            }
        }

        $resultTypes = [];
        foreach ($toStrings as $toString) {
            $toUnit = $toUnits[$toString];
            $resultTypes[] = $toUnit === null ? new FloatType() : new UnitFloatType($toUnit);
        }

        $type = TypeCombinator::union(...$resultTypes);
        $multiplicativeTargets = [];
        $pointTargets = [];
        foreach ($toStrings as $toString) {
            $toUnit = $toUnits[$toString];
            if ($toUnit !== null) {
                foreach ($multiplicativeTargets as $existing) {
                    if ($existing->equivalent($toUnit)) {
                        continue 2;
                    }
                }

                $multiplicativeTargets[] = $toUnit;
                continue;
            }

            $toPoint = $toPoints[$toString] ?? null;
            if ($toPoint === null) {
                // Successful conversion validation above should make this unreachable.
                $message = 'Cannot determine the semantic target of unit_to(): ' . $toString . '.';

                return [
                    'type' => new ErrorType($message),
                    'issue' => 'invalid',
                    'message' => $message,
                ];
            }

            foreach ($pointTargets as $existing) {
                if ($existing->equivalent($toPoint)) {
                    continue 2;
                }
            }

            $pointTargets[] = $toPoint;
        }

        if (count($multiplicativeTargets) + count($pointTargets) === 1) {
            return ['type' => $type, 'issue' => null, 'message' => null];
        }

        $displayStrings = [
            ...array_map(
                static fn (UnitExpression $unit): string => $unit->displayString,
                $multiplicativeTargets,
            ),
            ...array_map(
                static fn (PointUnitExpression $unit): string => $unit->displayString,
                $pointTargets,
            ),
        ];
        sort($displayStrings, SORT_STRING);

        return [
            'type' => $type,
            'issue' => 'ambiguous',
            'message' => 'unit_to() target unit expression resolves to multiple units after normalization: '
                . implode(', ', $displayStrings)
                . '.',
        ];
    }

    /**
     * @return list<UnitExpression>
     *
     * @logion [OSD 97:77] Every native seal within the divided magnitude was
     *     opened before its declared source, and no branded witness escaped comparison.
     */
    private function unitTypes(Type $type): array
    {
        $types = $type instanceof UnionType ? $type->getTypes() : [$type];
        $units = [];
        foreach ($types as $innerType) {
            if ($innerType instanceof UnitFloatType) {
                $units[] = $innerType->getUnitExpression();
                continue;
            }

            $integer = UnitIntegerTypeHelper::extract($innerType);
            if ($integer !== null) {
                $units[] = $integer['unit'];
            }
        }

        usort(
            $units,
            static fn (UnitExpression $left, UnitExpression $right): int => $left->displayString <=> $right->displayString,
        );

        return $units;
    }
}
