<?php

namespace jbboehr\IudexMensurarumMysteriorum\Tests\Analyzer;

use jbboehr\IudexMensurarumMysteriorum\Analyzer\AstConverter;
use jbboehr\IudexMensurarumMysteriorum\Analyzer\UnitResolver;
use jbboehr\IudexMensurarumMysteriorum\Exception\UnsupportedSyntaxException;
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

    public function testRejectsUnsupportedSyntax(): void
    {
        $converter = new AstConverter(new UnitResolver(UnitRegistry::defaults()));

        $this->expectException(UnsupportedSyntaxException::class);
        $converter->convert(Parser::parseString('meter + second'));
    }
}
