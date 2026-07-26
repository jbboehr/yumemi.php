<?php

namespace jbboehr\Yumemi\Tests;

use jbboehr\Yumemi\Dimension;
use PHPUnit\Framework\TestCase;

final class DimensionTest extends TestCase
{
    public function testDimensionlessHasZeroPowers(): void
    {
        $dimension = Dimension::dimensionless();

        $this->assertTrue($dimension->isDimensionless());
        $this->assertSame([0, 0, 0, 0, 0, 0, 0], $dimension->powers());
        $this->assertSame('dimensionless', $dimension->toString());
        $this->assertSame('dimensionless', (string) $dimension);
    }

    public function testExposesNamedAxisPowers(): void
    {
        $dimension = new Dimension(1, 2, 3, 4, 5, 6, 7);

        $this->assertSame(1, $dimension->length());
        $this->assertSame(2, $dimension->mass());
        $this->assertSame(3, $dimension->time());
        $this->assertSame(4, $dimension->electricCurrent());
        $this->assertSame(5, $dimension->temperature());
        $this->assertSame(6, $dimension->amountOfSubstance());
        $this->assertSame(7, $dimension->luminousIntensity());
        $this->assertSame(4, $dimension->power(Dimension::AXIS_ELECTRIC_CURRENT));
    }

    public function testRejectsUnknownAxis(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Dimension::dimensionless()->power(99);
    }

    public function testCombinesDimensions(): void
    {
        $length = new Dimension(length: 1);
        $time = new Dimension(time: 1);
        $velocity = $length->div($time);

        $this->assertSame([1, 0, -1, 0, 0, 0, 0], $velocity->powers());
        $this->assertSame('length / time', $velocity->toString());

        $acceleration = $velocity->div($time);

        $this->assertSame([1, 0, -2, 0, 0, 0, 0], $acceleration->powers());
        $this->assertSame('length / time ^ 2', $acceleration->toString());
    }

    public function testRaisesDimensionToPower(): void
    {
        $velocity = new Dimension(length: 1, time: -1);

        $this->assertSame([2, 0, -2, 0, 0, 0, 0], $velocity->pow(2)->powers());
        $this->assertSame('length ^ 2 / time ^ 2', $velocity->pow(2)->toString());
    }

    public function testFormatsDenominatorOnlyAndCompoundDenominators(): void
    {
        $frequency = new Dimension(time: -1);
        $capacitance = new Dimension(length: -2, mass: -1, time: 4, electricCurrent: 2);

        $this->assertSame('1 / time', $frequency->toString());
        $this->assertSame('time ^ 4 * electric_current ^ 2 / (length ^ 2 * mass)', $capacitance->toString());
    }

    public function testComparesDimensions(): void
    {
        $left = new Dimension(length: 1, time: -1);
        $right = Dimension::fromPowers([1, 0, -1, 0, 0, 0, 0]);

        $this->assertTrue($left->equals($right));
        $this->assertFalse($left->equals(new Dimension(length: 1)));
    }
}
