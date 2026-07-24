<?php

namespace jbboehr\IudexMensurarumMysteriorum\Tests\Analyzer;

use jbboehr\IudexMensurarumMysteriorum\Expr\Compound;
use jbboehr\IudexMensurarumMysteriorum\Expr\Constant;
use jbboehr\IudexMensurarumMysteriorum\Expr\Term;
use jbboehr\IudexMensurarumMysteriorum\Expr\Unit;
use jbboehr\IudexMensurarumMysteriorum\Number\Rational;
use jbboehr\IudexMensurarumMysteriorum\Units;
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

    public function testEqualsIsStructuralAfterReduction(): void
    {
        $meter = new Unit('meter');
        $second = new Unit('second');

        $left = $meter->mul($second);
        $right = $second->mul($meter);

        $this->assertTrue($left->equals($right));
        $this->assertFalse($meter->equals($second));
        $this->assertTrue((new Constant(2))->mul($meter)->equals($meter->mul(new Constant(2))));
        $this->assertTrue((new Constant(new Rational(2, 4)))->equals(new Constant(new Rational(1, 2))));
    }

    public function testUnitEqualsIgnoresDefinitionAndUnitsContext(): void
    {
        $units = Units::default();
        $bound = $units->unit('meter');
        $bare = new Unit('meter');

        $this->assertInstanceOf(Unit::class, $bound);
        $this->assertTrue($bound->equals($bare));
        $this->assertTrue($bare->equals(new Unit('meter', new Constant(1))));
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
