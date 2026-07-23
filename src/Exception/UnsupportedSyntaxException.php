<?php

namespace jbboehr\IudexMensurarumMysteriorum\Exception;

use jbboehr\IudexMensurarumMysteriorum\Parser\Ast;

final class UnsupportedSyntaxException extends \RuntimeException
{
    public static function create(Ast $ast): self
    {
        return new self(sprintf(
            'Unsupported unit expression syntax: %s.',
            $ast->toString(),
        ));
    }
}
