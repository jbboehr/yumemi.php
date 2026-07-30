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
use jbboehr\Yumemi\Registry\Udunits2UnitRegistry;
use jbboehr\Yumemi\Units;
use PhpBench\Attributes as Bench;

#[Bench\Iterations(5)]
#[Bench\Warmup(2)]
#[Bench\Groups(['runtime', 'conversion', 'quantity'])]
final class ConversionAndQuantityBench
{
    private Units $units;
    private Quantity $meters;
    private Quantity $feet;
    private Quantity $speed;
    private Quantity $duration;

    public function setUp(): void
    {
        $this->units = new Units(new Udunits2UnitRegistry());
        $this->meters = $this->units->quantity(100, 'meter');
        $this->feet = $this->units->quantity(100, 'foot');
        $this->speed = $this->units->quantity(90, 'kilometer / hour');
        $this->duration = $this->units->quantity(30, 'second');

        $this->units->conversionFactor('meter', 'foot');
        $this->units->convert(100, 'celsius', 'fahrenheit');
    }

    #[Bench\BeforeMethods('setUp')]
    #[Bench\Revs(500)]
    public function benchWarmConversionFactor(): Rational
    {
        return $this->units->conversionFactor('meter', 'foot');
    }

    #[Bench\BeforeMethods('setUp')]
    #[Bench\Revs(200)]
    public function benchWarmAffineConversion(): Rational
    {
        return $this->units->convert(100, 'celsius', 'fahrenheit');
    }

    #[Bench\BeforeMethods('setUp')]
    #[Bench\Revs(100)]
    public function benchCompatibleAddition(): Quantity
    {
        return $this->meters->add($this->feet);
    }

    #[Bench\BeforeMethods('setUp')]
    #[Bench\Revs(100)]
    public function benchQuantityMultiplicationAndDivision(): Quantity
    {
        return $this->speed->mul($this->duration)->div($this->duration);
    }

    #[Bench\BeforeMethods('setUp')]
    #[Bench\Revs(200)]
    public function benchQuantityValueIn(): Rational
    {
        return $this->speed->valueIn('meter / second');
    }
}
