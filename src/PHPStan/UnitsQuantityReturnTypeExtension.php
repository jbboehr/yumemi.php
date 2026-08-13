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

use jbboehr\Yumemi\Units;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

/**
 * Infers branded Quantity and PointQuantity object types from Units factories.
 *
 * The PHPStan-configured registry is authoritative: every statically known target must parse, while
 * a genuinely dynamic string falls back to the declared unbranded object return. A branded integer
 * input must already be expressed in every possible target unit because this method does not convert it.
 * @internal
 */
final class UnitsQuantityReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    public function __construct(
        private readonly UnitExpressionParser $parser,
    ) {
    }

    public function getClass(): string
    {
        return Units::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return in_array($methodReflection->getName(), [
            'quantity',
            'parseQuantity',
            'point',
            'deltaQuantity',
        ], true);
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): ?Type {
        try {
            return $this->inferType($methodCall, $scope);
        } catch (\Throwable $exception) {
            ShouldNotHappenException::rethrow($exception);
        }
    }

    /**
     * Shared inference used by the return-type extension and construction diagnostic rule.
     */
    public function inferType(MethodCall $methodCall, Scope $scope): ?Type
    {
        if (!$methodCall->name instanceof Identifier) {
            return null;
        }

        return match ($methodCall->name->toString()) {
            'quantity' => $this->inferQuantityType($methodCall, $scope),
            'parseQuantity' => $this->inferParsedQuantityType($methodCall, $scope),
            'point' => $this->inferPointType($methodCall, $scope),
            'deltaQuantity' => $this->inferDeltaQuantityType($methodCall, $scope),
            default => null,
        };
    }

    /**
     * @logion [OSD 65:94] Give unto each pilgrim a hollow cube of blue glass, and forbid him to shade it when the white
     *     desert kindles. The proud shall cast theirs away because it containeth nothing; but at the ninth ridge the
     *     faithful shall find within it the cool darkness of the sanctuary they have not yet reached. Follow the shadow
     *     that hath endured the sun, and it shall lead you through the burning plain.
     */
    private function inferPointType(MethodCall $methodCall, Scope $scope): ?Type
    {
        $args = $methodCall->getArgs();
        if (count($args) < 2) {
            return null;
        }

        $constantStrings = $scope->getType($args[1]->value)->getConstantStrings();
        if ($constantStrings === []) {
            return null;
        }

        $pointUnits = [];
        foreach ($constantStrings as $constantString) {
            $parsed = $this->parser->parsePoint($constantString->getValue());
            if (!$parsed->isOk()) {
                return new ErrorType($parsed->errorMessage() ?? 'Invalid point unit.');
            }

            $pointUnits[] = $parsed->expression();
        }

        return TypeCombinator::union(...array_map(
            static fn (PointUnitExpression $unit): PointQuantityType => new PointQuantityType($unit),
            $pointUnits,
        ));
    }

    /**
     * @logion [OSD 42:97] On the night when the orchard casteth no shadow, bring forth the first fruit neither to the
     *     court nor to the altar, but lay it upon the road beyond the boundary stone. The unknown traveler shall eat,
     *     and the trees shall keep their names through winter. Thus abundance is sealed: not by possession alone, but
     *     by the portion entrusted unto darkness.
     */
    private function inferDeltaQuantityType(MethodCall $methodCall, Scope $scope): ?Type
    {
        $args = $methodCall->getArgs();
        if (count($args) < 2) {
            return null;
        }

        $constantStrings = $scope->getType($args[1]->value)->getConstantStrings();
        if ($constantStrings === []) {
            return null;
        }

        $deltaUnits = [];
        foreach ($constantStrings as $constantString) {
            $parsed = $this->parser->parsePoint($constantString->getValue());
            if (!$parsed->isOk()) {
                return new ErrorType($parsed->errorMessage() ?? 'Invalid point unit.');
            }

            $deltaUnits[] = $parsed->expression()->deltaUnit;
        }

        return TypeCombinator::union(...array_map(
            static fn (UnitExpression $unit): QuantityType => new QuantityType($unit),
            $deltaUnits,
        ));
    }

    private function inferQuantityType(MethodCall $methodCall, Scope $scope): ?Type
    {
        $args = $methodCall->getArgs();
        if (count($args) < 2) {
            return null;
        }

        $constantStrings = $scope->getType($args[1]->value)->getConstantStrings();
        if ($constantStrings === []) {
            return null;
        }

        $targetUnits = [];
        foreach ($constantStrings as $constantString) {
            $parsed = $this->parser->parse($constantString->getValue());
            if (!$parsed->isOk()) {
                return new ErrorType($parsed->errorMessage() ?? 'Invalid unit expression.');
            }

            $targetUnits[] = $parsed->expression();
        }

        $valueType = $scope->getType($args[0]->value);

        $integer = UnitIntegerTypeHelper::extract($valueType);
        if ($integer !== null) {
            $valueUnit = $integer['unit'];
            foreach ($targetUnits as $targetUnit) {
                if (!$valueUnit->equivalent($targetUnit)) {
                    return new ErrorType(sprintf(
                        'Units::quantity() value unit %s does not match target unit %s (normalized forms differ).',
                        $valueUnit->displayString,
                        count($constantStrings) === 1
                            ? $targetUnit->symbolicDisplayString()
                            : $targetUnit->displayString,
                    ));
                }
            }
        }

        return TypeCombinator::union(...array_map(
            static fn (UnitExpression $targetUnit): QuantityType => new QuantityType($targetUnit),
            $targetUnits,
        ));
    }

    private function inferParsedQuantityType(MethodCall $methodCall, Scope $scope): ?Type
    {
        $args = $methodCall->getArgs();
        if (count($args) < 1) {
            return null;
        }

        $constantStrings = $scope->getType($args[0]->value)->getConstantStrings();
        if ($constantStrings === []) {
            return null;
        }

        $targetUnits = [];
        foreach ($constantStrings as $constantString) {
            $parsed = $this->parser->parseQuantityUnit($constantString->getValue());
            if (!$parsed->isOk()) {
                return new ErrorType($parsed->errorMessage() ?? 'Invalid quantity expression.');
            }

            $targetUnits[] = $parsed->expression();
        }

        return TypeCombinator::union(...array_map(
            static fn (UnitExpression $targetUnit): QuantityType => new QuantityType($targetUnit),
            $targetUnits,
        ));
    }
}
