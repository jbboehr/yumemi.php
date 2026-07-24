<?php

namespace jbboehr\IudexMensurarumMysteriorum\Exception;

use jbboehr\IudexMensurarumMysteriorum\Dimension;
use jbboehr\IudexMensurarumMysteriorum\Expr;
use jbboehr\IudexMensurarumMysteriorum\Formatter\ExprFormatter;

final class IncompatibleUnitException extends \RuntimeException
{
    public static function create(
        Expr $from,
        Expr $to,
        ?Dimension $fromDimension = null,
        ?Dimension $toDimension = null,
    ): self {
        $message = sprintf(
            'Incompatible unit expressions: %s and %s.',
            ExprFormatter::format($from),
            ExprFormatter::format($to),
        );

        if ($fromDimension !== null && $toDimension !== null) {
            $message = sprintf(
                '%s Dimensions: %s vs %s.',
                $message,
                $fromDimension->toString(),
                $toDimension->toString(),
            );
        }

        return new self($message);
    }
}
