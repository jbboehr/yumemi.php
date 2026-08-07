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
use jbboehr\Yumemi\Exception\NonExactRootException;
use jbboehr\Yumemi\Exception\OverflowException;
use PHPUnit\Framework\TestCase;

final class DimensionTest extends TestCase
{
    public function testDimensionlessHasZeroPowers(): void
    {
        $dimension = Dimension::dimensionless();

        $this->assertTrue($dimension->isDimensionless());
        $this->assertSame($dimension, Dimension::dimensionless());
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

    public function testConstructsAndExposesNamedExtensionAxes(): void
    {
        $dimension = Dimension::fromNamedPowers([
            'zeta' => 2,
            Dimension::CURRENCY => 1,
            'time' => -1,
            'luminous_intensity' => 4,
            'alpha' => 0,
        ]);

        $this->assertSame([0, 0, -1, 0, 0, 0, 4], $dimension->powers());
        $this->assertSame([
            'time' => -1,
            'luminous_intensity' => 4,
            'currency' => 1,
            'zeta' => 2,
        ], $dimension->namedPowers());
        $this->assertSame(-1, $dimension->powerOf('time'));
        $this->assertSame(1, $dimension->powerOf(Dimension::CURRENCY));
        $this->assertSame(0, $dimension->powerOf('absent_axis'));
        $this->assertSame('luminous_intensity ^ 4 * currency * zeta ^ 2 / time', $dimension->toString());
    }

    public function testPreservesNegativeNamedExtensionPowers(): void
    {
        $dimension = Dimension::fromNamedPowers(['currency' => -1]);

        $this->assertSame(['currency' => -1], $dimension->namedPowers());
        $this->assertSame('1 / currency', $dimension->toString());
    }

    public function testRejectsInvalidNamedAxes(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('lower_snake_case');

        Dimension::fromNamedPowers(['Not Valid' => 1]);
    }

    public function testRejectsDimensionlessAsANamedAxis(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Dimension::fromNamedPowers(['dimensionless' => 1]);
    }

    public function testRejectsMalformedAdditionalAxisPayloads(): void
    {
        foreach (["currency\n", 'currency!'] as $name) {
            try {
                new Dimension(additionalPowers: [$name => 1]);
                self::fail('Expected malformed dimension axis to be rejected: ' . $name);
            } catch (\InvalidArgumentException $exception) {
                $this->assertStringContainsString('lower_snake_case', $exception->getMessage());
            }

            try {
                Dimension::dimensionless()->powerOf($name);
                self::fail('Expected malformed dimension lookup to be rejected: ' . $name);
            } catch (\InvalidArgumentException $exception) {
                $this->assertStringContainsString('lower_snake_case', $exception->getMessage());
            }
        }

        foreach ([[1], ['currency' => '1']] as $powers) {
            try {
                new Dimension(additionalPowers: $powers);
                self::fail('Expected malformed dimension powers to be rejected.');
            } catch (\InvalidArgumentException $exception) {
                $this->assertNotSame('', $exception->getMessage());
            }
        }
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

    public function testCombinesEveryDimensionAxis(): void
    {
        $left = new Dimension(1, 2, 3, 4, 5, 6, 7);
        $right = new Dimension(-1, 1, -2, 3, -4, 5, -6);

        $this->assertSame([0, 3, 1, 7, 1, 11, 1], $left->mul($right)->powers());
        $this->assertSame([2, 1, 5, 1, 9, 1, 13], $left->div($right)->powers());
    }

    public function testCombinesAndCancelsExtensionAxes(): void
    {
        $currency = Dimension::fromNamedPowers(['currency' => 1]);
        $events = Dimension::fromNamedPowers(['event_count' => 1]);

        $rate = $currency->div($events);

        $this->assertSame(['currency' => 1, 'event_count' => -1], $rate->namedPowers());
        $this->assertSame('currency / event_count', $rate->toString());
        $this->assertTrue($rate->mul($events)->equals($currency));
        $this->assertTrue($currency->div($currency)->isDimensionless());
    }

    public function testCombiningExtensionAxesRestoresCanonicalOrder(): void
    {
        $combined = Dimension::fromNamedPowers(['zeta' => 1])
            ->mul(Dimension::fromNamedPowers(['alpha' => 1]));

        $this->assertSame(['alpha' => 1, 'zeta' => 1], $combined->namedPowers());
        $this->assertSame('alpha * zeta', $combined->toString());
    }

    public function testRaisesDimensionToPower(): void
    {
        $velocity = new Dimension(length: 1, time: -1);

        $this->assertSame([2, 0, -2, 0, 0, 0, 0], $velocity->pow(2)->powers());
        $this->assertSame('length ^ 2 / time ^ 2', $velocity->pow(2)->toString());
    }

    public function testRaisesExtensionDimensionToPower(): void
    {
        $dimension = Dimension::fromNamedPowers(['currency' => 1, 'time' => -1])->pow(2);

        $this->assertSame(['time' => -2, 'currency' => 2], $dimension->namedPowers());
        $this->assertSame('currency ^ 2 / time ^ 2', $dimension->toString());
        $this->assertTrue($dimension->pow(0)->isDimensionless());
    }

    public function testZeroPowerIsDimensionless(): void
    {
        $this->assertTrue((new Dimension(length: 1, time: -1))->pow(0)->isDimensionless());
    }

    public function testTakesExactDimensionRoot(): void
    {
        $squaredVelocity = new Dimension(length: 2, time: -2);
        $velocity = $squaredVelocity->root(2);

        $this->assertSame(['length' => 1, 'time' => -1], $velocity->namedPowers());
        $this->assertTrue($velocity->pow(2)->equals($squaredVelocity));
    }

    public function testTakesExactExtensionDimensionRoot(): void
    {
        $dimension = Dimension::fromNamedPowers(['currency' => 6, 'event_count' => -3]);

        $this->assertSame(
            ['currency' => 2, 'event_count' => -1],
            $dimension->root(3)->namedPowers(),
        );
    }

    public function testDimensionlessHasEverySupportedExactRoot(): void
    {
        $this->assertTrue(Dimension::dimensionless()->root(10_000)->isDimensionless());
    }

    public function testRejectsNonExactDimensionRoot(): void
    {
        $this->expectException(NonExactRootException::class);

        (new Dimension(length: 2, time: -1))->root(2);
    }

    public function testRejectsNonPositiveDimensionRootDegree(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Dimension::dimensionless()->root(0);
    }

    public function testRejectsDimensionRootDegreeBeyondSupportedRange(): void
    {
        $this->expectException(OverflowException::class);

        Dimension::dimensionless()->root(10_001);
    }

    public function testRejectsOutOfRangeDimensionConstruction(): void
    {
        $this->expectException(OverflowException::class);

        new Dimension(length: 10_001);
    }

    public function testRejectsDimensionArithmeticBeyondSupportedRange(): void
    {
        $dimension = new Dimension(length: 100);

        $this->expectException(OverflowException::class);
        $dimension->pow(101);
    }

    public function testRejectsExtensionDimensionArithmeticBeyondSupportedRange(): void
    {
        $dimension = Dimension::fromNamedPowers(['currency' => 100]);

        $this->expectException(OverflowException::class);
        $dimension->pow(101);
    }

    public function testRejectsSerializedDimensionBeyondSupportedRange(): void
    {
        $payload = 'O:24:"jbboehr\\Yumemi\\Dimension":2:{s:7:"version";i:1;s:6:"powers";a:7:{'
            . 'i:0;i:10001;i:1;i:0;i:2;i:0;i:3;i:0;i:4;i:0;i:5;i:0;i:6;i:0;}}';

        $this->expectException(\UnexpectedValueException::class);
        unserialize($payload);
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
        $this->assertFalse(
            Dimension::fromNamedPowers(['currency' => 1])
                ->equals(Dimension::fromNamedPowers(['information' => 1])),
        );
    }
}
