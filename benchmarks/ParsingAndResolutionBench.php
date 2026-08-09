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

use jbboehr\Yumemi\Expr;
use jbboehr\Yumemi\Registry\Udunits2UnitRegistry;
use jbboehr\Yumemi\Units;
use PhpBench\Attributes as Bench;

#[Bench\Iterations(5)]
#[Bench\Warmup(2)]
#[Bench\Groups(['runtime', 'parsing'])]
final class ParsingAndResolutionBench
{
    private Units $units;

    public function setUpWarmContext(): void
    {
        $this->units = self::newUnits();
        $this->units->parse('meter');
        $this->units->parse('kilometer');
        $this->units->parse('kilogram * meter / second^2');
    }

    #[Bench\Revs(5)]
    public function benchColdContextAndCompoundParse(): Expr
    {
        /** @var int $sequence */
        static $sequence = 0;

        return self::newUnits()->parse(
            str_repeat(' ', ++$sequence) . 'kilogram * meter / second^2',
        );
    }

    #[Bench\BeforeMethods('setUpWarmContext')]
    #[Bench\Revs(5)]
    public function benchUncachedCompoundParseInWarmContext(): Expr
    {
        /** @var int $sequence */
        static $sequence = 0;

        return $this->units->parse(
            str_repeat("\t", ++$sequence) . 'kilogram * meter / second^2',
        );
    }

    #[Bench\BeforeMethods('setUpWarmContext')]
    #[Bench\Revs(500)]
    public function benchWarmSimpleParse(): Expr
    {
        return $this->units->parse('meter');
    }

    #[Bench\BeforeMethods('setUpWarmContext')]
    #[Bench\Revs(500)]
    public function benchWarmPrefixedParse(): Expr
    {
        return $this->units->parse('kilometer');
    }

    #[Bench\BeforeMethods('setUpWarmContext')]
    #[Bench\Revs(200)]
    public function benchWarmCompoundParse(): Expr
    {
        return $this->units->parse('kilogram * meter / second^2');
    }

    private static function newUnits(): Units
    {
        return new Units(new Udunits2UnitRegistry());
    }
}
