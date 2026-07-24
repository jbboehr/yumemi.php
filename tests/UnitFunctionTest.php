<?php

namespace jbboehr\IudexMensurarumMysteriorum\Tests;

use PHPUnit\Framework\TestCase;

use function jbboehr\IudexMensurarumMysteriorum\unit;

final class UnitFunctionTest extends TestCase
{
    public function testReturnsMagnitudeUnchanged(): void
    {
        $this->assertSame(1500.0, unit(1500.0, 'kilogram'));
        $this->assertSame(3, unit(3, 'meter'));
    }

    public function testRejectsUnknownUnit(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid unit expression');

        unit(1.0, 'not_a_real_unit_xyz');
    }
}
