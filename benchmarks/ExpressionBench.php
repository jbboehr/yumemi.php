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

use jbboehr\Yumemi\Analyzer\ExprReducer;
use jbboehr\Yumemi\Expr;
use jbboehr\Yumemi\Expr\Power;
use jbboehr\Yumemi\Expr\Product;
use jbboehr\Yumemi\Expr\Unit;
use jbboehr\Yumemi\Quantity;
use jbboehr\Yumemi\Registry\Udunits2UnitRegistry;
use jbboehr\Yumemi\Units;
use PhpBench\Attributes as Bench;

#[Bench\Iterations(5)]
#[Bench\Warmup(2)]
#[Bench\Groups(['runtime', 'expression'])]
final class ExpressionBench
{
    private Units $units;
    private Expr $reductionInput;
    private Expr $normalizationInput;
    private Quantity $simplificationInput;

    public function setUp(): void
    {
        $this->units = new Units(new Udunits2UnitRegistry());
        $this->reductionInput = new Product([
            new Unit('meter'),
            new Power(new Unit('second'), -1),
            new Unit('second'),
            new Power(new Unit('kilogram'), 2),
            new Power(new Unit('kilogram'), -1),
        ]);
        $this->normalizationInput = $this->units->parse('mile / hour');
        $this->simplificationInput = $this->units->quantity(90, 'kilometer / hour');
    }

    #[Bench\BeforeMethods('setUp')]
    #[Bench\Revs(5000)]
    public function benchReduction(): Expr
    {
        return ExprReducer::reduce($this->reductionInput);
    }

    #[Bench\BeforeMethods('setUp')]
    #[Bench\Revs(200)]
    public function benchNormalization(): Expr
    {
        return $this->units->normalize($this->normalizationInput);
    }

    #[Bench\BeforeMethods('setUp')]
    #[Bench\Revs(100)]
    public function benchQuantitySimplification(): Quantity
    {
        return $this->simplificationInput->simplify();
    }
}
