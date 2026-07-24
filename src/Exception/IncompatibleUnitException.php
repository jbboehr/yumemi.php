<?php

namespace jbboehr\IudexMensurarumMysteriorum\Exception;

use jbboehr\IudexMensurarumMysteriorum\Expr;
use jbboehr\IudexMensurarumMysteriorum\Formatter\ExprFormatter;

final class IncompatibleUnitException extends \RuntimeException
{
    public static function create(Expr $from, Expr $to): self
    {
        return new self(sprintf(
            'Incompatible unit expressions: %s and %s.',
            ExprFormatter::format($from),
            ExprFormatter::format($to),
        ));
    }
}
