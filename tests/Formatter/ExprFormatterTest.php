<?php

namespace jbboehr\IudexMensurarumMysteriorum\Tests\Formatter;

use jbboehr\IudexMensurarumMysteriorum\Expr\Compound;
use jbboehr\IudexMensurarumMysteriorum\Expr\Constant;
use jbboehr\IudexMensurarumMysteriorum\Expr\Term;
use jbboehr\IudexMensurarumMysteriorum\Expr\Unit;
use jbboehr\IudexMensurarumMysteriorum\Formatter\ExprFormatter;
use PHPUnit\Framework\TestCase;

final class ExprFormatterTest extends TestCase
{
    public function testFormatsUnitsWithDenominator(): void
    {
        $expr = new Compound([
            new Constant(3),
            new Unit('meter'),
            new Term(new Unit('second'), -1),
        ]);

        $this->assertSame('3 * meter / second', ExprFormatter::format($expr));
    }

    public function testFormatsMultipleDenominatorTermsWithParentheses(): void
    {
        $expr = new Compound([
            new Unit('centimeter'),
            new Term(new Unit('foot'), -1),
            new Term(new Unit('second'), -1),
        ]);

        $this->assertSame('centimeter / (foot * second)', ExprFormatter::format($expr));
    }

    public function testFormatsPositivePowers(): void
    {
        $expr = (new Unit('meter'))->pow(2);

        $this->assertSame('meter ^ 2', ExprFormatter::format($expr));
    }
}
