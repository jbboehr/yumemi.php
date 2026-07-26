<?php

namespace jbboehr\Yumemi\Parser\Ast;

final class Integer_ implements Number
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
