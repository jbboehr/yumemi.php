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

use jbboehr\Yumemi\Analyzer\NormalizedExpr;
use jbboehr\Yumemi\Formatter\ExprRenderer;
use jbboehr\Yumemi\Quantity;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\ErrorType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

/**
 * Propagates the unit through the fluent {@see Quantity} method chain.
 *
 * When the receiver is a branded {@see QuantityType}, each unit-bearing method returns a new
 * QuantityType whose unit matches the runtime result (see the table in {@see Quantity}):
 * `mul`/`div` combine units via {@see UnitExpressionAlgebra}; `pow` raises by a constant integer;
 * `neg` keeps the left unit; `add`/`sub` accept dimensionally compatible units and keep the left
 * unit; `addWithSameUnit`/`subWithSameUnit` additionally require normalized-equivalent units; comparison
 * methods require compatible dimensions while retaining their native int/bool return types; `to` rebrands
 * to each possible statically known target unit; `normalize` rebrands to the catalog-normalized form; and
 * `simplify` moves the normalized scale into the magnitude, leaving the normalized unit factors on the type.
 *
 * An explicit finite target also brands results from an unbranded {@see Quantity}; without a source
 * brand, only the target can be inferred and source compatibility cannot be checked. The configured
 * registry is authoritative for constant targets. Genuinely dynamic targets and unit-combining
 * operations whose units cannot be determined fall back to the native return.
 */
final class QuantityMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    public function __construct(
        private readonly UnitExpressionParser $parser,
    ) {
    }

    public function getClass(): string
    {
        return Quantity::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return in_array($methodReflection->getName(), [
            'mul', 'div', 'pow', 'neg', 'add', 'sub', 'addWithSameUnit', 'subWithSameUnit', 'to', 'valueIn',
            'intValueIn', 'exactIntValueIn', 'decimalValueIn', 'exactDecimalValueIn', 'floatValueIn', 'normalize',
            'simplify', 'compareTo', 'equals', 'lessThan', 'lessThanOrEqual', 'greaterThan', 'greaterThanOrEqual',
        ], true);
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): ?Type {
        return $this->inferType($methodReflection->getName(), $methodCall, $scope);
    }

    /**
     * Shared inference and validation entry point used by the dynamic return extension and its
     * standalone diagnostic rule.
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
            if (!$receiver instanceof QuantityType && !$this->isUnbrandedQuantity($receiver)) {
                return null;
            }

            return $this->convert(
                $receiver instanceof QuantityType ? $receiver : null,
                $args,
                $scope,
                $methodName,
            );
        }

        if (!$receiver instanceof QuantityType) {
            return null;
        }

        $unit = $receiver->getUnitExpression();

        return match ($methodName) {
            'neg' => $receiver,
            'add', 'sub' => $this->addSub($receiver, $args, $scope, false, $methodName),
            'addWithSameUnit', 'subWithSameUnit' => $this->addSub($receiver, $args, $scope, true, $methodName),
            'compareTo', 'equals', 'lessThan', 'lessThanOrEqual', 'greaterThan', 'greaterThanOrEqual' => $this->compare(
                $receiver,
                $args,
                $scope,
                $methodName,
            ),
            'mul' => $this->combine($unit, $args, $scope, true),
            'div' => $this->combine($unit, $args, $scope, false),
            'pow' => $this->power($unit, $args, $scope),
            'normalize' => $this->normalize($unit),
            'simplify' => $this->simplify($unit),
            default => null,
        };
    }

    /**
     * @param array<\PhpParser\Node\Arg> $args
     */
    private function addSub(
        QuantityType $receiver,
        array $args,
        Scope $scope,
        bool $requireSameUnit,
        string $methodName,
    ): ?Type {
        if (count($args) < 1) {
            return null;
        }

        $other = $scope->getType($args[0]->value);
        if (!$other instanceof QuantityType) {
            // The runtime result keeps the receiver's unit when the call succeeds, but an
            // unbranded operand does not carry enough information for a compatibility check.
            return $receiver;
        }

        $leftUnit = $receiver->getUnitExpression();
        $rightUnit = $other->getUnitExpression();
        $compatible = $requireSameUnit
            ? $leftUnit->equivalent($rightUnit)
            : $leftUnit->sameDimension($rightUnit);

        if ($compatible) {
            return $receiver;
        }

        if ($requireSameUnit) {
            return new ErrorType(sprintf(
                'Cannot call Quantity::%s() with units %s and %s; the method requires normalized-equivalent units.',
                $methodName,
                $leftUnit->displayString,
                $rightUnit->displayString,
            ));
        }

        return new ErrorType(sprintf(
            'Cannot call Quantity::%s() with dimensionally incompatible units %s (%s) and %s (%s).',
            $methodName,
            $leftUnit->displayString,
            $leftUnit->dimension->toString(),
            $rightUnit->displayString,
            $rightUnit->dimension->toString(),
        ));
    }

    /**
     * @param array<\PhpParser\Node\Arg> $args
     */
    private function combine(UnitExpression $unit, array $args, Scope $scope, bool $multiply): ?Type
    {
        if (count($args) < 1) {
            return null;
        }

        $argType = $scope->getType($args[0]->value);

        if ($argType instanceof QuantityType) {
            $other = $argType->getUnitExpression();
            $result = $multiply
                ? UnitExpressionAlgebra::multiply($unit, $other)
                : UnitExpressionAlgebra::divide($unit, $other);

            return new QuantityType($result);
        }

        // An unbranded Quantity operand carries no static unit — cannot compute the result.
        if ($this->isUnbrandedQuantity($argType)) {
            return null;
        }

        // Bare int / Rational scalar: magnitude changes, unit is preserved.
        return new QuantityType($unit);
    }

    /**
     * @param array<\PhpParser\Node\Arg> $args
     */
    private function compare(
        QuantityType $receiver,
        array $args,
        Scope $scope,
        string $methodName,
    ): ?Type {
        if (count($args) < 1) {
            return null;
        }

        $other = $scope->getType($args[0]->value);
        if (!$other instanceof QuantityType) {
            return null;
        }

        $leftUnit = $receiver->getUnitExpression();
        $rightUnit = $other->getUnitExpression();
        if ($leftUnit->sameDimension($rightUnit)) {
            return null;
        }

        return new ErrorType(sprintf(
            'Cannot call Quantity::%s() with dimensionally incompatible units %s (%s) and %s (%s).',
            $methodName,
            $leftUnit->displayString,
            $leftUnit->dimension->toString(),
            $rightUnit->displayString,
            $rightUnit->dimension->toString(),
        ));
    }

    /**
     * @param array<\PhpParser\Node\Arg> $args
     */
    private function power(UnitExpression $unit, array $args, Scope $scope): ?Type
    {
        if (count($args) < 1) {
            return null;
        }

        $argType = $scope->getType($args[0]->value);
        if (!$argType instanceof ConstantIntegerType) {
            return null;
        }

        return new QuantityType(UnitExpressionAlgebra::power($unit, $argType->getValue()));
    }

    /**
     * @param array<\PhpParser\Node\Arg> $args
     */
    private function convert(
        ?QuantityType $receiver,
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

        $targetUnits = [];
        foreach ($constantStrings as $constantString) {
            $parsed = $this->parser->parse($constantString->getValue());
            if (!$parsed->isOk()) {
                return new ErrorType($parsed->errorMessage() ?? 'Invalid unit expression.');
            }

            $targetUnits[] = $parsed->expression();
        }

        if ($receiver instanceof QuantityType) {
            $sourceUnit = $receiver->getUnitExpression();
            foreach ($targetUnits as $targetUnit) {
                if ($sourceUnit->sameDimension($targetUnit)) {
                    continue;
                }

                return new ErrorType(sprintf(
                    'Cannot call Quantity::%s() with dimensionally incompatible units %s (%s) and %s (%s).',
                    $methodName,
                    $sourceUnit->displayString,
                    $sourceUnit->dimension->toString(),
                    $targetUnit->displayString,
                    $targetUnit->dimension->toString(),
                ));
            }
        }

        return match ($methodName) {
            'to' => TypeCombinator::union(...array_map(
                static fn (UnitExpression $targetUnit): QuantityType => new QuantityType($targetUnit),
                $targetUnits,
            )),
            'intValueIn', 'exactIntValueIn' => TypeCombinator::union(...array_map(
                static fn (UnitExpression $targetUnit): UnitIntegerType => new UnitIntegerType($targetUnit),
                $targetUnits,
            )),
            'floatValueIn' => TypeCombinator::union(...array_map(
                static fn (UnitExpression $targetUnit): UnitFloatType => new UnitFloatType($targetUnit),
                $targetUnits,
            )),
            // Rational and decimal-string extractions retain their native returns after validation.
            default => null,
        };
    }

    private function normalize(UnitExpression $unit): QuantityType
    {
        // The catalog-normalized form is already carried on the UnitExpression; rebrand to it.
        // Dimension is preserved by normalization, and the normalized expr is its own normal form.
        $normalized = $unit->normalizedExpr;

        return new QuantityType(new UnitExpression(
            $normalized,
            ExprRenderer::format($normalized),
            $unit->dimension,
            $normalized,
        ));
    }

    private function simplify(UnitExpression $unit): QuantityType
    {
        // Runtime Quantity::simplify() folds the normalized constant into the magnitude, so the
        // static unit contains only the remaining normalized factors.
        $simplified = NormalizedExpr::withoutConstant($unit->normalizedExpr);

        return new QuantityType(new UnitExpression(
            $simplified,
            ExprRenderer::format($simplified),
            $unit->dimension,
            $simplified,
        ));
    }

    private function isUnbrandedQuantity(Type $type): bool
    {
        return !$type instanceof QuantityType
            && (new ObjectType(Quantity::class))->isSuperTypeOf($type)->yes();
    }
}
