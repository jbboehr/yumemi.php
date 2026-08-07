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

namespace jbboehr\Yumemi\Tests\PHPStan;

use jbboehr\Yumemi\Exception\NonExactRootException;
use jbboehr\Yumemi\PHPStan\UnitExpression;
use jbboehr\Yumemi\PHPStan\UnitExpressionAlgebra;
use jbboehr\Yumemi\PHPStan\UnitExpressionParser;
use PHPUnit\Framework\TestCase;

/**
 * Direct, in-process coverage of the pure unit algebra shared by the native and Quantity layers.
 *
 * The Quantity/return-type assertions live in out-of-process PHPStan fixtures, which do not register
 * PCOV coverage; these exercise the same combination rules in-process.
 */
final class UnitExpressionAlgebraTest extends TestCase
{
    public function testConstructsNormalFormWithSharedExpressionIdentity(): void
    {
        $meter = $this->unit('meter');

        $normalForm = UnitExpression::fromNormalForm($meter->expr, $meter->dimension);

        $this->assertSame($meter->expr, $normalForm->expr);
        $this->assertSame($meter->expr, $normalForm->normalizedExpr);
        $this->assertSame($meter->expr, $normalForm->symbolicExpr);
        $this->assertSame('meter', $normalForm->displayString);
        $this->assertSame($meter->dimension, $normalForm->dimension);
    }

    public function testMultiplyCombinesUnitsAndDimensions(): void
    {
        $result = UnitExpressionAlgebra::multiply($this->unit('meter'), $this->unit('second'));

        $this->assertSame('meter * second', $result->displayString);
        $this->assertSame($this->unit('meter * second')->dimension->toString(), $result->dimension->toString());
        $this->assertTrue($result->equivalent($this->unit('meter * second')));
    }

    public function testDivideCombinesUnitsAndDimensions(): void
    {
        $result = UnitExpressionAlgebra::divide($this->unit('meter'), $this->unit('second'));

        $this->assertSame('meter / second', $result->displayString);
        $this->assertSame('length / time', $result->dimension->toString());
        $this->assertTrue($result->equivalent($this->unit('meter / second')));
    }

    public function testPowerRaisesTheExponent(): void
    {
        $result = UnitExpressionAlgebra::power($this->unit('meter'), 2);

        $this->assertSame('meter ^ 2', $result->displayString);
        $this->assertTrue($result->equivalent($this->unit('meter ^ 2')));
    }

    public function testNegativePowerInverts(): void
    {
        $result = UnitExpressionAlgebra::power($this->unit('meter'), -1);

        $this->assertSame('1 / meter', $result->displayString);
    }

    public function testInvertMatchesNegativeFirstPower(): void
    {
        $meter = $this->unit('meter');
        $inverted = UnitExpressionAlgebra::invert($meter);
        $power = UnitExpressionAlgebra::power($meter, -1);

        $this->assertSame($power->displayString, $inverted->displayString);
        $this->assertTrue($inverted->equivalent($power));
    }

    public function testUnitTimesItsInverseIsDimensionless(): void
    {
        $meter = $this->unit('meter');
        $result = UnitExpressionAlgebra::multiply($meter, UnitExpressionAlgebra::invert($meter));
        $cancelled = UnitExpressionAlgebra::divide($meter, $meter);

        $this->assertSame($cancelled->dimension->toString(), $result->dimension->toString());
        $this->assertTrue($result->equivalent($cancelled));
    }

    public function testCompoundFactorsCancelUnderNormalization(): void
    {
        // (meter / second) * second is normalized-equivalent to meter.
        $result = UnitExpressionAlgebra::multiply($this->unit('meter / second'), $this->unit('second'));

        $this->assertTrue($result->equivalent($this->unit('meter')));
        $this->assertTrue($result->sameDimension($this->unit('meter')));
    }

    public function testRootExtractsExactSymbolicPower(): void
    {
        $result = UnitExpressionAlgebra::root($this->unit('centimeter ^ 2'), 2);

        $this->assertSame('1/100 * meter', $result->displayString);
        $this->assertSame('centimeter', $result->symbolicExpr->toString());
        $this->assertSame('length', $result->dimension->toString());
        $this->assertTrue($result->equivalent($this->unit('centimeter')));
    }

    public function testPowerThenRootRetainsSymbolicWitness(): void
    {
        $kilometer = $this->unit('kilometer');
        $result = UnitExpressionAlgebra::root(UnitExpressionAlgebra::power($kilometer, 2), 2);

        $this->assertSame('1000 * meter', $result->displayString);
        $this->assertSame('kilometer', $result->symbolicExpr->toString());
    }

    public function testRootRejectsDifferentSymbolsThatNormalizeToAPerfectPower(): void
    {
        $this->expectException(NonExactRootException::class);

        UnitExpressionAlgebra::root($this->unit('kilometer * millimeter'), 2);
    }

    public function testExplicitNormalFormAllowsDefinitionallyExactRoot(): void
    {
        $unit = $this->unit('kilometer * millimeter');
        $normalized = UnitExpression::fromNormalForm($unit->normalizedExpr, $unit->dimension);

        $this->assertSame('meter', UnitExpressionAlgebra::root($normalized, 2)->displayString);
    }

    private function unit(string $unit): UnitExpression
    {
        $parsed = (new UnitExpressionParser())->parse($unit);
        $this->assertTrue($parsed->isOk(), $parsed->errorMessage() ?? $unit);

        return $parsed->expression();
    }
}
