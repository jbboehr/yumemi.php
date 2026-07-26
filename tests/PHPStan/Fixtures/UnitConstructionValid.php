<?php

namespace jbboehr\Yumemi\Tests\PHPStan\Fixtures;

use function jbboehr\Yumemi\unit;

final class UnitConstructionValid
{
    /**
     * @param unit_float<'newton'> $force
     */
    public function expectForce(float $force): void
    {
    }

    public static function exercise(): void
    {
        $mass = unit(1500.0, 'kilogram');
        $accel = unit(3.0, 'meter / second^2');
        (new self())->expectForce($mass * $accel);
    }
}

UnitConstructionValid::exercise();
