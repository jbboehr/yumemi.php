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

namespace jbboehr\Yumemi\Tests\Analyzer;

use jbboehr\Yumemi\Analyzer\AstConverter;
use jbboehr\Yumemi\Analyzer\UnitResolver;
use jbboehr\Yumemi\Exception\UnsupportedSyntaxException;
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
