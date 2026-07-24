<?php

namespace jbboehr\IudexMensurarumMysteriorum\Exception;

final class IncompatibleQuantityContextException extends \RuntimeException
{
    public static function create(): self
    {
        return new self('Quantities must use the same Units context.');
    }
}
