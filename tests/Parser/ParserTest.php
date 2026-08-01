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

namespace jbboehr\Yumemi\Tests\Parser;

use jbboehr\Yumemi\Parser\Ast;
use jbboehr\Yumemi\Parser\Parser;
use PHPUnit\Framework\TestCase;

final class ParserTest extends TestCase
{
    public function testIdentifier(): void
    {
        $this->assertEquals(new Ast\Identifier('meter'), Parser::parseString('meter'));
    }

    public function testExplicitMultiplication(): void
    {
        $this->assertEquals(
            new Ast\Mul(
                new Ast\Identifier('meter'),
                new Ast\Identifier('second'),
            ),
            Parser::parseString('meter * second'),
        );
    }

    public function testDotMultiplication(): void
    {
        $expected = new Ast\Mul(
            new Ast\Identifier('meter'),
            new Ast\Identifier('second'),
        );

        $this->assertEquals($expected, Parser::parseString('meter.second'));
        $this->assertEquals($expected, Parser::parseString('meter · second'));
    }

    public function testImplicitMultiplication(): void
    {
        $this->assertEquals(
            new Ast\Mul(
                new Ast\Identifier('meter'),
                new Ast\Identifier('second'),
            ),
            Parser::parseString('meter second'),
        );
    }

    public function testDivision(): void
    {
        $this->assertEquals(
            new Ast\Div(
                new Ast\Identifier('meter'),
                new Ast\Identifier('second'),
            ),
            Parser::parseString('meter / second'),
        );
    }

    public function testExplicitMultiplicationAndDivisionAssociateLeft(): void
    {
        $this->assertEquals(
            new Ast\Mul(
                new Ast\Div(
                    new Ast\Identifier('meter'),
                    new Ast\Identifier('second'),
                ),
                new Ast\Identifier('kilogram'),
            ),
            Parser::parseString('meter / second * kilogram'),
        );
    }

    public function testParenthesesOverrideMultiplicationAndDivisionPrecedence(): void
    {
        $expected = new Ast\Div(
            new Ast\Identifier('meter'),
            new Ast\Mul(
                new Ast\Identifier('second'),
                new Ast\Identifier('kilogram'),
            ),
        );

        $this->assertEquals($expected, Parser::parseString('meter / (second * kilogram)'));
        $this->assertEquals($expected, Parser::parseString('meter / (second kilogram)'));
    }

    public function testImplicitMultiplicationAndDivisionAssociateLeft(): void
    {
        $this->assertEquals(
            new Ast\Mul(
                new Ast\Div(
                    new Ast\Identifier('meter'),
                    new Ast\Identifier('second'),
                ),
                new Ast\Identifier('kilogram'),
            ),
            Parser::parseString('meter / second kilogram'),
        );
    }

    public function testImplicitMultiplicationBeforeDivisionAssociatesLeft(): void
    {
        $this->assertEquals(
            new Ast\Div(
                new Ast\Mul(
                    new Ast\Identifier('meter'),
                    new Ast\Identifier('kilogram'),
                ),
                new Ast\Identifier('second'),
            ),
            Parser::parseString('meter kilogram / second'),
        );
    }

    public function testRepeatedDivisionAssociatesLeft(): void
    {
        $this->assertEquals(
            new Ast\Div(
                new Ast\Div(
                    new Ast\Identifier('meter'),
                    new Ast\Identifier('second'),
                ),
                new Ast\Identifier('kilogram'),
            ),
            Parser::parseString('meter / second / kilogram'),
        );
    }

    public function testPower(): void
    {
        $this->assertEquals(
            new Ast\Pow(
                new Ast\Identifier('meter'),
                new Ast\Integer_('2'),
            ),
            Parser::parseString('meter^2'),
        );
    }

    public function testPowerBindsMoreTightlyThanMultiplication(): void
    {
        $expected = new Ast\Mul(
            new Ast\Identifier('meter'),
            new Ast\Pow(
                new Ast\Identifier('second'),
                new Ast\Integer_('2'),
            ),
        );

        $this->assertEquals($expected, Parser::parseString('meter * second^2'));
        $this->assertEquals($expected, Parser::parseString('meter second^2'));
    }

    public function testNegativePower(): void
    {
        $this->assertEquals(
            new Ast\Pow(
                new Ast\Identifier('second'),
                new Ast\Integer_('-2'),
            ),
            Parser::parseString('second^-2'),
        );
    }

    public function testRepeatedNegationTogglesNumericSign(): void
    {
        $this->assertEquals(
            new Ast\Pow(
                new Ast\Identifier('meter'),
                new Ast\Integer_('2'),
            ),
            Parser::parseString('meter^--2'),
        );

        $this->assertEquals(new Ast\Integer_('5'), Parser::parseString('--5'));
        $this->assertEquals(new Ast\Float_('1.25'), Parser::parseString('--1.25'));
    }

    public function testAffineOriginMayBeNegative(): void
    {
        $this->assertEquals(
            new Ast\At(
                new Ast\Identifier('kelvin'),
                new Ast\Float_('-273.15'),
            ),
            Parser::parseString('kelvin @ -273.15'),
        );
    }

    public function testParenthesizedPower(): void
    {
        $this->assertEquals(
            new Ast\Pow(
                new Ast\Div(
                    new Ast\Identifier('meter'),
                    new Ast\Identifier('second'),
                ),
                new Ast\Integer_('2'),
            ),
            Parser::parseString('(meter / second)^2'),
        );
    }
}
