<?php

namespace jbboehr\IudexMensurarumMysteriorum\Tests\Analyzer;

use jbboehr\IudexMensurarumMysteriorum\Analyzer\AstConverter;
use jbboehr\IudexMensurarumMysteriorum\Analyzer\UnitResolver;
use jbboehr\IudexMensurarumMysteriorum\Exception\UnsupportedSyntaxException;
use jbboehr\IudexMensurarumMysteriorum\Expr\Unit;
use jbboehr\IudexMensurarumMysteriorum\Parser\Parser;
use jbboehr\IudexMensurarumMysteriorum\Registry\UnitRegistry;
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
