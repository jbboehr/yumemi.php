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

namespace jbboehr\Yumemi\Benchmarks;

use jbboehr\Yumemi\Number\Rational;
use jbboehr\Yumemi\Quantity;
use jbboehr\Yumemi\Units;
use PhpBench\Attributes as Bench;

use function jbboehr\Yumemi\unit;
use function jbboehr\Yumemi\unit_to;

#[Bench\Iterations(5)]
#[Bench\Warmup(2)]
#[Bench\Groups(['runtime', 'comparison', 'quantity'])]
final class NativeAndQuantityBench
{
    private Units $units;
    private float $distance = 100.0;
    private float $duration = 10.0;
    private float $brandedDistance;
    private float $brandedDuration;
    private Quantity $distanceQuantity;
    private Quantity $durationQuantity;
    private Quantity $speedQuantity;

    public function setUp(): void
    {
        $this->units = Units::default();
        $this->brandedDistance = unit($this->distance, 'meter');
        $this->brandedDuration = unit($this->duration, 'second');
        $this->distanceQuantity = $this->units->quantity(100, 'meter');
        $this->durationQuantity = $this->units->quantity(10, 'second');
        $this->speedQuantity = $this->units->quantity(90, 'kilometer / hour');

        unit_to(90.0, 'kilometer / hour', 'meter / second');
        $this->speedQuantity->valueIn('meter / second');
    }

    #[Bench\BeforeMethods('setUp')]
    #[Bench\Revs(100000)]
    public function benchPlainNativeDivision(): float
    {
        return $this->distance / $this->duration;
    }

    #[Bench\BeforeMethods('setUp')]
    #[Bench\Revs(500)]
    public function benchBrandAndDivideNativeValues(): float
    {
        return unit($this->distance, 'meter') / unit($this->duration, 'second');
    }

    #[Bench\BeforeMethods('setUp')]
    #[Bench\Revs(100000)]
    public function benchDividePreBrandedNativeValues(): float
    {
        return $this->brandedDistance / $this->brandedDuration;
    }

    #[Bench\BeforeMethods('setUp')]
    #[Bench\Revs(100)]
    public function benchConstructAndDivideQuantities(): Quantity
    {
        return $this->units
            ->quantity(100, 'meter')
            ->div($this->units->quantity(10, 'second'));
    }

    #[Bench\BeforeMethods('setUp')]
    #[Bench\Revs(2000)]
    public function benchDividePreconstructedQuantities(): Quantity
    {
        return $this->distanceQuantity->div($this->durationQuantity);
    }

    #[Bench\BeforeMethods('setUp')]
    #[Bench\Revs(200)]
    public function benchNativeUnitConversion(): float
    {
        return unit_to(90.0, 'kilometer / hour', 'meter / second');
    }

    #[Bench\BeforeMethods('setUp')]
    #[Bench\Revs(200)]
    public function benchExactQuantityConversion(): Rational
    {
        return $this->speedQuantity->valueIn('meter / second');
    }
}
