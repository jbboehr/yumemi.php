<?php

namespace jbboehr\Yumemi\Tests\Formatter;

use jbboehr\Yumemi\Exception\IncompatibleUnitException;
use jbboehr\Yumemi\Expr\Compound;
use jbboehr\Yumemi\Expr\Constant;
use jbboehr\Yumemi\Expr\Term;
use jbboehr\Yumemi\Expr\Unit;
use jbboehr\Yumemi\Formatter\ExprFormatter;
use jbboehr\Yumemi\Units;
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

    public function testDisplayFormDiffersFromStructuralToStringForQuotients(): void
    {
        $expr = Units::default()->parse('meter / second');

        $this->assertSame('meter * second ^ -1', $expr->toString());
        $this->assertSame('meter / second', ExprFormatter::format($expr));
    }

    public function testIncompatibleUnitExceptionUsesDisplayForm(): void
    {
        $units = Units::default();
        $from = $units->parse('meter / second');
        $to = $units->parse('kilogram');

        $exception = IncompatibleUnitException::create($from, $to);

        $this->assertStringContainsString('meter / second', $exception->getMessage());
        $this->assertStringNotContainsString('second ^ -1', $exception->getMessage());
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
