<?php

namespace jbboehr\IudexMensurarumMysteriorum\PHPStan;

use jbboehr\IudexMensurarumMysteriorum\Formatter\ExprFormatter;
use jbboehr\IudexMensurarumMysteriorum\Quantity;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

/**
 * Propagates the unit through the fluent {@see Quantity} method chain.
 *
 * When the receiver is a branded {@see QuantityType}, each unit-bearing method returns a new
 * QuantityType whose unit matches the runtime result (see the table in {@see Quantity}):
 * `mul`/`div` combine units via {@see UnitExpressionAlgebra}; `pow` raises by a constant integer;
 * `neg`/`add`/`sub` keep the left unit; `to` rebrands to the (constant, statically parseable) target
 * unit; `normalize` rebrands to the catalog-normalized form.
 *
 * Fails open like {@see UnitsQuantityReturnTypeExtension}: anything not statically computable —
 * non-constant exponent/target, an unbranded {@see Quantity} operand, or a target unit unknown to
 * the default catalog — returns null, falling back to the native `Quantity` return.
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
            'mul', 'div', 'pow', 'neg', 'add', 'sub', 'to', 'normalize',
        ], true);
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): ?Type {
        $receiver = $scope->getType($methodCall->var);
        if (!$receiver instanceof QuantityType) {
            return null;
        }

        $unit = $receiver->getUnitExpression();
        $args = $methodCall->getArgs();

        return match ($methodReflection->getName()) {
            // Unary / left-unit-preserving: neg keeps the unit, add/sub keep the left operand's.
            'neg', 'add', 'sub' => $receiver,
            'mul' => $this->combine($unit, $args, $scope, true),
            'div' => $this->combine($unit, $args, $scope, false),
            'pow' => $this->power($unit, $args, $scope),
            'to' => $this->convert($args, $scope),
            'normalize' => $this->normalize($unit),
            default => null,
        };
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
    private function convert(array $args, Scope $scope): ?Type
    {
        if (count($args) < 1) {
            return null;
        }

        $constantStrings = $scope->getType($args[0]->value)->getConstantStrings();
        if (count($constantStrings) !== 1) {
            return null;
        }

        $parsed = $this->parser->parse($constantStrings[0]->getValue());
        if (!$parsed->isOk()) {
            // Fail open: to() converts through the instance's (possibly custom) registry.
            return null;
        }

        return new QuantityType($parsed->expression());
    }

    private function normalize(UnitExpression $unit): QuantityType
    {
        // The catalog-normalized form is already carried on the UnitExpression; rebrand to it.
        // Dimension is preserved by normalization, and the normalized expr is its own normal form.
        $normalized = $unit->normalizedExpr;

        return new QuantityType(new UnitExpression(
            $normalized,
            ExprFormatter::format($normalized),
            $unit->dimension,
            $normalized,
        ));
    }

    private function isUnbrandedQuantity(Type $type): bool
    {
        return !$type instanceof QuantityType
            && (new ObjectType(Quantity::class))->isSuperTypeOf($type)->yes();
    }
}
