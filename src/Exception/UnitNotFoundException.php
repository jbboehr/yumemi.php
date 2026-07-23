<?php

namespace jbboehr\IudexMensurarumMysteriorum\Exception;

final class UnitNotFoundException extends \RuntimeException
{
    public static function create(string $name): self
    {
        return new self(sprintf('Unit not found: %s.', $name));
    }
}
