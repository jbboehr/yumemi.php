<?php

namespace jbboehr\IudexMensurarumMysteriorum\Expr;

use jbboehr\IudexMensurarumMysteriorum\Analyzer\DimensionResolver;
use jbboehr\IudexMensurarumMysteriorum\Analyzer\ExprReducer;
use jbboehr\IudexMensurarumMysteriorum\Analyzer\UnitNormalizer;
use jbboehr\IudexMensurarumMysteriorum\Dimension;
use jbboehr\IudexMensurarumMysteriorum\Expr;
use jbboehr\IudexMensurarumMysteriorum\Util\MathTrait;

final class Unit implements Expr
{
    use MathTrait;

    public function __construct(
        public readonly string $name,
        public readonly ?Expr $definition = null,
    ) {
        if ($this->name === '') {
            throw new \InvalidArgumentException('Unit name must not be empty.');
        }
    }

    public function isBase(): bool
    {
        return $this->definition === null;
    }

    public function dimension(): Dimension
    {
        return (new DimensionResolver(new UnitNormalizer()))->resolve($this);
    }

    public function toString(): string
    {
        return $this->name;
    }

    public function reduce(): Expr
    {
        return ExprReducer::reduce($this);
    }
}
