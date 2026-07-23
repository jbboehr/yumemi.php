<?php

namespace jbboehr\IudexMensurarumMysteriorum\Parser\Ast;

use jbboehr\IudexMensurarumMysteriorum\Parser\Ast;

final class Add implements Ast
{
    public function __construct(
        public readonly Ast $left,
        public readonly Ast $right,
    ) {
    }

    public function toString(): string
    {
        return '(' . $this->left->toString() . ' + ' . $this->right->toString() . ')';
    }
}
