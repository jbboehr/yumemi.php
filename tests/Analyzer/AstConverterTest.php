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

use jbboehr\Yumemi\Analyzer\AstConverter;
use jbboehr\Yumemi\Analyzer\UnitResolver;
use jbboehr\Yumemi\Exception\UnsupportedSyntaxException;
use jbboehr\Yumemi\Exception\OverflowException;
use jbboehr\Yumemi\Expr\Unit;
use jbboehr\Yumemi\Parser\Parser;
use jbboehr\Yumemi\Registry\UnitRegistry;
use PHPUnit\Framework\TestCase;

final class AstConverterTest extends TestCase
{
    public function testConvertsUnitExpressionSyntax(): void
    {
        $converter = new AstConverter(new UnitResolver(UnitRegistry::defaults()));
        $expr = $converter->convert(Parser::parseString('2 kilometer / minute'));

        $this->assertSame('2 * kilometer * minute ^ -1', $expr->reduce()->toString());
    }

    public function testConvertsDecimalConstantsExactly(): void
    {
        $converter = new AstConverter(new UnitResolver(UnitRegistry::defaults()));
        $expr = $converter->convert(Parser::parseString('1.25 meter'));

        $this->assertSame('5/4 * meter', $expr->reduce()->toString());
    }

    public function testConvertsRepeatedlyNegatedExponentWithoutLosingTheUnit(): void
    {
        $converter = new AstConverter(new UnitResolver(UnitRegistry::defaults()));
        $expr = $converter->convert(Parser::parseString('meter^--2'));

        $this->assertSame('meter ^ 2', $expr->reduce()->toString());
    }

    public function testRejectsExponentBeyondSupportedRangeWithoutClamping(): void
    {
        $converter = new AstConverter(new UnitResolver(UnitRegistry::defaults()));

        $this->expectException(OverflowException::class);
        $this->expectExceptionMessage(str_repeat('9', 40));

        $converter->convert(Parser::parseString('meter^' . str_repeat('9', 40)));
    }

    public function testSymbolicModeKeepsBareUnitNames(): void
    {
        $ast = Parser::parseString('foot');
        $symbolic = AstConverter::symbolic()->convert($ast);
        $resolved = (new AstConverter(new UnitResolver(UnitRegistry::defaults())))->convert($ast);

        $this->assertInstanceOf(Unit::class, $symbolic);
        $this->assertInstanceOf(Unit::class, $resolved);
        $this->assertSame('foot', $symbolic->toString());
        $this->assertTrue($symbolic->isBase(), 'symbolic foot has no definition tree');
        $this->assertFalse($resolved->isBase(), 'resolved foot carries a catalog definition');
    }

    public function testSymbolicModePreservesUnknownIdentifiersAsUnits(): void
    {
        $expr = AstConverter::symbolic()->convert(Parser::parseString('widget'));

        $this->assertInstanceOf(Unit::class, $expr);
        $this->assertSame('widget', $expr->toString());
    }

    public function testRejectsUnsupportedSyntax(): void
    {
        $converter = new AstConverter(new UnitResolver(UnitRegistry::defaults()));

        $this->expectException(UnsupportedSyntaxException::class);
        $converter->convert(Parser::parseString('meter + second'));
    }
}
