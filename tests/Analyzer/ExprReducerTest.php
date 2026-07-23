<?php

namespace jbboehr\IudexMensurarumMysteriorum\Tests\Analyzer;

use jbboehr\IudexMensurarumMysteriorum\Expr\Compound;
use jbboehr\IudexMensurarumMysteriorum\Expr\Constant;
use jbboehr\IudexMensurarumMysteriorum\Expr\Term;
use jbboehr\IudexMensurarumMysteriorum\Expr\Unit;
use jbboehr\IudexMensurarumMysteriorum\Number\Rational;
use PHPUnit\Framework\TestCase;

final class ExprReducerTest extends TestCase
{
    public function testConstantsReduceToRational(): void
    {
        $expr = (new Constant(2))
            ->mul(new Constant(3))
            ->div(new Constant(4));

        $this->assertSame('3/2', $expr->toString());
    }

    public function testIdenticalUnitsCombine(): void
    {
        $meter = new Unit('meter');

        $expr = $meter->mul($meter);

        $this->assertSame('meter ^ 2', $expr->toString());
    }

    public function testInverseUnitsCancel(): void
    {
        $meter = new Unit('meter');
        $second = new Unit('second');

        $expr = $meter
            ->div($second)
            ->mul($second);

        $this->assertSame('meter', $expr->toString());
    }

    public function testCompoundPowersDistribute(): void
    {
        $meter = new Unit('meter');
        $second = new Unit('second');

        $expr = (new Compound([
            $meter,
            new Term($second, -1),
        ]))->pow(2);

        $this->assertSame('meter ^ 2 * second ^ -2', $expr->toString());
    }

    public function testUnitOrderingIsDeterministic(): void
    {
        $meter = new Unit('meter');
        $second = new Unit('second');

        $expr = $second->mul($meter);

        $this->assertSame('meter * second', $expr->toString());
    }

    public function testReducedConstantOneIsKeptForDimensionlessExpressions(): void
    {
        $meter = new Unit('meter');

        $expr = $meter->div($meter);

        $this->assertSame('1', $expr->toString());
    }

    public function testNegativeRationalPowersInvert(): void
    {
        $expr = (new Constant(new Rational(2, 3)))->pow(-2);

        $this->assertSame('9/4', $expr->toString());
    }
}
