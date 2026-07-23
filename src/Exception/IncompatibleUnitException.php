<?php

namespace jbboehr\IudexMensurarumMysteriorum\Exception;

use jbboehr\IudexMensurarumMysteriorum\Expr;

final class IncompatibleUnitException extends \RuntimeException
{
    public static function create(Expr $from, Expr $to): self
    {
        return new self(sprintf(
            'Incompatible unit expressions: %s and %s.',
            $from->toString(),
            $to->toString(),
        ));
    }
}
