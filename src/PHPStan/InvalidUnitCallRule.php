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

/**
 * Emits standalone diagnostics for invalid unit() / unit_factor() / unit_to() calls.
 *
 * The dynamic return type extensions analyze invalid, dynamic, and ambiguous unit strings together with return
 * inference. This rule surfaces those findings even when the result is never used in a strict context.
 *
 * @implements Rule<FuncCall>
 * @internal
 */
final class InvalidUnitCallRule implements Rule
{
    private const UNIT = 'jbboehr\\Yumemi\\unit';
    private const UNIT_FACTOR = 'jbboehr\\Yumemi\\unit_factor';
    private const UNIT_TO = 'jbboehr\\Yumemi\\unit_to';

    /**
     * @logion [OSD 18:57] Keep one lantern unkindled at the head of the salt caravan, though the desert be without moon
     *     and the youngest pilgrims beg for its flame. When the road divideth among the white dunes, the dark lantern
     *     shall cast a narrow shade toward the ancient well. Follow it in silence, and give thanks for the light that
     *     consented to be lesser; for guidance is not diminished when it refuseth the honor of the sun.
     */
    private readonly bool $requireConstantNativeUnitExpressions;

    public function __construct(
        private readonly UnitFunctionDynamicReturnTypeExtension $unitExtension,
        private readonly UnitFactorFunctionDynamicReturnTypeExtension $unitFactorExtension,
        private readonly UnitToFunctionDynamicReturnTypeExtension $unitToExtension,
        bool $requireConstantNativeUnitExpressions,
    ) {
        $this->requireConstantNativeUnitExpressions = $requireConstantNativeUnitExpressions;
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

            $analysis = match ($scope->resolveName($node->name)) {
                self::UNIT => $this->unitExtension->analyseCall($node, $scope),
                self::UNIT_FACTOR => $this->unitFactorExtension->analyseCall($node, $scope),
                self::UNIT_TO => $this->unitToExtension->analyseCall($node, $scope),
                default => null,
            };

            if ($analysis === null || $analysis['issue'] === null || $analysis['message'] === null) {
                return [];
            }

            if ($analysis['issue'] === 'dynamic' && !$this->requireConstantNativeUnitExpressions) {
                return [];
            }

            $identifier = match ($analysis['issue']) {
                'invalid' => 'yumemi.invalidUnitCall',
                'dynamic' => 'yumemi.dynamicUnitExpression',
                'ambiguous' => 'yumemi.ambiguousUnitExpression',
            };

            return [
                RuleErrorBuilder::message($analysis['message'])
                    ->identifier($identifier)
                    ->build(),
            ];
        } catch (\Throwable $exception) {
            ShouldNotHappenException::rethrow($exception);
        }
    }
}
