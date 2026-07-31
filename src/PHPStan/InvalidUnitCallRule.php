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

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ErrorType;

/**
 * Emits standalone diagnostics for invalid unit() / unit_factor() / unit_to() calls.
 *
 * The dynamic return type extensions infer an {@see ErrorType} for invalid unit strings,
 * dimensional mismatches, and value/from mismatches, but PHPStan does not report a call
 * just because its return type is an error. This rule surfaces the same reason as an actual
 * diagnostic so mistakes are caught even when the result is never used in a strict context.
 *
 * @implements Rule<FuncCall>
 */
final class InvalidUnitCallRule implements Rule
{
    private const UNIT = 'jbboehr\\Yumemi\\unit';
    private const UNIT_FACTOR = 'jbboehr\\Yumemi\\unit_factor';
    private const UNIT_TO = 'jbboehr\\Yumemi\\unit_to';

    public function __construct(
        private readonly UnitFunctionDynamicReturnTypeExtension $unitExtension,
        private readonly UnitFactorFunctionDynamicReturnTypeExtension $unitFactorExtension,
        private readonly UnitToFunctionDynamicReturnTypeExtension $unitToExtension,
    ) {
    }

    public function getNodeType(): string
    {
        return FuncCall::class;
    }

    /**
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        try {
            if (!$node->name instanceof Name) {
                return [];
            }

            $type = match ($scope->resolveName($node->name)) {
                self::UNIT => $this->unitExtension->inferType($node, $scope),
                self::UNIT_FACTOR => $this->unitFactorExtension->inferType($node, $scope),
                self::UNIT_TO => $this->unitToExtension->inferType($node, $scope),
                default => null,
            };

            if (!$type instanceof ErrorType) {
                return [];
            }

            $reason = $type->getReason();
            if ($reason === null) {
                return [];
            }

            return [
                RuleErrorBuilder::message($reason)
                    ->identifier('yumemi.invalidUnitCall')
                    ->build(),
            ];
        } catch (\Throwable $exception) {
            ShouldNotHappenException::rethrow($exception);
        }
    }
}
