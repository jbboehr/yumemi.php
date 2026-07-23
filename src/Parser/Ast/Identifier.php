<?php

namespace jbboehr\IudexMensurarumMysteriorum\Parser\Ast;

use jbboehr\IudexMensurarumMysteriorum\Parser\Ast;

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
