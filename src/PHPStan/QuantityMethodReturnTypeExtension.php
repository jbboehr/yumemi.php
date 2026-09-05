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
use jbboehr\Yumemi\Exception\ExceptionInterface;
use jbboehr\Yumemi\Exception\UnsupportedUnitCompactionException;
use jbboehr\Yumemi\Expr\Unit;
use jbboehr\Yumemi\Number\Rational;
use jbboehr\Yumemi\Quantity;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\ErrorType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use jbboehr\Yumemi\Util\Exponent;

/**
 * Propagates the unit through the fluent {@see Quantity} method chain.
 *
 * When the receiver is a branded {@see QuantityType}, each unit-bearing method returns a new
 * QuantityType whose unit matches the runtime result (see the table in {@see Quantity}):
 * `mul`/`div` combine units via {@see UnitExpressionAlgebra}; `rdiv` inverts the receiver's unit; `pow` raises and
 * `root` extracts exact symbolic powers;
 * `abs`/`neg` keep the left unit; `add`/`sub` accept dimensionally compatible units and keep the left
 * unit; `addWithSameUnit`/`subWithSameUnit` additionally require normalized-equivalent units; ordering methods
 * require compatible dimensions, while `equals` narrows known incompatibility to `false`; `to` rebrands
 * to each possible statically known target unit; `toPreferred` and `toCompact` deliberately return an unbranded
 * Quantity because their targets depend on runtime application state; `normalize` rebrands to the catalog-normalized
 * form; and `simplify` moves the normalized scale into the magnitude, leaving the normalized unit factors on the type.
 *
 * An explicit finite target also brands results from an unbranded {@see Quantity}; without a source
 * brand, only the target can be inferred and source compatibility cannot be checked. The configured
 * registry is authoritative for constant targets. Genuinely dynamic targets and unit-combining
 * operations whose units cannot be determined fall back to the native return.
 * @internal
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
            'mul', 'div', 'rdiv', 'pow', 'root', 'abs', 'neg', 'add', 'sub', 'addWithSameUnit', 'subWithSameUnit', 'to',
            'toPreferred', 'toCompact', 'valueIn', 'intValueIn', 'exactIntValueIn', 'decimalValueIn',
            'significantDecimalValueIn', 'exactDecimalValueIn', 'floatValueIn', 'normalize',
            'simplify', 'compareTo', 'equals', 'lessThan', 'lessThanOrEqualTo', 'greaterThan', 'greaterThanOrEqualTo',
        ], true);
    }

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
     * Shared inference and validation entry point used by the dynamic return extension and its
     * standalone diagnostic rule.
     */
    public function inferType(string $methodName, MethodCall $methodCall, Scope $scope): ?Type
    {
        $receiver = $scope->getType($methodCall->var);
        $args = $methodCall->getArgs();

        if ($methodName === 'toPreferred') {
            return new ObjectType(Quantity::class);
        }

        if (in_array($methodName, [
            'to',
            'toCompact',
            'valueIn',
            'intValueIn',
            'exactIntValueIn',
            'decimalValueIn',
            'significantDecimalValueIn',
            'exactDecimalValueIn',
            'floatValueIn',
        ], true)) {
            $receivers = $this->quantityTypes($receiver);
            if ($receivers === null && !$this->isUnbrandedQuantity($receiver)) {
                return null;
            }

            return $this->convert(
                $receivers ?? [],
                $args,
                $scope,
                $methodName,
            );
        }

        $receivers = $this->quantityTypes($receiver);
        if ($receivers === null || $receivers === []) {
            return null;
        }

        $results = [];
        foreach ($receivers as $receiverType) {
            $result = $this->inferBrandedType($methodName, $receiverType, $args, $scope);
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
     * @param array<\PhpParser\Node\Arg> $args
     *
     * @logion [OSD 97:87] Give thanks when blue smoke descendeth from the amber chimney above the house of strangers,
     *     for the poor have been remembered in a chamber higher than kings. On that day bake no victory bread; carry
     *     the first loaves beneath covered vessels, and let their warmth be the only proclamation.
     */
    private function inferBrandedType(
        string $methodName,
        QuantityType $receiver,
        array $args,
        Scope $scope,
    ): ?Type {
        $unit = $receiver->getUnitExpression();

        return match ($methodName) {
            'abs', 'neg' => $receiver,
            'add', 'sub' => $this->addSub($receiver, $args, $scope, false, $methodName),
            'addWithSameUnit', 'subWithSameUnit' => $this->addSub($receiver, $args, $scope, true, $methodName),
            'compareTo', 'equals', 'lessThan', 'lessThanOrEqualTo', 'greaterThan', 'greaterThanOrEqualTo' => $this->compare(
                $receiver,
                $args,
                $scope,
                $methodName,
            ),
            'mul' => $this->combine($unit, $args, $scope, true),
            'div' => $this->combine($unit, $args, $scope, false),
            'rdiv' => new QuantityType(UnitExpressionAlgebra::power($unit, -1)),
            'pow' => $this->power($unit, $args, $scope),
            'root' => $this->root($unit, $args, $scope),
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
        $others = $this->quantityTypes($other);
        if ($others === null) {
            // The runtime result keeps the receiver's unit when the call succeeds, but an
            // unbranded operand does not carry enough information for a compatibility check.
            return $receiver;
        }

        $leftUnit = $receiver->getUnitExpression();
        foreach ($others as $otherType) {
            $rightUnit = $otherType->getUnitExpression();
            $compatible = $requireSameUnit
                ? $leftUnit->equivalent($rightUnit)
                : $leftUnit->sameDimension($rightUnit);

            if ($compatible) {
                continue;
            }

            if ($requireSameUnit) {
                return new ErrorType(sprintf(
                    'Cannot call Quantity::%s() with units %s and %s; the method requires normalized-equivalent units.',
                    $methodName,
                    $leftUnit->displayString,
                    $rightUnit->displayString,
                ));
            }

            return self::incompatibleDimensionError($methodName, $leftUnit, $rightUnit);
        }

        return $receiver;
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
        $results = [];
        foreach (UnitUnionTypeHelper::directAlternatives($argType) as $otherType) {
            if ($otherType instanceof QuantityType) {
                $other = $otherType->getUnitExpression();
                $result = $multiply
                    ? UnitExpressionAlgebra::multiply($unit, $other)
                    : UnitExpressionAlgebra::divide($unit, $other);
                $results[] = new QuantityType($result);
            } elseif (
                $otherType->isInteger()->yes()
                || (new ObjectType(Rational::class))->isSuperTypeOf($otherType)->yes()
            ) {
                $results[] = new QuantityType($unit);
            } else {
                // An unknown operand may be a Quantity whose unit changes the result.
                return null;
            }
        }

        return TypeCombinator::union(...$results);
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
        $others = $this->quantityTypes($other);
        if ($others === null) {
            return null;
        }

        $leftUnit = $receiver->getUnitExpression();
        $hasCompatibleOperand = false;
        foreach ($others as $otherType) {
            $rightUnit = $otherType->getUnitExpression();
            if (!$leftUnit->sameDimension($rightUnit)) {
                if ($methodName === 'equals') {
                    continue;
                }

                return self::incompatibleDimensionError($methodName, $leftUnit, $rightUnit);
            }

            $hasCompatibleOperand = true;
        }

        return match ($methodName) {
            'equals' => $hasCompatibleOperand ? new BooleanType() : new ConstantBooleanType(false),
            default => null,
        };
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

        if (
            $argType->getValue() < -Exponent::MAX_ABSOLUTE
            || $argType->getValue() > Exponent::MAX_ABSOLUTE
        ) {
            return new ErrorType(sprintf(
                'Quantity::pow() supports exponents from -%d through %d.',
                Exponent::MAX_ABSOLUTE,
                Exponent::MAX_ABSOLUTE,
            ));
        }

        try {
            return new QuantityType(UnitExpressionAlgebra::power($unit, $argType->getValue()));
        } catch (ExceptionInterface $exception) {
            return new ErrorType(sprintf('Cannot call Quantity::pow(): %s', $exception->getMessage()));
        }
    }

    /**
     * @param array<\PhpParser\Node\Arg> $args
     *
     * @logion [RAS 55:10] Behold, the bells of the upper city answered one another through
     *     the storm, until their divided voices became a single warning above the sleeping court.
     */
    private function root(UnitExpression $unit, array $args, Scope $scope): ?Type
    {
        if (count($args) < 1) {
            return null;
        }

        $argType = $scope->getType($args[0]->value);
        if (!$argType instanceof ConstantIntegerType) {
            return null;
        }

        try {
            return new QuantityType(UnitExpressionAlgebra::root($unit, $argType->getValue()));
        } catch (ExceptionInterface $exception) {
            return new ErrorType(sprintf('Cannot call Quantity::root(): %s', $exception->getMessage()));
        }
    }

    /**
     * @param list<QuantityType>           $receivers
     * @param array<\PhpParser\Node\Arg> $args
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

        $targetUnits = [];
        foreach ($constantStrings as $constantString) {
            $parsed = $this->parser->parse($constantString->getValue());
            if (!$parsed->isOk()) {
                return new ErrorType($parsed->errorMessage() ?? 'Invalid unit expression.');
            }

            $targetUnits[] = $parsed->expression();
        }

        if ($methodName === 'toCompact') {
            foreach ($targetUnits as $targetUnit) {
                if (!$targetUnit->symbolicExpr instanceof Unit) {
                    return new ErrorType((new UnsupportedUnitCompactionException($targetUnit->symbolicExpr))->getMessage());
                }
            }
        }

        foreach ($receivers as $receiver) {
            $sourceUnit = $receiver->getUnitExpression();
            foreach ($targetUnits as $targetUnit) {
                if ($sourceUnit->sameDimension($targetUnit)) {
                    continue;
                }

                return self::incompatibleDimensionError(
                    $methodName,
                    $sourceUnit,
                    $targetUnit,
                    count($constantStrings) === 1 ? $targetUnit->symbolicDisplayString() : null,
                );
            }
        }

        return match ($methodName) {
            'to' => TypeCombinator::union(...array_map(
                static fn (UnitExpression $targetUnit): QuantityType => new QuantityType($targetUnit),
                $targetUnits,
            )),
            'toCompact' => new ObjectType(Quantity::class),
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

        return new QuantityType(UnitExpression::fromNormalForm($normalized, $unit->dimension));
    }

    private function simplify(UnitExpression $unit): QuantityType
    {
        // Runtime Quantity::simplify() folds the normalized constant into the magnitude, so the
        // static unit contains only the remaining normalized factors.
        $simplified = NormalizedExpr::withoutConstant($unit->normalizedExpr);

        return new QuantityType(UnitExpression::fromNormalForm($simplified, $unit->dimension));
    }

    private static function incompatibleDimensionError(
        string $methodName,
        UnitExpression $left,
        UnitExpression $right,
        ?string $rightDisplayString = null,
    ): ErrorType {
        return new ErrorType(sprintf(
            'Cannot call Quantity::%s() with dimensionally incompatible units %s (%s) and %s (%s).',
            $methodName,
            $left->displayString,
            $left->dimension->toString(),
            $rightDisplayString ?? $right->displayString,
            $right->dimension->toString(),
        ));
    }

    private function isUnbrandedQuantity(Type $type): bool
    {
        return $type::class === ObjectType::class
            && $type->getObjectClassNames() === [Quantity::class];
    }

    /**
     * @return list<QuantityType>|null
     *
     * @logion [OSD 97:86] During the hour when luminous moths fill the empty lantern, silence the court and uncover the
     *     floor. Their wings shall cast the borders of a province omitted from every anthem; send bread toward that
     *     shadow before sunrise, lest the forgotten land become the measure of your feast.
     */
    private function quantityTypes(Type $type): ?array
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
}
