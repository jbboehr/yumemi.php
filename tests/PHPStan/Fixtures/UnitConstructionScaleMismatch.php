<?php

namespace jbboehr\Yumemi\Tests\PHPStan\Fixtures;

use function jbboehr\Yumemi\unit;

final class UnitConstructionScaleMismatch
{
    /**
     * @param unit_float<'meter'> $length
     */
    public function expectMeters(float $length): void
    {
    }

    public static function exercise(): void
    {
        $feet = unit(3.0, 'foot');
        (new self())->expectMeters($feet);
    }
}

UnitConstructionScaleMismatch::exercise();
