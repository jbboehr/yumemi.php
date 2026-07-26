<?php

namespace jbboehr\Yumemi\Exception;

use jbboehr\Yumemi\Units;

final class IncompatibleQuantityContextException extends \RuntimeException
{
    public readonly ?int $leftContextId;
    public readonly ?int $rightContextId;

    public function __construct(
        string $message,
        ?int $leftContextId = null,
        ?int $rightContextId = null,
    ) {
        parent::__construct($message);
        $this->leftContextId = $leftContextId;
        $this->rightContextId = $rightContextId;
    }

    public static function create(?Units $left = null, ?Units $right = null): self
    {
        $leftId = $left !== null ? spl_object_id($left) : null;
        $rightId = $right !== null ? spl_object_id($right) : null;

        $message = 'Quantities must use the same Units context (object identity). '
            . 'Units::default() is shared; for isolation construct new Units($registry).';

        if ($leftId !== null && $rightId !== null) {
            $message .= sprintf(' Got contexts #%d and #%d.', $leftId, $rightId);
        }

        return new self($message, $leftId, $rightId);
    }
}
