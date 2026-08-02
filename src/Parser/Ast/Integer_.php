<?php

namespace jbboehr\Yumemi\Parser\Ast;

use jbboehr\Yumemi\Parser\AstNode;
use jbboehr\Yumemi\Parser\SourceSpan;

final class Integer_ extends AstNode implements Number
{
    public function __construct(
        public readonly string $value,
        ?SourceSpan $span = null,
    ) {
        parent::__construct($span);
    }

    public function toString(): string
    {
        return $this->value;
    }
}
