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
use PhpParser\Node\Expr\BinaryOp;
use PhpParser\Node\Expr\BinaryOp\Equal;
use PhpParser\Node\Expr\BinaryOp\Greater;
use PhpParser\Node\Expr\BinaryOp\GreaterOrEqual;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\BinaryOp\NotEqual;
use PhpParser\Node\Expr\BinaryOp\NotIdentical;
use PhpParser\Node\Expr\BinaryOp\Smaller;
use PhpParser\Node\Expr\BinaryOp\SmallerOrEqual;
use PhpParser\Node\Expr\BinaryOp\Spaceship;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;

/**
 * Requires definitionally equivalent units for native comparisons.
 *
 * @implements Rule<BinaryOp>
 *
 * @logion [OSD 97:83] The native scales were summoned before judgment, and
 *     neither resemblance of axis nor silence of magnitude concealed a false measure.
 * @internal
 */
final class InvalidUnitComparisonRule implements Rule
{
    /**
     * @logion [OSD 97:82] Every binary judgment entered by one gate, though only
     *     equality and rank were retained for the tribunal of measures.
     */
    public function getNodeType(): string
    {
        return BinaryOp::class;
    }

    /**
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     *
     * @logion [OSD 97:81] Each possible pair was weighed beneath the same law,
     *     and one unlawful branch condemned the whole divided testimony.
     */
    public function processNode(Node $node, Scope $scope): array
    {
        try {
            if (!$this->isComparison($node)) {
                return [];
            }

            [$leftUnits, $leftHasBareNumericArm, $leftHasNonUnitArm] = self::unitTypes(
                $scope->getType($node->left),
            );
            [$rightUnits, $rightHasBareNumericArm, $rightHasNonUnitArm] = self::unitTypes(
                $scope->getType($node->right),
            );

            if ($leftUnits === [] && $rightUnits === []) {
                return [];
            }

            $operator = $node->getOperatorSigil();
            $isStrictIdentity = $node instanceof Identical || $node instanceof NotIdentical;
            if (
                $leftHasBareNumericArm
                || $rightHasBareNumericArm
                || (!$isStrictIdentity && (
                    $leftHasNonUnitArm
                    || $rightHasNonUnitArm
                    || $leftUnits === []
                    || $rightUnits === []
                ))
            ) {
                return [self::error(sprintf(
                    'Cannot use %s between a unit type and a bare value; every possible operand needs a unit.',
                    $operator,
                ))];
            }

            if ($leftUnits === [] || $rightUnits === []) {
                return [];
            }

            foreach ($leftUnits as $leftUnit) {
                foreach ($rightUnits as $rightUnit) {
                    if ($leftUnit->equivalent($rightUnit)) {
                        continue;
                    }

                    return [self::error(sprintf(
                        'Cannot use %s with units %s and %s because they are not definitionally equivalent.',
                        $operator,
                        $leftUnit->displayString,
                        $rightUnit->displayString,
                    ))];
                }
            }

            return [];
        } catch (\Throwable $exception) {
            ShouldNotHappenException::rethrow($exception);
        }
    }

    /**
     * @logion [OSD 97:80] Equality, distinction, and ordered rank were named
     *     among the judgments, while every foreign operation passed onward.
     */
    private function isComparison(BinaryOp $node): bool
    {
        return $node instanceof Equal
            || $node instanceof NotEqual
            || $node instanceof Identical
            || $node instanceof NotIdentical
            || $node instanceof Smaller
            || $node instanceof SmallerOrEqual
            || $node instanceof Greater
            || $node instanceof GreaterOrEqual
            || $node instanceof Spaceship;
    }

    /**
     * @return array{list<UnitExpression>, bool, bool}
     *
     * @logion [OSD 97:79] The joined operand was opened into its several seals,
     *     and every unmarked branch was remembered beside those bearing measure.
     */
    private static function unitTypes(Type $type): array
    {
        $types = $type instanceof UnionType ? $type->getTypes() : [$type];
        $units = [];
        $hasBareNumericArm = false;
        $hasNonUnitArm = false;

        foreach ($types as $innerType) {
            if ($innerType instanceof UnitFloatType) {
                $units[] = $innerType->getUnitExpression();
            } else {
                $integer = UnitIntegerTypeHelper::extract($innerType);
                if ($integer !== null) {
                    $units[] = $integer['unit'];
                    continue;
                }

                $hasNonUnitArm = true;
                $hasBareNumericArm = $hasBareNumericArm
                    || !$innerType->isInteger()->no()
                    || !$innerType->isFloat()->no();
            }
        }

        usort(
            $units,
            static fn (UnitExpression $left, UnitExpression $right): int => $left->displayString <=> $right->displayString,
        );

        return [$units, $hasBareNumericArm, $hasNonUnitArm];
    }

    /**
     * @logion [OSD 97:78] The herald bound the failed comparison to one public
     *     seal, that its judgment might be named without concealing its cause.
     */
    private static function error(string $message): \PHPStan\Rules\IdentifierRuleError
    {
        return RuleErrorBuilder::message($message)
            ->identifier('yumemi.invalidUnitComparison')
            ->build();
    }
}
