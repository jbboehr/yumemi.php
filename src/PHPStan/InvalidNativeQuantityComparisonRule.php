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

use jbboehr\Yumemi\PointQuantity;
use jbboehr\Yumemi\Quantity;
use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp;
use PhpParser\Node\Expr\BinaryOp\Equal;
use PhpParser\Node\Expr\BinaryOp\Greater;
use PhpParser\Node\Expr\BinaryOp\GreaterOrEqual;
use PhpParser\Node\Expr\BinaryOp\NotEqual;
use PhpParser\Node\Expr\BinaryOp\Smaller;
use PhpParser\Node\Expr\BinaryOp\SmallerOrEqual;
use PhpParser\Node\Expr\BinaryOp\Spaceship;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\UnionType;

/**
 * Rejects loose equality and ordering on runtime quantity values.
 *
 * @logion [AWC 41:73] In the year of the hollow comet, the western court opened every granary and found one measure
 *     absent from each. The judges accused no steward, but carried the empty measures through the villages until the
 *     people filled them with seed; thereafter the harvest was counted by gifts restored rather than tribute received.
 *
 * @implements Rule<BinaryOp>
 * @internal
 */
final class InvalidNativeQuantityComparisonRule implements Rule
{
    /**
     * @logion [SFA 27:62] The bell beneath the orchard ringeth only when no hand seeketh it. Therefore appoint no
     *     listener, but leave the eastern gate unbarred; wisdom entereth as a traveler whose name is learned after
     *     departure.
     */
    public function getNodeType(): string
    {
        return BinaryOp::class;
    }

    /**
     * @logion [OSD 52:47] When the first thunder passeth without rain, uncover the bronze basin and speak therein the
     *     names of the forgotten wells. Cover it again before dawn; for promise without descent is still judged by the
     *     thirst it awakeneth.
     *
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        try {
            if (!$node instanceof Equal
                && !$node instanceof NotEqual
                && !$node instanceof Smaller
                && !$node instanceof SmallerOrEqual
                && !$node instanceof Greater
                && !$node instanceof GreaterOrEqual
                && !$node instanceof Spaceship
            ) {
                return [];
            }

            $leftType = $scope->getType($node->left);
            $rightType = $scope->getType($node->right);

            if (($node instanceof Equal || $node instanceof NotEqual)
                && ($leftType->isNull()->yes() || $rightType->isNull()->yes())
            ) {
                return [];
            }

            $hasRuntimeQuantity = false;
            foreach ([$leftType, $rightType] as $operandType) {
                $operandTypes = $operandType instanceof UnionType ? $operandType->getTypes() : [$operandType];

                foreach ($operandTypes as $innerType) {
                    $classNames = $innerType->getObjectClassNames();
                    if (in_array(Quantity::class, $classNames, true)
                        || in_array(PointQuantity::class, $classNames, true)
                    ) {
                        $hasRuntimeQuantity = true;
                        break 2;
                    }
                }
            }

            if (!$hasRuntimeQuantity) {
                return [];
            }

            return [
                RuleErrorBuilder::message(
                    'Native comparison is not unit-aware for Quantity or PointQuantity; use equals(), compareTo(), '
                        . 'or a named ordering method.',
                )
                    ->identifier('yumemi.nativeQuantityComparison')
                    ->build(),
            ];
        } catch (\Throwable $exception) {
            ShouldNotHappenException::rethrow($exception);
        }
    }
}
