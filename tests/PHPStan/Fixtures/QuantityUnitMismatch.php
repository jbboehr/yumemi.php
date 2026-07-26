<?php

namespace jbboehr\IudexMensurarumMysteriorum\Tests\PHPStan\Fixtures;

use jbboehr\IudexMensurarumMysteriorum\Quantity;
use jbboehr\IudexMensurarumMysteriorum\Units;

final class QuantityUnitMismatch
{
    /**
     * @param Quantity<'meter'> $length
     */
    public function expectMeters(Quantity $length): void
    {
    }

    public static function exercise(): void
    {
        $units = Units::default();
        $feet = $units->quantity(3, 'foot');
        (new self())->expectMeters($feet);
    }
}

QuantityUnitMismatch::exercise();
