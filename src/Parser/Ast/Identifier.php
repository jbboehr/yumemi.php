<?php

namespace jbboehr\Yumemi\Parser\Ast;

use jbboehr\Yumemi\Parser\Ast;

final class Identifier implements Ast
{
    public function __construct(
        public readonly string $identifier,
    ) {
    }

    public function toString(): string
    {
        return $this->identifier;
    }
}
