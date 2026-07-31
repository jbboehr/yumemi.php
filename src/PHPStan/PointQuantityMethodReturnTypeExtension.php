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

/**
 * Propagates coordinate and delta identities through PointQuantity methods.
 *
 * @logion [OSD 69:87] The static judge distinguished translation from subtraction,
 *     preserving stations as points and returning intervals to ordinary measure.
 */
final class PointQuantityMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    /**
     * @logion [OSD 30:96] The parser of coordinate seals remained beside the judge,
     *     ready to examine every literal scale named by a method.
     */
    private readonly UnitExpressionParser $parser;

    /**
     * @logion [OSD 90:41] The examiner received the common interpreter of units,
     *     that runtime and static courts might render one judgment.
     */
    public function __construct(UnitExpressionParser $parser)
    {
        $this->parser = $parser;
    }

    /**
     * @logion [OSD 20:73] The extension named the house of coordinate points
     *     over which its judgments held authority.
     */
    public function getClass(): string
    {
        return PointQuantity::class;
    }

    /**
     * @logion [OSD 57:91] Only translation, difference, conversion, extraction,
     *     and comparison were admitted before the point's static tribunal.
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
     * @logion [OSD 40:18] The method call entered the tribunal with its receiver
     *     and scope, and the inferred seal was returned when judgment was possible.
     */
    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): ?Type {
        return $this->inferType($methodReflection->getName(), $methodCall, $scope);
    }

    /**
     * Shared inference entry point used by the dynamic extension and diagnostic rule.
     *
     * @logion [OSD 88:63] Every supported act was dispatched according to whether
     *     it moved a station, compared it, renamed it, or measured an interval.
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
            'exactDecimalValueIn',
            'floatValueIn',
        ], true)) {
            if (!$receiver instanceof PointQuantityType && !$this->isUnbrandedPointQuantity($receiver)) {
                return null;
            }

            return $this->convert(
                $receiver instanceof PointQuantityType ? $receiver : null,
                $args,
                $scope,
                $methodName,
            );
        }

        if (!$receiver instanceof PointQuantityType) {
            return null;
        }

        return match ($methodName) {
            'add', 'sub' => $this->translate($receiver, $args, $scope, $methodName),
            'difference' => $this->difference($receiver, $args, $scope),
            'compareTo',
            'equals',
            'lessThan',
            'lessThanOrEqualTo',
            'greaterThan',
            'greaterThanOrEqualTo' => $this->compare($receiver, $args, $scope, $methodName),
            default => null,
        };
    }

    /**
     * @param array<\PhpParser\Node\Arg> $args
     *
     * @logion [OSD 17:85] The station crossed into each named coordinate tongue,
     *     provided every target shared the same hidden axis.
     */
    private function convert(
        ?PointQuantityType $receiver,
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

        if ($receiver !== null) {
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
     * @logion [OSD 73:46] A multiplicative interval translated the station only
     *     when its hidden axis agreed with the coordinate's own.
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
        if (!$other instanceof QuantityType) {
            return $receiver;
        }

        $pointUnit = $receiver->getPointUnitExpression();
        $deltaUnit = $other->getUnitExpression();
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

        return $receiver;
    }

    /**
     * @param array<\PhpParser\Node\Arg> $args
     *
     * @logion [OSD 11:49] Subtraction reconciled the two stations and returned
     *     the left coordinate's unshifted rod as the surviving measure.
     */
    private function difference(PointQuantityType $receiver, array $args, Scope $scope): ?Type
    {
        if (count($args) < 1) {
            return null;
        }

        $other = $scope->getType($args[0]->value);
        if ($other instanceof PointQuantityType) {
            $left = $receiver->getPointUnitExpression();
            $right = $other->getPointUnitExpression();
            if (!$left->sameDimension($right)) {
                return self::incompatiblePointError('difference', $left, $right);
            }
        }

        return new QuantityType($receiver->getPointUnitExpression()->deltaUnit);
    }

    /**
     * @param array<\PhpParser\Node\Arg> $args
     *
     * @logion [OSD 95:52] Comparison admitted two stations only after their hidden
     *     axes agreed, leaving the native judgment otherwise unchanged.
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
        if (!$other instanceof PointQuantityType) {
            return null;
        }

        $left = $receiver->getPointUnitExpression();
        $right = $other->getPointUnitExpression();

        return $left->sameDimension($right)
            ? null
            : self::incompatiblePointError($methodName, $left, $right);
    }

    /**
     * @logion [OSD 50:68] The examiner recognized an unsealed point of the proper
     *     house, though its coordinate identity could no longer guide inference.
     */
    private function isUnbrandedPointQuantity(Type $type): bool
    {
        return !$type instanceof PointQuantityType
            && (new ObjectType(PointQuantity::class))->isSuperTypeOf($type)->yes();
    }

    /**
     * @logion [OSD 22:31] Incompatible axes were named together with their stations,
     *     that the fracture in translation might be plainly judged.
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
