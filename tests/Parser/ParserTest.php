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
use jbboehr\Yumemi\Parser\AstNode;
use jbboehr\Yumemi\Parser\Parser;
use PHPUnit\Framework\TestCase;

final class ParserTest extends TestCase
{
    public function testIdentifier(): void
    {
        $this->assertAstEquals(new Ast\Identifier('meter'), Parser::parseString('meter'));
    }

    public function testExplicitMultiplication(): void
    {
        $this->assertAstEquals(
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

        $this->assertAstEquals($expected, Parser::parseString('meter.second'));
        $this->assertAstEquals($expected, Parser::parseString('meter · second'));
    }

    public function testImplicitMultiplication(): void
    {
        $this->assertAstEquals(
            new Ast\Mul(
                new Ast\Identifier('meter'),
                new Ast\Identifier('second'),
            ),
            Parser::parseString('meter second'),
        );
    }

    public function testDivision(): void
    {
        $this->assertAstEquals(
            new Ast\Div(
                new Ast\Identifier('meter'),
                new Ast\Identifier('second'),
            ),
            Parser::parseString('meter / second'),
        );
    }

    public function testExplicitMultiplicationAndDivisionAssociateLeft(): void
    {
        $this->assertAstEquals(
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

        $this->assertAstEquals($expected, Parser::parseString('meter / (second * kilogram)'));
        $this->assertAstEquals($expected, Parser::parseString('meter / (second kilogram)'));
    }

    public function testImplicitMultiplicationAndDivisionAssociateLeft(): void
    {
        $this->assertAstEquals(
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
        $this->assertAstEquals(
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
        $this->assertAstEquals(
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

    public function testAdditionAndSubtractionRemainDistinctAstNodes(): void
    {
        $this->assertAstEquals(
            new Ast\Add(
                new Ast\Identifier('meter'),
                new Ast\Identifier('second'),
            ),
            Parser::parseString('meter + second'),
        );
        $this->assertAstEquals(
            new Ast\Sub(
                new Ast\Identifier('meter'),
                new Ast\Identifier('second'),
            ),
            Parser::parseString('meter - second'),
        );
    }

    public function testPower(): void
    {
        $this->assertAstEquals(
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

        $this->assertAstEquals($expected, Parser::parseString('meter * second^2'));
        $this->assertAstEquals($expected, Parser::parseString('meter second^2'));
    }

    public function testNegativePower(): void
    {
        $this->assertAstEquals(
            new Ast\Pow(
                new Ast\Identifier('second'),
                new Ast\Integer_('-2'),
            ),
            Parser::parseString('second^-2'),
        );
    }

    public function testRepeatedNegationTogglesNumericSign(): void
    {
        $this->assertAstEquals(
            new Ast\Pow(
                new Ast\Identifier('meter'),
                new Ast\Integer_('2'),
            ),
            Parser::parseString('meter^--2'),
        );

        $this->assertAstEquals(new Ast\Integer_('5'), Parser::parseString('--5'));
        $this->assertAstEquals(new Ast\Float_('1.25'), Parser::parseString('--1.25'));
    }

    public function testNegatesNonnumericExpressionsByMultiplyingByNegativeOne(): void
    {
        $this->assertAstEquals(
            new Ast\Mul(
                new Ast\Integer_('-1'),
                new Ast\Identifier('meter'),
            ),
            Parser::parseString('-meter'),
        );
    }

    public function testAffineOriginMayBeNegative(): void
    {
        $this->assertAstEquals(
            new Ast\At(
                new Ast\Identifier('kelvin'),
                new Ast\Float_('-273.15'),
            ),
            Parser::parseString('kelvin @ -273.15'),
        );
    }

    public function testParenthesizedPower(): void
    {
        $this->assertAstEquals(
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

    public function testNegativeNumericBaseOfPowerIsParenthesizedForRoundTrip(): void
    {
        // A negative numeric literal base must survive the canonical round trip.
        // Exponentiation binds more tightly than the leading sign, so an unparenthesized
        // "-5 ^ 2" reparses as "-(5 ^ 2)" and silently changes meaning.
        $integerBase = Parser::parseString('(-5)^2');
        $this->assertInstanceOf(Ast\Pow::class, $integerBase);
        $this->assertInstanceOf(Ast\Integer_::class, $integerBase->left);
        $this->assertSame('((-5) ^ 2)', $integerBase->toString());
        $this->assertSame(
            $integerBase->toString(),
            Parser::parseString($integerBase->toString())->toString(),
        );

        // The same rule applies to a negative floating-point base, independently of a
        // negative exponent, which never needs added parentheses.
        $floatBase = Parser::parseString('(-5.5)^(-2)');
        $this->assertInstanceOf(Ast\Pow::class, $floatBase);
        $this->assertInstanceOf(Ast\Float_::class, $floatBase->left);
        $this->assertSame('((-5.5) ^ -2)', $floatBase->toString());
        $this->assertSame(
            $floatBase->toString(),
            Parser::parseString($floatBase->toString())->toString(),
        );
    }

    public function testParserNodesRetainHalfOpenByteSpans(): void
    {
        $ast = Parser::parseString('  meter / μs^2');

        $this->assertInstanceOf(Ast\Div::class, $ast);
        $this->assertSpan($ast, 2, 15);
        $this->assertInstanceOf(Ast\Identifier::class, $ast->left);
        $this->assertSpan($ast->left, 2, 7);
        $this->assertInstanceOf(Ast\Pow::class, $ast->right);
        $this->assertSpan($ast->right, 10, 15);
        $this->assertInstanceOf(Ast\Identifier::class, $ast->right->left);
        $this->assertSpan($ast->right->left, 10, 13);
        $this->assertInstanceOf(Ast\Integer_::class, $ast->right->right);
        $this->assertSpan($ast->right->right, 14, 15);
    }

    private function assertAstEquals(Ast $expected, Ast $actual): void
    {
        $this->assertSame($expected::class, $actual::class);
        $this->assertSame($expected->toString(), $actual->toString());
    }

    private function assertSpan(AstNode $ast, int $start, int $end): void
    {
        $this->assertNotNull($ast->span);
        $this->assertSame($start, $ast->span->start);
        $this->assertSame($end, $ast->span->end);
    }
}
