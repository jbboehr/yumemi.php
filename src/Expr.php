<?php

namespace jbboehr\IudexMensurarumMysteriorum;

interface Expr
{
    /**
     * Dimensional identity of this expression.
     *
     * Resolved recursively: a product multiplies its factors' dimensions, a power
     * scales them, a bare constant is dimensionless, and a unit leaf resolves through
     * its definition tree (falling back to bound {@see Units} context). Requires each
     * unit leaf to be resolvable — i.e. to carry a definition or a bound catalog
     * context; values from {@see Units::unit()}, parse, or quantity APIs satisfy this.
     */
    public function dimension(): Dimension;

    public function div(self $expr): self;

    /**
     * Structural equality after canonical reduction.
     *
     * Not a display-string comparison; see Formatter\ExprFormatter for rendering.
     */
    public function equals(self $expr): bool;

    public function mul(self $expr): self;

    public function pow(int $power): self;

    public function reduce(): self;

    /**
     * Structural / debug rendering of the expression tree.
     *
     * Prefer Formatter\ExprFormatter for user-facing display.
     */
    public function toString(): string;
}
