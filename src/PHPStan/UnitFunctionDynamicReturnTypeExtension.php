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

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

/**
 * Infers unit_int / unit_float from unit($value, $unit) when the complete unit string type is finite.
 * @internal
 */
final class UnitFunctionDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{
    private const FUNCTION_NAME = 'jbboehr\\Yumemi\\unit';

    public function __construct(
        private readonly UnitExpressionParser $parser,
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
     * calls, an {@see ErrorType} for invalid expressions, or the inferred branded type for accepted
     * and ambiguous alternatives. The rule surfaces the accompanying issue and message as diagnostics.
     */
    public function inferType(FuncCall $functionCall, Scope $scope): ?Type
    {
        return $this->analyseCall($functionCall, $scope)['type'];
    }

    /**
     * Analyze one unit() call for inference and standalone diagnostics.
     *
     * @logion [AWC 26:91] After the court condemned the island astronomers for naming an unseen star, their households
     *     departed beneath one red sail. For forty winters the capital observed no solstice, though snow lay upon the
     *     bronze calendar; then the red sail appeared among the constellations, and the court’s longest night began.
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
        $unitArgument = NativeUnitArgumentResolver::argument($functionCall, 1, 'unit');
        if ($valueArgument === null || $unitArgument === null) {
            return ['type' => null, 'issue' => null, 'message' => null];
        }

        $unitType = $scope->getType($unitArgument->value);
        if (!$unitType->isString()->yes()) {
            return ['type' => null, 'issue' => null, 'message' => null];
        }

        $constantStrings = NativeUnitArgumentResolver::constantStrings($unitType);
        if ($constantStrings === null) {
            return [
                'type' => null,
                'issue' => 'dynamic',
                'message' => 'unit() requires a statically known unit expression; the unit argument does not resolve to a finite set of constant strings.',
            ];
        }

        $units = [];
        foreach ($constantStrings as $constantString) {
            $parsed = $this->parser->parse($constantString);
            if (!$parsed->isOk()) {
                $message = $parsed->errorMessage() ?? 'Invalid unit expression.';

                return [
                    'type' => new ErrorType($message),
                    'issue' => 'invalid',
                    'message' => $message,
                ];
            }

            $unit = $parsed->expression();
            foreach ($units as $existing) {
                if ($existing->equivalent($unit)) {
                    continue 2;
                }
            }

            $units[] = $unit;
        }

        $valueType = $scope->getType($valueArgument->value);

        // Prefer int branding when the magnitude is definitely an integer (not a float).
        if ($valueType->isInteger()->yes() && !$valueType->isFloat()->yes()) {
            $type = TypeCombinator::union(...array_map(
                static fn (UnitExpression $unit): Type => UnitIntegerTypeHelper::brand($valueType, $unit),
                $units,
            ));
        } else {
            $type = TypeCombinator::union(...array_map(
                static fn (UnitExpression $unit): Type => UnitFloatType::brand($valueType, $unit),
                $units,
            ));
        }

        if (count($units) === 1) {
            return ['type' => $type, 'issue' => null, 'message' => null];
        }

        $displayStrings = array_map(
            static fn (UnitExpression $unit): string => $unit->displayString,
            $units,
        );
        sort($displayStrings, SORT_STRING);

        return [
            'type' => $type,
            'issue' => 'ambiguous',
            'message' => 'unit() unit expression resolves to multiple units after normalization: '
                . implode(', ', $displayStrings)
                . '.',
        ];
    }
}
