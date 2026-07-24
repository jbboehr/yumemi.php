<?php

namespace jbboehr\IudexMensurarumMysteriorum\Exception;

use jbboehr\IudexMensurarumMysteriorum\Dimension;
use jbboehr\IudexMensurarumMysteriorum\Expr;
use jbboehr\IudexMensurarumMysteriorum\Formatter\ExprFormatter;

final class IncompatibleUnitException extends \RuntimeException
{
    public readonly Expr $from;
    public readonly Expr $to;
    public readonly ?Dimension $fromDimension;
    public readonly ?Dimension $toDimension;

    public function __construct(
        string $message,
        Expr $from,
        Expr $to,
        ?Dimension $fromDimension = null,
        ?Dimension $toDimension = null,
    ) {
        parent::__construct($message);
        $this->from = $from;
        $this->to = $to;
        $this->fromDimension = $fromDimension;
        $this->toDimension = $toDimension;
    }

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
            if ($fromDimension->equals($toDimension)) {
                $message .= sprintf(
                    ' Both have dimension %s; convert explicitly before adding or subtracting.',
                    $fromDimension->toString(),
                );
            } else {
                $message .= sprintf(
                    ' Dimensions: %s vs %s.',
                    $fromDimension->toString(),
                    $toDimension->toString(),
                );
            }
        }

        return new self($message, $from, $to, $fromDimension, $toDimension);
    }
}
