<?php

namespace jbboehr\IudexMensurarumMysteriorum\Tests\PHPStan\Fixtures;

use function jbboehr\IudexMensurarumMysteriorum\unit;
use function jbboehr\IudexMensurarumMysteriorum\unit_to;

/**
 * RuleTestCase fixture: unit_to() produces unit_float<'meter'> for foot→meter.
 */
final class UnitToConversionCase
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
        $meters = unit_to($feet, 'foot', 'meter');
        (new self())->expectMeters($meters);
    }
}

UnitToConversionCase::exercise();
