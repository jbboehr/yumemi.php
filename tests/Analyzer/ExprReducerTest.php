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

namespace jbboehr\Yumemi\Tests\Analyzer;

use jbboehr\Yumemi\Analyzer\ExprReducer;
use jbboehr\Yumemi\Exception\NonExactRootException;
use jbboehr\Yumemi\Expr\Product;
use jbboehr\Yumemi\Expr\Constant;
use jbboehr\Yumemi\Expr\Power;
use jbboehr\Yumemi\Expr\Unit;
use jbboehr\Yumemi\Exception\OverflowException;
use jbboehr\Yumemi\Number\Rational;
use jbboehr\Yumemi\Units;
use PHPUnit\Framework\TestCase;

final class ExprReducerTest extends TestCase
{
    public function testConstantsReduceToRational(): void
    {
        $expr = (new Constant(2))
            ->mul(new Constant(3))
            ->div(new Constant(4));

        $this->assertSame('3/2', $expr->toString());
    }

    public function testIdenticalUnitsCombine(): void
    {
        $meter = new Unit('meter');

        $expr = $meter->mul($meter);

        $this->assertSame('meter ^ 2', $expr->toString());
    }

    public function testInverseUnitsCancel(): void
    {
        $meter = new Unit('meter');
        $second = new Unit('second');

        $expr = $meter
            ->div($second)
            ->mul($second);

        $this->assertSame('meter', $expr->toString());
    }

    public function testEqualsIsStructuralAfterReduction(): void
    {
        $meter = new Unit('meter');
        $second = new Unit('second');

        $left = $meter->mul($second);
        $right = $second->mul($meter);

        $this->assertTrue($left->equals($right));
        $this->assertFalse($meter->equals($second));
        $this->assertTrue((new Constant(2))->mul($meter)->equals($meter->mul(new Constant(2))));
        $this->assertTrue((new Constant(new Rational(2, 4)))->equals(new Constant(new Rational(1, 2))));
    }

    public function testPowersCompareExponentAndBase(): void
    {
        $meter = new Unit('meter');
        $second = new Unit('second');

        $this->assertFalse((new Power($meter, 2))->equals(new Power($meter, 3)));
        $this->assertFalse((new Power($meter, 2))->equals(new Power($second, 2)));
    }

    public function testPowerAndProductExposeMathematicalParts(): void
    {
        $meter = new Unit('meter');
        $second = new Unit('second');
        $power = new Power($second, -2);
        $product = new Product([$meter, $power]);

        $this->assertSame($second, $power->base);
        $this->assertSame(-2, $power->exponent);
        $this->assertSame([$meter, $power], $product->factors);
    }

    public function testUnitEqualsIgnoresDefinitionAndUnitsContext(): void
    {
        $units = Units::default();
        $bound = $units->unit('meter');
        $bare = new Unit('meter');

        $this->assertInstanceOf(Unit::class, $bound);
        $this->assertTrue($bound->equals($bare));
        $this->assertTrue($bare->equals(new Unit('meter', new Constant(1))));
    }

    public function testProductPowersDistribute(): void
    {
        $meter = new Unit('meter');
        $second = new Unit('second');

        $expr = (new Product([
            $meter,
            new Power($second, -1),
        ]))->pow(2);

        $this->assertSame('meter ^ 2 * second ^ -2', $expr->toString());
    }

    public function testUnitOrderingIsDeterministic(): void
    {
        $meter = new Unit('meter');
        $second = new Unit('second');

        $expr = $second->mul($meter);

        $this->assertSame('meter * second', $expr->toString());
    }

    public function testReducedConstantOneIsKeptForDimensionlessExpressions(): void
    {
        $meter = new Unit('meter');

        $expr = $meter->div($meter);

        $this->assertSame('1', $expr->toString());
    }

    public function testNegativeRationalPowersInvert(): void
    {
        $expr = (new Constant(new Rational(2, 3)))->pow(-2);

        $this->assertSame('9/4', $expr->toString());
    }

    public function testTakesExactRootOfReducedExpression(): void
    {
        $expr = (new Constant(new Rational(4, 9)))
            ->mul((new Unit('meter'))->pow(2))
            ->div((new Unit('second'))->pow(4));

        $this->assertSame('2/3 * meter * second ^ -2', ExprReducer::root($expr, 2)->toString());
    }

    public function testRootCombinesRepeatedSymbolsBeforeCheckingPowers(): void
    {
        $meter = new Unit('meter');

        $this->assertSame('meter', ExprReducer::root(new Product([$meter, $meter]), 2)->toString());
    }

    public function testRejectsSymbolicallyNonExactRoot(): void
    {
        $this->expectException(NonExactRootException::class);

        ExprReducer::root((new Unit('kilometer'))->mul(new Unit('millimeter')), 2);
    }

    public function testRejectsExpressionWithNonExactConstantRoot(): void
    {
        $this->expectException(NonExactRootException::class);

        ExprReducer::root((new Constant(2))->mul((new Unit('meter'))->pow(2)), 2);
    }

    public function testRejectsNestedPowersWhoseCombinedExponentExceedsTheLimit(): void
    {
        $this->expectException(OverflowException::class);

        (new Power(new Power(new Unit('meter'), 101), 100))->reduce();
    }

    public function testRejectsPowerWhoseExponentExceedsTheLimit(): void
    {
        $this->expectException(OverflowException::class);

        new Power(new Unit('meter'), 10_001);
    }
}
