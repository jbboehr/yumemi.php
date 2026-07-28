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

namespace jbboehr\Yumemi\Tests\Formatter;

use jbboehr\Yumemi\Exception\IncompatibleUnitException;
use jbboehr\Yumemi\Expr\Compound;
use jbboehr\Yumemi\Expr\Constant;
use jbboehr\Yumemi\Expr\Term;
use jbboehr\Yumemi\Expr\Unit;
use jbboehr\Yumemi\Units;
use PHPUnit\Framework\TestCase;

final class ExprFormatterTest extends TestCase
{
    public function testFormatsUnitsWithDenominator(): void
    {
        $expr = new Compound([
            new Constant(3),
            new Unit('meter'),
            new Term(new Unit('second'), -1),
        ]);

        $this->assertSame('3 * meter / second', Units::default()->formatter()->format($expr));
    }

    public function testDisplayFormDiffersFromStructuralToStringForQuotients(): void
    {
        $expr = Units::default()->parse('meter / second');

        $this->assertSame('meter * second ^ -1', $expr->toString());
        $this->assertSame('meter / second', Units::default()->formatter()->format($expr));
    }

    public function testIncompatibleUnitExceptionUsesDisplayForm(): void
    {
        $units = Units::default();
        $from = $units->parse('meter / second');
        $to = $units->parse('kilogram');

        $exception = IncompatibleUnitException::create($from, $to);

        $this->assertStringContainsString('meter / second', $exception->getMessage());
        $this->assertStringNotContainsString('second ^ -1', $exception->getMessage());
    }

    public function testFormatsMultipleDenominatorTermsWithParentheses(): void
    {
        $expr = new Compound([
            new Unit('centimeter'),
            new Term(new Unit('foot'), -1),
            new Term(new Unit('second'), -1),
        ]);

        $this->assertSame('centimeter / (foot * second)', Units::default()->formatter()->format($expr));
    }

    public function testFormatsPositivePowers(): void
    {
        $expr = (new Unit('meter'))->pow(2);

        $this->assertSame('meter ^ 2', Units::default()->formatter()->format($expr));
    }
}
