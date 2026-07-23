<?php

namespace jbboehr\IudexMensurarumMysteriorum\Parser\Ast;

final class Float_ implements Number
{
    public function __construct(
        public readonly string $value
    ) {
    }

    public function toString(): string
    {
        return $this->value;
    }
}
