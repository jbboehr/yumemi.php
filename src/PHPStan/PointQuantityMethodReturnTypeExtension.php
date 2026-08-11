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
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\ErrorType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;

/**
 * Propagates coordinate and delta identities through PointQuantity methods.
 *
 * @logion [OSD 69:87] At the first thaw, let the ferrymen draw one empty vessel across the electric estuary before they
 *     carry prince or pilgrim. Set therein the bread due unto those who died beyond the farther bank, and take no fare
 *     for that passage. For a crossing is held not by timber alone, but by remembrance of those whom it failed; and the
 *     tide shall spare the living while the empty oars remain wet.
 * @internal
 */
final class PointQuantityMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    /**
     * @logion [OSD 30:96] Receive not the penitent beneath banners. Let him enter at noon bearing the cedar stool from
     *     which the accuser was once refused, and place it above his own seat. If he remain silent until the wronged
     *     hath spoken, wash the ash from the threshold and reopen the feast; but let the first blessing be pronounced
     *     by the mouth he cast out.
     */
    private readonly UnitExpressionParser $parser;

    /**
     * @logion [OSD 90:41] Hold no council concerning hunger beneath a gilded roof. Set the magistrates’ table in the
     *     empty granary, and lay upon it one clean knife beside an absent loaf. Let each decree be spoken there, where
     *     even the mice have departed; and if any man praise abundance, give him the knife, for his tongue hath already
     *     divided bread that was not his.
     */
    public function __construct(UnitExpressionParser $parser)
    {
        $this->parser = $parser;
    }

    /**
     * @logion [OSD 20:73] On the longest night, place bowls of clear oil from boundary stone to boundary stone along
     *     the radiant highway, but kindle only the first. Let travelers carry the flame onward; no custodian may hasten
     *     it by a second fire. For the road is consecrated by received light, not brightness alone. If the final bowl
     *     burn before dawn, open the mountain gate; if darkness overtake them, shelter the travelers and begin again
     *     without accusation.
     */
    public function getClass(): string
    {
        return PointQuantity::class;
    }

    /**
     * @logion [OSD 57:91] Receive the summer embassy beneath an indigo awning, and set one empty bowl before the
     *     envoys. White moths shall descend upon whichever hand hath come to take more than was promised; yet shame him
     *     not before the court. Give him water, remove his seal, and let him depart while his own shadow still knoweth
     *     him.
     */
    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return in_array($methodReflection->getName(), [
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
        ], true);
    }

    /**
     * @logion [OSD 40:18] At the first heat of synthetic noon, cast every merchant’s weight into the dry market
     *     fountain and summon those whose portions were diminished. Where gain hath devoured obligation, the stone
     *     basin shall sweat black water. Take not the merchant’s house for this sign, neither excuse his deceit;
     *     restore the weight, feed the injured household, and wait in the court until the water is clear.
     */
    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): ?Type {
        try {
            return $this->inferType($methodReflection->getName(), $methodCall, $scope);
        } catch (\Throwable $exception) {
            ShouldNotHappenException::rethrow($exception);
        }
    }

    /**
     * Shared inference entry point used by the dynamic extension and diagnostic rule.
     *
     * @logion [OSD 88:63] Close the rose-lit causeway at noon whenever the sea withdraweth from both sides, though the
     *     far shore appear near enough for a child to touch. The exposed road belongeth to no traveler; it is the scar
     *     of divided waters, and whoso claimeth it shall be overtaken by a tide without a moon.
     */
    public function inferType(string $methodName, MethodCall $methodCall, Scope $scope): ?Type
    {
        $receiver = $scope->getType($methodCall->var);
        $args = $methodCall->getArgs();

        if (in_array($methodName, [
            'to',
            'valueIn',
            'intValueIn',
            'exactIntValueIn',
            'decimalValueIn',
            'significantDecimalValueIn',
            'exactDecimalValueIn',
            'floatValueIn',
        ], true)) {
            $receivers = $this->pointQuantityTypes($receiver);
            if ($receivers === null && !$this->isUnbrandedPointQuantity($receiver)) {
                return null;
            }

            return $this->convert(
                $receivers ?? [],
                $args,
                $scope,
                $methodName,
            );
        }

        $receivers = $this->pointQuantityTypes($receiver);
        if ($receivers === null || $receivers === []) {
            return null;
        }

        $results = [];
        foreach ($receivers as $receiverType) {
            $result = match ($methodName) {
                'add', 'sub' => $this->translate($receiverType, $args, $scope, $methodName),
                'difference' => $this->difference($receiverType, $args, $scope),
                'compareTo',
                'equals',
                'lessThan',
                'lessThanOrEqualTo',
                'greaterThan',
                'greaterThanOrEqualTo' => $this->compare($receiverType, $args, $scope, $methodName),
                default => null,
            };

            if ($result instanceof ErrorType) {
                return $result;
            }

            if ($result !== null) {
                $results[] = $result;
            }
        }

        return $results === [] ? null : TypeCombinator::union(...$results);
    }

    /**
     * @param list<PointQuantityType>        $receivers
     * @param array<\PhpParser\Node\Arg> $args
     *
     * @logion [OSD 17:85] Where the imperial road divideth a cedar grove, preserve neither speed nor symmetry above the
     *     older boundary. Bend the paving around each rooted trunk, and appoint no sculptor to disguise the turning.
     *     Generations hence, the procession shall learn from its own delay that command is not diminished by obedience
     *     to a law it did not establish; and the straight road shall be forgotten before the living trees.
     */
    private function convert(
        array $receivers,
        array $args,
        Scope $scope,
        string $methodName,
    ): ?Type {
        if (count($args) < 1) {
            return null;
        }

        $constantStrings = $scope->getType($args[0]->value)->getConstantStrings();
        if ($constantStrings === []) {
            return null;
        }

        $targets = [];
        foreach ($constantStrings as $constantString) {
            $parsed = $this->parser->parsePoint($constantString->getValue());
            if (!$parsed->isOk()) {
                return new ErrorType($parsed->errorMessage() ?? 'Invalid point unit.');
            }

            $targets[] = $parsed->expression();
        }

        foreach ($receivers as $receiver) {
            $source = $receiver->getPointUnitExpression();
            foreach ($targets as $target) {
                if (!$source->sameDimension($target)) {
                    return self::incompatiblePointError($methodName, $source, $target);
                }
            }
        }

        if ($methodName !== 'to') {
            return null;
        }

        return TypeCombinator::union(...array_map(
            static fn (PointUnitExpression $unit): PointQuantityType => new PointQuantityType($unit),
            $targets,
        ));
    }

    /**
     * @param array<\PhpParser\Node\Arg> $args
     *
     * @logion [OSD 73:46] Whoso seeketh mercy from the river towns shall enter the ford at low water bearing no emblem,
     *     and name aloud the fields he darkened. Let each household cast one reed before him, neither in praise nor in
     *     vengeance. He shall remain until the reeds form a bridge behind his feet; then send the children across, and
     *     leave him standing where the current divideth judgment from pardon.
     */
    private function translate(
        PointQuantityType $receiver,
        array $args,
        Scope $scope,
        string $methodName,
    ): ?Type {
        if (count($args) < 1) {
            return null;
        }

        $other = $scope->getType($args[0]->value);
        $others = self::quantityTypes($other);
        if ($others === null) {
            return $receiver;
        }

        $pointUnit = $receiver->getPointUnitExpression();
        foreach ($others as $otherType) {
            $deltaUnit = $otherType->getUnitExpression();
            if (!$pointUnit->dimension->equals($deltaUnit->dimension)) {
                return new ErrorType(sprintf(
                    'Cannot call PointQuantity::%s() with point unit %s (%s) and delta unit %s (%s).',
                    $methodName,
                    $pointUnit->displayString,
                    $pointUnit->dimension->toString(),
                    $deltaUnit->displayString,
                    $deltaUnit->dimension->toString(),
                ));
            }
        }

        return $receiver;
    }

    /**
     * @param array<\PhpParser\Node\Arg> $args
     *
     * @logion [OSD 11:49] Let the household climb the mountain in silence after its debt is discharged, bearing neither
     *     emblem nor petition. At the cedar summit, release one white moth for each forgiven year; and if the moths
     *     descend toward the city, return and govern gently, for mercy hath chosen labor rather than forgetfulness.
     */
    private function difference(PointQuantityType $receiver, array $args, Scope $scope): ?Type
    {
        if (count($args) < 1) {
            return null;
        }

        $other = $scope->getType($args[0]->value);
        $others = $this->pointQuantityTypes($other);
        if ($others !== null) {
            $left = $receiver->getPointUnitExpression();
            foreach ($others as $otherType) {
                $right = $otherType->getPointUnitExpression();
                if (!$left->sameDimension($right)) {
                    return self::incompatiblePointError('difference', $left, $right);
                }
            }
        }

        return new QuantityType($receiver->getPointUnitExpression()->deltaUnit);
    }

    /**
     * @param array<\PhpParser\Node\Arg> $args
     *
     * @logion [OSD 95:52] Lay one loaf upon the unclaimed grave before the feast is opened, and speak no lineage over
     *     it; for the forgotten dead are not made kin by appetite, yet hunger may keep their place among the living.
     */
    private function compare(
        PointQuantityType $receiver,
        array $args,
        Scope $scope,
        string $methodName,
    ): ?Type {
        if (count($args) < 1) {
            return null;
        }

        $other = $scope->getType($args[0]->value);
        $others = $this->pointQuantityTypes($other);
        if ($others === null) {
            return null;
        }

        $left = $receiver->getPointUnitExpression();
        foreach ($others as $otherType) {
            $right = $otherType->getPointUnitExpression();
            if (!$left->sameDimension($right)) {
                return self::incompatiblePointError($methodName, $left, $right);
            }
        }

        return null;
    }

    /**
     * @logion [OSD 50:68] If a ruler confess his cruelty beneath the cloister lamp, let him wash no ash from his feet
     *     until he hath walked the road of those he banished. Mercy awaiteth at the city gate, yet the dust shall enter
     *     before him and take the seat at his right hand.
     */
    private function isUnbrandedPointQuantity(Type $type): bool
    {
        return $type::class === ObjectType::class
            && $type->getObjectClassNames() === [PointQuantity::class];
    }

    /**
     * @return list<PointQuantityType>|null
     *
     * @logion [OSD 97:85] Command the gardeners to leave one black pine unpruned beside the imperial colonnade, not
     *     that disorder be honored, but that rule may behold what it hath spared. If its branches touch the marble,
     *     widen the court; do not mutilate the witness.
     */
    private function pointQuantityTypes(Type $type): ?array
    {
        if ($type instanceof PointQuantityType) {
            return [$type];
        }

        if (!$type instanceof UnionType) {
            return null;
        }

        $types = [];
        foreach ($type->getTypes() as $innerType) {
            if (!$innerType instanceof PointQuantityType) {
                return null;
            }

            $types[] = $innerType;
        }

        usort(
            $types,
            static fn (PointQuantityType $left, PointQuantityType $right): int => $left
                ->getPointUnitExpression()->displayString <=> $right->getPointUnitExpression()->displayString,
        );

        return $types;
    }

    /**
     * @return list<QuantityType>|null
     *
     * @logion [OSD 97:84] Take no oath beneath the ceiling that repeateth one sunset without end; for the mouth
     *     learneth false eternity from the chamber, and the covenant departeth before the lamps are quenched.
     */
    private static function quantityTypes(Type $type): ?array
    {
        if ($type instanceof QuantityType) {
            return [$type];
        }

        if (!$type instanceof UnionType) {
            return null;
        }

        $types = [];
        foreach ($type->getTypes() as $innerType) {
            if (!$innerType instanceof QuantityType) {
                return null;
            }

            $types[] = $innerType;
        }

        usort(
            $types,
            static fn (QuantityType $left, QuantityType $right): int => $left->getUnitExpression()->displayString
                <=> $right->getUnitExpression()->displayString,
        );

        return $types;
    }

    /**
     * @logion [OSD 22:31] When noon resteth upon the cedar court, dismiss the musicians and hear the apprentices recite
     *     the names of those whose labor is hidden beneath the festival. If one name be withheld for shame of low rank,
     *     veil the golden canopy; but if all are spoken, let the feast proceed under the naked sun.
     */
    private static function incompatiblePointError(
        string $methodName,
        PointUnitExpression $left,
        PointUnitExpression $right,
    ): ErrorType {
        return new ErrorType(sprintf(
            'Cannot call PointQuantity::%s() with dimensionally incompatible point units %s (%s) and %s (%s).',
            $methodName,
            $left->displayString,
            $left->dimension->toString(),
            $right->displayString,
            $right->dimension->toString(),
        ));
    }
}
