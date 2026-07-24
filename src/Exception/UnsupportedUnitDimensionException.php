<?php

namespace jbboehr\IudexMensurarumMysteriorum\Exception;

final class UnsupportedUnitDimensionException extends \RuntimeException
{
    public static function create(string $unitName): self
    {
        return new self('Cannot resolve dimension for unit: ' . $unitName);
    }

    public static function missingContext(string $unitName): self
    {
        return new self(sprintf(
            'Cannot resolve dimension for unit "%s": incomplete definition and no Units context. '
            . 'Obtain units via Units::unit() (or Units::parse / quantity APIs), '
            . 'not by constructing Unit directly.',
            $unitName,
        ));
    }
}
