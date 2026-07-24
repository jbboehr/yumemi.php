<?php

namespace jbboehr\IudexMensurarumMysteriorum;

interface Expr
{
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
