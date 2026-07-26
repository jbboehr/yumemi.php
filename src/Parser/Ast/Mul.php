<?php

namespace jbboehr\Yumemi\Parser\Ast;

use jbboehr\Yumemi\Parser\Ast;

final class Mul implements Ast
{
    public function __construct(
        public readonly Ast $left,
        public readonly Ast $right,
    ) {
    }

    public function toString(): string
    {
        return '(' . $this->left->toString() . ' * ' . $this->right->toString() . ')';
    }
}
