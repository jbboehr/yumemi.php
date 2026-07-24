<?php

namespace jbboehr\IudexMensurarumMysteriorum\Exception;

final class UnsupportedUnitDimensionException extends \RuntimeException
{
    public static function create(string $unitName): self
    {
        return new self('Cannot resolve dimension for unit: ' . $unitName);
    }
}
