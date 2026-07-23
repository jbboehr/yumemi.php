<?php

namespace jbboehr\IudexMensurarumMysteriorum\Tests\Parser;

use jbboehr\IudexMensurarumMysteriorum\Parser\Ast;
use jbboehr\IudexMensurarumMysteriorum\Parser\Parser;
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
