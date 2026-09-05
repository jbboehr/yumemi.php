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

use jbboehr\Yumemi\Number\DecimalNotation;
use jbboehr\Yumemi\Number\Rational;
use PhpBench\Attributes as Bench;

#[Bench\Iterations(5)]
#[Bench\Warmup(2)]
#[Bench\Groups(['runtime', 'rational', 'numeric-output'])]
final class RationalBench
{
    private \GMP $integerInput;
    private \GMP $denominatorInput;
    private Rational $left;
    private Rational $right;
    private Rational $terminating;

    public function setUp(): void
    {
        $this->integerInput = gmp_init(123456789);
        $this->denominatorInput = gmp_init(1);
        $this->left = new Rational(123456789, 1000000);
        $this->right = new Rational(355, 113);
        $this->terminating = Rational::fromDecimalString('123456789.123456789');
    }

    #[Bench\Revs(1000)]
    public function benchConstructInteger(): Rational
    {
        return new Rational(123456789);
    }

    #[Bench\BeforeMethods('setUp')]
    #[Bench\Revs(1000)]
    public function benchConstructGmpInteger(): Rational
    {
        return new Rational($this->integerInput);
    }

    #[Bench\BeforeMethods('setUp')]
    #[Bench\Revs(1000)]
    public function benchConstructGmpPair(): Rational
    {
        return new Rational($this->integerInput, $this->denominatorInput);
    }

    #[Bench\BeforeMethods('setUp')]
    #[Bench\Revs(1000)]
    public function benchReadNumerator(): \GMP
    {
        return $this->left->numerator();
    }

    #[Bench\BeforeMethods('setUp')]
    #[Bench\Revs(1000)]
    public function benchSerialize(): string
    {
        return serialize($this->left);
    }

    #[Bench\BeforeMethods('setUp')]
    #[Bench\Revs(1000)]
    public function benchConstructReducedFraction(): Rational
    {
        return new Rational(123456789, 1000000);
    }

    #[Bench\BeforeMethods('setUp')]
    #[Bench\Revs(1000)]
    public function benchAddition(): Rational
    {
        return $this->left->add($this->right);
    }

    #[Bench\BeforeMethods('setUp')]
    #[Bench\Revs(1000)]
    public function benchMultiplication(): Rational
    {
        return $this->left->mul($this->right);
    }

    #[Bench\BeforeMethods('setUp')]
    #[Bench\Revs(1000)]
    public function benchDivision(): Rational
    {
        return $this->left->div($this->right);
    }

    #[Bench\BeforeMethods('setUp')]
    #[Bench\Revs(500)]
    public function benchRoundedDecimal(): string
    {
        return $this->right->toDecimal(12, \RoundingMode::HalfEven);
    }

    #[Bench\BeforeMethods('setUp')]
    #[Bench\Revs(200)]
    public function benchSignificantPlainDecimal(): string
    {
        return $this->right->toSignificantDecimal(12, \RoundingMode::HalfEven);
    }

    #[Bench\BeforeMethods('setUp')]
    #[Bench\Revs(200)]
    public function benchSignificantScientificDecimal(): string
    {
        return $this->right->toSignificantDecimal(
            12,
            \RoundingMode::HalfEven,
            DecimalNotation::Scientific,
        );
    }

    #[Bench\BeforeMethods('setUp')]
    #[Bench\Revs(500)]
    public function benchExactTerminatingDecimal(): string
    {
        return $this->terminating->toDecimalExact();
    }

    #[Bench\BeforeMethods('setUp')]
    #[Bench\Revs(1000)]
    public function benchBinaryFloatOutput(): float
    {
        return $this->right->toFloat();
    }
}
