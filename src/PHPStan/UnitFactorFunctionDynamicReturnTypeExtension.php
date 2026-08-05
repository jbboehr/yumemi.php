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

use jbboehr\Yumemi\Exception\IncompatibleUnitException;
use jbboehr\Yumemi\Exception\NonMultiplicativeConversionException;
use jbboehr\Yumemi\Units;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

/**
 * Validates constant unit_factor() calls and infers unit_float<'to / from'>.
 * @internal
 */
final class UnitFactorFunctionDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{
    private const FUNCTION_NAME = 'jbboehr\\Yumemi\\unit_factor';

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
     * calls, an {@see ErrorType} for invalid conversions, or the inferred factor brand for accepted
     * and ambiguous alternatives. The rule surfaces the accompanying issue and message as diagnostics.
     */
    public function inferType(FuncCall $functionCall, Scope $scope): ?Type
    {
        return $this->analyseCall($functionCall, $scope)['type'];
    }

    /**
     * Analyze one unit_factor() call for inference and standalone diagnostics.
     *
     * @logion [OSD 59:34] Every road between the measures was tried before the appointed quotient,
     *     and only one proportion was permitted to pass beneath the native seal.
     *
     * @return array{
     *     type: Type|null,
     *     issue: 'invalid'|'dynamic'|'ambiguous'|null,
     *     message: string|null,
     * }
     */
    public function analyseCall(FuncCall $functionCall, Scope $scope): array
    {
        $fromArgument = NativeUnitArgumentResolver::argument($functionCall, 0, 'from');
        $toArgument = NativeUnitArgumentResolver::argument($functionCall, 1, 'to');
        if ($fromArgument === null || $toArgument === null) {
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
                'message' => 'unit_factor() requires a statically known source unit expression; the source argument does not resolve to a finite set of constant strings.',
            ];
        }

        $toStrings = NativeUnitArgumentResolver::constantStrings($toType);
        if ($toStrings === null) {
            return [
                'type' => null,
                'issue' => 'dynamic',
                'message' => 'unit_factor() requires a statically known target unit expression; the target argument does not resolve to a finite set of constant strings.',
            ];
        }

        $fromUnits = [];
        foreach ($fromStrings as $fromString) {
            $fromResult = $this->parser->parse($fromString);
            if (!$fromResult->isOk()) {
                $message = $fromResult->errorMessage() ?? 'Invalid source unit expression.';

                return [
                    'type' => new ErrorType($message),
                    'issue' => 'invalid',
                    'message' => $message,
                ];
            }

            $fromUnits[] = $fromResult->expression();
        }

        $toUnits = [];
        foreach ($toStrings as $toString) {
            $toResult = $this->parser->parse($toString);
            if (!$toResult->isOk()) {
                $message = $toResult->errorMessage() ?? 'Invalid target unit expression.';

                return [
                    'type' => new ErrorType($message),
                    'issue' => 'invalid',
                    'message' => $message,
                ];
            }

            $toUnits[] = $toResult->expression();
        }

        $resultUnits = [];
        foreach ($fromUnits as $fromUnit) {
            foreach ($toUnits as $toUnit) {
                try {
                    $this->units->conversionFactor($fromUnit->expr, $toUnit->expr);
                } catch (IncompatibleUnitException|NonMultiplicativeConversionException $exception) {
                    $message = 'Cannot calculate unit_factor(): ' . $exception->getMessage();

                    return [
                        'type' => new ErrorType($message),
                        'issue' => 'invalid',
                        'message' => $message,
                    ];
                }

                $resultUnit = UnitExpressionAlgebra::divide($toUnit, $fromUnit);
                foreach ($resultUnits as $existing) {
                    if ($existing->equivalent($resultUnit)) {
                        continue 2;
                    }
                }

                $resultUnits[] = $resultUnit;
            }
        }

        $type = TypeCombinator::union(...array_map(
            static fn (UnitExpression $unit): UnitFloatType => new UnitFloatType($unit),
            $resultUnits,
        ));

        if (count($resultUnits) === 1) {
            return ['type' => $type, 'issue' => null, 'message' => null];
        }

        $displayStrings = array_map(
            static fn (UnitExpression $unit): string => $unit->displayString,
            $resultUnits,
        );
        sort($displayStrings, SORT_STRING);

        return [
            'type' => $type,
            'issue' => 'ambiguous',
            'message' => 'unit_factor() resolves to multiple conversion-factor units after normalization: '
                . implode(', ', $displayStrings)
                . '.',
        ];
    }
}
