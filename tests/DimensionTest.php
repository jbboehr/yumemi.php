<?php

/**
 * +--------------------------------------------------------------------------------------------------------------+
 * |        *                 .                         *                  .                         *            |
 * |   .              *                      .                    *                      .                        |
 * |             .                 .                  *                         .                 *               |
 * -      *                    .             *                    .                         .                     -
 *
 *                               Iudex Mensurarum Mysticarum『夢見』〜ＹＵＭＥＭＩ〜
 *
 * -                                          .----------------.                                                  -
 * |                                      .--'        __        '--.                                              |
 * |                                  .--'          .'  '.          '--.                                          |
 * |                             .---'            .'      '.            '---.                                     |
 * +--------------------------------------------------------------------------------------------------------------+
 *
 * Copyright (c) anno Domini nostri Jesu Christi MMXXVI, John Boehr & contributors
 *
 * SPDX-License-Identifier: AGPL-3.0-only WITH romic-exception
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License version 3,
 * as published by the Free Software Foundation, together with the Romic
 * Exception (an additional permission under section 7 of that license).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * and the Romic Exception along with this program.  If not, see
 * <http://www.gnu.org/licenses/> and the LICENSE_EXCEPTION file.
 */

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
