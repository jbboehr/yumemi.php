<?php

namespace jbboehr\Yumemi\Tests\PHPStan\Fixtures;

/**
 * RuleTestCase fixture: foot is not assignable to a meter parameter.
 *
 * Class lives under Tests\ autoload so ReflectionProvider can discover it.
 */
final class UnitFootNotMeterCase
{
    /**
     * @param unit_float<'meter'> $length
     */
    public function expectMetersOnly(float $length): void
    {
    }

    public static function exercise(): void
    {
        /** @var unit_float<'foot'> $feet */
        $feet = 3.0;
        (new self())->expectMetersOnly($feet);
    }
}

UnitFootNotMeterCase::exercise();
