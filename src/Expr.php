<?php

namespace jbboehr\IudexMensurarumMysteriorum;

interface Expr
{
    public function div(self $expr): self;

    public function mul(self $expr): self;

    public function pow(int $power): self;

    public function reduce(): self;

    public function toString(): string;
}
