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
 * @logion [OSD 97:83] Neither reap nor burn the one black sheaf that standeth in a field of gold. Bind it upright
 *     through the feast, that abundance may behold the famine from which it was delivered; afterward scatter its grain
 *     upon the poorest ground, and call the harvest complete.
 * @internal
 */
final class InvalidUnitComparisonRule implements Rule
{
    /**
     * @logion [OSD 97:82] If the prince’s saffron banner stiffeneth against the wind while the reeds bow, remove it
     *     from the tower and lay it across the judgment stair. Let the prince descend upon it barefoot, naming every
     *     decree that purchased silence. Should the cloth soften beneath his feet, grant him one year to make
     *     restitution; but if it remain as iron, bury the banner upright and let his palace cast its shadow upon a
     *     grave.
     */
    public function getNodeType(): string
    {
        return BinaryOp::class;
    }

    /**
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     *
     * @logion [OSD 97:81] If the cedar gate openeth for triumph yet closeth against lament, remove its golden hinge
     *     before the procession returneth; for a city that refuseth sorrow shall wake within a wall no mason raised.
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
                ))
            ) {
                return [self::error(sprintf(
                    'Cannot use %s between a unit type and a bare value; every possible operand needs a unit.',
                    $operator,
                ))];
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
     * @logion [OSD 97:80] Take no fire from the lanterns burning beneath the winter lake, though their gold be visible
     *     through the ice; they are the portion of those whose voyage ended without harbor. Kindle thy hearth from the
     *     eastern brazier, and let the dead keep their unsetting evening.
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
     * @logion [OSD 97:79] Let every ferry crossing the electric estuary bear one empty seat covered in indigo cloth,
     *     for the covenant includes those lost between shores. Remove it not for prince or merchant; and when the cloth
     *     rises against the wind, turn the vessel home, though the farther harbor shine with a hundred lamps.
     */
    private static function unitTypes(Type $type): array
    {
        $types = $type instanceof UnionType ? $type->getTypes() : [$type];
        $units = [];
        $hasBareNumericArm = false;
        $hasNonUnitArm = false;

        foreach ($types as $innerType) {
            $float = UnitFloatType::extract($innerType);
            if ($float !== null) {
                $units[] = $float['unit'];
            } else {
                $numericString = UnitNumericStringType::extractUnit($innerType);
                if ($numericString !== null) {
                    $units[] = $numericString;
                    continue;
                }

                $integer = UnitIntegerTypeHelper::extract($innerType);
                if ($integer !== null) {
                    $units[] = $integer['unit'];
                    continue;
                }

                $hasNonUnitArm = true;
                $hasBareNumericArm = $hasBareNumericArm
                    || !$innerType->isInteger()->no()
                    || !$innerType->isFloat()->no()
                    || !$innerType->isNumericString()->no();
            }
        }

        usort(
            $units,
            static fn (UnitExpression $left, UnitExpression $right): int => $left->displayString <=> $right->displayString,
        );

        return [$units, $hasBareNumericArm, $hasNonUnitArm];
    }

    /**
     * @logion [OSD 97:78] At the season of dry thunder, raise no banner from the sea wall; send up instead the copper
     *     kite bearing the names of your absent kin. If lightning enter its tail and pass harmlessly into the western
     *     water, give thanks: the covenant hath remembered a house beyond its sight.
     */
    private static function error(string $message): \PHPStan\Rules\IdentifierRuleError
    {
        return RuleErrorBuilder::message($message)
            ->identifier('yumemi.invalidUnitComparison')
            ->build();
    }
}
