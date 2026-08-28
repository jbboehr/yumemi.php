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

namespace jbboehr\Yumemi\Tests\Extension;

use jbboehr\Yumemi\Exception\DivisionByZeroError;
use jbboehr\Yumemi\Exception\IncompatibleQuantityContextException;
use jbboehr\Yumemi\InternalQuantity;
use jbboehr\Yumemi\Number\Rational;
use jbboehr\Yumemi\Quantity;
use jbboehr\Yumemi\Registry\Udunits2UnitRegistry;
use jbboehr\Yumemi\Units;
use PHPUnit\Framework\TestCase;

final class OperatorIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        self::assertTrue(
            extension_loaded('yumemi'),
            'Run the extension integration suite with ext-yumemi loaded before Composer.',
        );
    }

    public function testQuantityExtendsTheExtensionBaseClass(): void
    {
        $base = new \ReflectionClass(InternalQuantity::class);
        $parent = (new \ReflectionClass(Quantity::class))->getParentClass();

        self::assertTrue($base->isInternal());
        self::assertTrue($base->isAbstract());
        self::assertInstanceOf(\ReflectionClass::class, $parent);
        self::assertSame(InternalQuantity::class, $parent->getName());
    }

    public function testQuantityArithmeticOperatorsDelegateToMethods(): void
    {
        $units = Units::default();
        $meters = $units->quantity(2, 'meter');
        $feet = $units->quantity(1, 'foot');
        $seconds = $units->quantity(3, 'second');

        self::assertSameQuantity($meters->add($feet), $meters + $feet);
        self::assertSameQuantity($meters->sub($feet), $meters - $feet);
        self::assertSameQuantity($meters->mul($seconds), $meters * $seconds);
        self::assertSameQuantity($meters->div($seconds), $meters / $seconds);
        self::assertSameQuantity($meters->pow(2), $meters ** 2);
        self::assertSameQuantity($meters->pow(-1), $meters ** -1);
    }

    public function testQuantityScalarOperatorsDelegateToMethods(): void
    {
        $meters = Units::default()->quantity(2, 'meter');
        $rational = new Rational(3, 2);

        self::assertSameQuantity($meters->mul(3), $meters * 3);
        self::assertSameQuantity($meters->div($rational), $meters / $rational);
        self::assertSameQuantity($meters->mul(3), 3 * $meters);
        self::assertSameQuantity($meters->mul($rational), $rational * $meters);
        self::assertSameQuantity($meters->rdiv(6), 6 / $meters);
        self::assertSameQuantity($meters->rdiv($rational), $rational / $meters);

        $scalarLeftCompound = 6;
        $scalarLeftCompound /= $meters;
        self::assertSameQuantity($meters->rdiv(6), $scalarLeftCompound);
    }

    public function testTemporaryQuantityOperandsRemainValid(): void
    {
        $units = Units::default();
        $meters = $units->quantity(2, 'meter');
        $seconds = $units->quantity(3, 'second');

        self::assertSameQuantity(
            $units->quantity(2, 'meter')->mul($seconds),
            $units->quantity(2, 'meter') * $seconds,
        );
        self::assertSameQuantity(
            $meters->mul($units->quantity(3, 'second')),
            $meters * $units->quantity(3, 'second'),
        );
    }

    public function testUnsupportedOperandsAreRejected(): void
    {
        $meters = Units::default()->quantity(2, 'meter');

        self::assertRejectsUnsupportedOperands(static fn (): mixed => 1 + $meters);
        self::assertRejectsUnsupportedOperands(static fn (): mixed => 1 - $meters);
        self::assertRejectsUnsupportedOperands(static fn (): mixed => [] / $meters);
        self::assertRejectsUnsupportedOperands(static fn (): mixed => 2 ** $meters);
    }

    public function testScalarLeftDivisionByZeroRetainsTheMethodException(): void
    {
        $units = Units::default();

        try {
            1 / $units->quantity(0, 'meter');
            self::fail('Scalar-left division by a zero quantity must retain the method-layer exception.');
        } catch (DivisionByZeroError) {
            $this->addToAssertionCount(1);
        }
    }

    public function testCrossContextMultiplicationRetainsTheMethodException(): void
    {
        $leftContext = new Units(new Udunits2UnitRegistry());
        $rightContext = new Units(new Udunits2UnitRegistry());

        try {
            $leftContext->quantity(1, 'meter') * $rightContext->quantity(1, 'meter');
            self::fail('Cross-context operator multiplication must retain the method-layer exception.');
        } catch (IncompatibleQuantityContextException) {
            $this->addToAssertionCount(1);
        }
    }

    private static function assertSameQuantity(Quantity $expected, Quantity $actual): void
    {
        self::assertSame($expected->valueToString(), $actual->valueToString());
        self::assertSame($expected->unitToString(), $actual->unitToString());
    }

    /**
     * @param \Closure(): mixed $operation
     */
    private function assertRejectsUnsupportedOperands(\Closure $operation): void
    {
        try {
            $operation();
            self::fail('Unsupported scalar-left operators must be rejected.');
        } catch (\TypeError) {
            $this->addToAssertionCount(1);
        }
    }
}
