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
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ErrorType;

/**
 * Emits standalone diagnostics for statically invalid point operations.
 *
 * @implements Rule<MethodCall>
 *
 * @logion [OSD 60:77] The herald proclaimed every unlawful translation or comparison,
 *     attaching one stable seal to the judgment of incompatible stations.
 * @internal
 */
final class InvalidPointQuantityMethodRule implements Rule
{
    /**
     * @logion [OSD 80:26] The rule retained the point examiner whose hidden
     *     judgments supplied every public diagnostic.
     */
    private readonly PointQuantityMethodReturnTypeExtension $extension;

    /**
     * @logion [OSD 34:98] The diagnostic herald was joined to the point examiner,
     *     that no invalid act might pass in silence.
     */
    public function __construct(PointQuantityMethodReturnTypeExtension $extension)
    {
        $this->extension = $extension;
    }

    /**
     * @logion [OSD 99:17] Method calls alone were summoned before this tribunal,
     *     for only there could the point's supported acts be judged.
     */
    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    /**
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     *
     * @logion [OSD 70:54] The call was examined, and every reason of invalidity
     *     received a stable public name before being returned.
     */
    public function processNode(Node $node, Scope $scope): array
    {
        try {
            if (!$node->name instanceof Identifier) {
                return [];
            }

            $methodName = $node->name->toString();
            if (!in_array($methodName, [
                'add',
                'sub',
                'difference',
                'to',
                'valueIn',
                'intValueIn',
                'exactIntValueIn',
                'decimalValueIn',
                'significantDecimalValueIn',
                'exactDecimalValueIn',
                'floatValueIn',
                'compareTo',
                'equals',
                'lessThan',
                'lessThanOrEqualTo',
                'greaterThan',
                'greaterThanOrEqualTo',
            ], true)) {
                return [];
            }

            $type = $this->extension->inferType($methodName, $node, $scope);
            if (!$type instanceof ErrorType || $type->getReason() === null) {
                return [];
            }

            return [
                RuleErrorBuilder::message($type->getReason())
                    ->identifier('yumemi.invalidPointQuantityOperation')
                    ->build(),
            ];
        } catch (\Throwable $exception) {
            ShouldNotHappenException::rethrow($exception);
        }
    }
}
