<?php

/**
 * Iudex Mensurarum Mysticarum『夢見』〜ＹＵＭＥＭＩ〜
 *
 * Copyright (c) anno Domini nostri Jesu Christi MMXXVI, John Boehr & contributors
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace jbboehr\Yumemi\Tests\Formatter;

use jbboehr\Yumemi\Exception\IncompatibleUnitException;
use jbboehr\Yumemi\Expr\Compound;
use jbboehr\Yumemi\Expr\Constant;
use jbboehr\Yumemi\Expr\Term;
use jbboehr\Yumemi\Expr\Unit;
use jbboehr\Yumemi\Formatter\ExprFormatter;
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

        $this->assertSame('3 * meter / second', ExprFormatter::format($expr));
    }

    public function testDisplayFormDiffersFromStructuralToStringForQuotients(): void
    {
        $expr = Units::default()->parse('meter / second');

        $this->assertSame('meter * second ^ -1', $expr->toString());
        $this->assertSame('meter / second', ExprFormatter::format($expr));
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

        $this->assertSame('centimeter / (foot * second)', ExprFormatter::format($expr));
    }

    public function testFormatsPositivePowers(): void
    {
        $expr = (new Unit('meter'))->pow(2);

        $this->assertSame('meter ^ 2', ExprFormatter::format($expr));
    }
}
