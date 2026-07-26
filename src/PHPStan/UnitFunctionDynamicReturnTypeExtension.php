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

/**
 * Infers unit_int / unit_float from unit($value, 'meter') when the unit string is constant.
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
        return $this->inferType($functionCall, $scope);
    }

    /**
     * Shared inference used by both the return-type extension and {@see InvalidUnitCallRule}.
     *
     * Returns null when the call is not statically analysable (non-constant unit string,
     * too few arguments), an {@see ErrorType} carrying a reason for an invalid unit string,
     * or the branded unit type otherwise.
     */
    public function inferType(FuncCall $functionCall, Scope $scope): ?Type
    {
        $args = $functionCall->getArgs();
        if (count($args) < 2) {
            return null;
        }

        $unitType = $scope->getType($args[1]->value);
        $constantStrings = $unitType->getConstantStrings();
        if (count($constantStrings) !== 1) {
            return null;
        }

        $parsed = $this->parser->parse($constantStrings[0]->getValue());
        if (!$parsed->isOk()) {
            return new ErrorType($parsed->errorMessage() ?? 'Invalid unit expression.');
        }

        $unit = $parsed->expression();
        $valueType = $scope->getType($args[0]->value);

        // Prefer int branding when the magnitude is definitely an integer (not a float).
        if ($valueType->isInteger()->yes() && !$valueType->isFloat()->yes()) {
            return new UnitIntegerType($unit);
        }

        return new UnitFloatType($unit);
    }
}
