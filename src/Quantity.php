<?php

namespace jbboehr\IudexMensurarumMysteriorum;

use jbboehr\IudexMensurarumMysteriorum\Analyzer\ExprReducer;
use jbboehr\IudexMensurarumMysteriorum\Analyzer\NormalizedExpr;
use jbboehr\IudexMensurarumMysteriorum\Analyzer\SymbolicAstConverter;
use jbboehr\IudexMensurarumMysteriorum\Exception\IncompatibleUnitException;
use jbboehr\IudexMensurarumMysteriorum\Expr\Constant;
use jbboehr\IudexMensurarumMysteriorum\Formatter\ExprFormatter;
use jbboehr\IudexMensurarumMysteriorum\Number\Rational;
use jbboehr\IudexMensurarumMysteriorum\Parser\Parser;

final class Quantity
{
    public readonly Rational $value;
    public readonly Expr $unit;
    private readonly Units $units;
    private readonly Expr $resolvedUnit;

    public function __construct(
        int|Rational $value,
        Expr|string $unit,
        ?Units $units = null,
        ?Expr $resolvedUnit = null,
    ) {
        $this->units = $units ?? Units::default();
        $this->value = self::rational($value);
        $this->unit = ExprReducer::reduce($this->symbolicExprFrom($unit));
        $this->resolvedUnit = ExprReducer::reduce($resolvedUnit ?? $this->resolvedExprFrom($unit));
    }

    public function add(self $other): self
    {
        $this->assertSameUnit($other);

        return new self(
            $this->value->add($other->value),
            $this->unit,
            $this->units,
            $this->resolvedUnit,
        );
    }

    public function div(self|int|Rational $other): self
    {
        if ($other instanceof self) {
            $unit = $this->unit->div($other->unit);

            return new self(
                $this->value->div($other->value),
                $unit,
                $this->units,
                $this->resolvedUnit->div($other->resolvedUnit),
            );
        }

        return new self(
            $this->value->div(self::rational($other)),
            $this->unit,
            $this->units,
            $this->resolvedUnit,
        );
    }

    public function expr(): Expr
    {
        return (new Constant($this->value))->mul($this->unit);
    }

    public function mul(self|int|Rational $other): self
    {
        if ($other instanceof self) {
            $unit = $this->unit->mul($other->unit);

            return new self(
                $this->value->mul($other->value),
                $unit,
                $this->units,
                $this->resolvedUnit->mul($other->resolvedUnit),
            );
        }

        return new self(
            $this->value->mul(self::rational($other)),
            $this->unit,
            $this->units,
            $this->resolvedUnit,
        );
    }

    public function normalize(): self
    {
        $unit = $this->normalizedUnit();

        return new self($this->value, $unit, $this->units, $unit);
    }

    public function simplify(): self
    {
        $unit = $this->normalizedUnit();
        $unitWithoutConstant = NormalizedExpr::withoutConstant($unit);

        return new self(
            $this->value->mul(NormalizedExpr::constant($unit)),
            $unitWithoutConstant,
            $this->units,
            $unitWithoutConstant,
        );
    }

    public function sub(self $other): self
    {
        $this->assertSameUnit($other);

        return new self(
            $this->value->sub($other->value),
            $this->unit,
            $this->units,
            $this->resolvedUnit,
        );
    }

    public function to(Expr|string $unit): self
    {
        $symbolicExpr = $this->symbolicExprFrom($unit);
        $resolvedExpr = $this->resolvedExprFrom($unit);

        return new self(
            $this->valueIn($resolvedExpr),
            $symbolicExpr,
            $this->units,
            $resolvedExpr,
        );
    }

    public function toString(): string
    {
        return ExprFormatter::format($this->expr());
    }

    public function unitToString(): string
    {
        return ExprFormatter::format($this->unit);
    }

    public function valueToString(): string
    {
        return $this->value->toString();
    }

    public function valueIn(Expr|string $unit): Rational
    {
        return $this->units->convert($this->value, $this->resolvedUnit, $this->resolvedExprFrom($unit));
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    private function assertSameUnit(self $other): void
    {
        if ($this->unit->toString() === $other->unit->toString()) {
            return;
        }

        throw IncompatibleUnitException::create($this->unit, $other->unit);
    }

    private function resolvedExprFrom(Expr|string $expr): Expr
    {
        return is_string($expr) ? $this->units->parse($expr) : $expr;
    }

    private function normalizedUnit(): Expr
    {
        return $this->units->normalize($this->resolvedUnit);
    }

    private static function rational(int|Rational $value): Rational
    {
        return $value instanceof Rational ? $value : new Rational($value);
    }

    private static function symbolicExprFrom(Expr|string $expr): Expr
    {
        return is_string($expr) ? (new SymbolicAstConverter())->convert(Parser::parseString($expr)) : $expr;
    }
}
