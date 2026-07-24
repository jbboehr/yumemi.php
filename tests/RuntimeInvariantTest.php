<?php

namespace jbboehr\IudexMensurarumMysteriorum\Tests;

use jbboehr\IudexMensurarumMysteriorum\Analyzer\ExprReducer;
use jbboehr\IudexMensurarumMysteriorum\Exception\IncompatibleUnitException;
use jbboehr\IudexMensurarumMysteriorum\Expr;
use jbboehr\IudexMensurarumMysteriorum\Expr\Compound;
use jbboehr\IudexMensurarumMysteriorum\Expr\Constant;
use jbboehr\IudexMensurarumMysteriorum\Expr\Term;
use jbboehr\IudexMensurarumMysteriorum\Expr\Unit;
use jbboehr\IudexMensurarumMysteriorum\Number\Rational;
use jbboehr\IudexMensurarumMysteriorum\Units;
use PHPUnit\Framework\TestCase;

final class RuntimeInvariantTest extends TestCase
{
    public function testConversionFactorsAreReciprocal(): void
    {
        $units = Units::default();

        foreach (self::compatibleUnitPairs() as [$left, $right]) {
            $leftToRight = $units->conversionFactor($left, $right);
            $rightToLeft = $units->conversionFactor($right, $left);

            $this->assertSame('1', $leftToRight->mul($rightToLeft)->toString(), $left . ' <-> ' . $right);
        }
    }

    public function testConvertingThereAndBackPreservesValue(): void
    {
        $units = Units::default();
        $value = new Rational(123, 7);

        foreach (self::compatibleUnitPairs() as [$left, $right]) {
            $converted = $units->convert($value, $left, $right);
            $roundTripped = $units->convert($converted, $right, $left);

            $this->assertSame($value->toString(), $roundTripped->toString(), $left . ' <-> ' . $right);
        }
    }

    public function testCompatibilityMatchesConversionFactorResolution(): void
    {
        $units = Units::default();

        foreach (self::compatibleUnitPairs() as [$left, $right]) {
            $this->assertTrue($units->compatible($left, $right), $left . ' should be compatible with ' . $right);
            $units->conversionFactor($left, $right);
            $this->addToAssertionCount(1);
        }

        foreach (self::incompatibleUnitPairs() as [$left, $right]) {
            $this->assertFalse($units->compatible($left, $right), $left . ' should be incompatible with ' . $right);

            try {
                $units->conversionFactor($left, $right);
                self::fail($left . ' unexpectedly converted to ' . $right);
            } catch (IncompatibleUnitException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function testExplicitQuantityConversionRoundTripsThroughOriginalUnit(): void
    {
        $units = Units::default();
        $value = new Rational(355, 113);

        foreach (self::compatibleUnitPairs() as [$left, $right]) {
            $converted = $units->quantity($value, $left)->to($right);

            $this->assertSame($value->toString(), $converted->valueIn($left)->toString(), $left . ' -> ' . $right);
        }
    }

    public function testNormalizeIsIdempotent(): void
    {
        $units = Units::default();

        foreach (self::normalizationInputs() as $input) {
            $normalized = $units->normalize($input);
            $again = $units->normalize($normalized);

            $this->assertSame($normalized->toString(), $again->toString(), $input);
        }
    }

    public function testQuantitySimplifyIsIdempotent(): void
    {
        $units = Units::default();
        $quantities = [
            $units->quantity(2, 'centimeter / second'),
            $units->quantity(3, 'newton * second / meter'),
            $units->quantity(new Rational(981, 100), 'kilometer / hour'),
            $units->quantity(120, 'liter / minute'),
        ];

        foreach ($quantities as $quantity) {
            $simplified = $quantity->simplify();
            $again = $simplified->simplify();

            $this->assertSame($simplified->valueToString(), $again->valueToString(), $quantity->toString());
            $this->assertSame($simplified->unitToString(), $again->unitToString(), $quantity->toString());
            $this->assertSame($simplified->toString(), $again->toString(), $quantity->toString());
        }
    }

    public function testExprReducerIsIdempotent(): void
    {
        foreach (self::reductionInputs() as $expr) {
            $reduced = ExprReducer::reduce($expr);
            $again = ExprReducer::reduce($reduced);

            $this->assertSame($reduced->toString(), $again->toString(), $expr->toString());
        }
    }

    /**
     * @return list<array{string, string}>
     */
    private static function compatibleUnitPairs(): array
    {
        return [
            ['meter', 'centimeter'],
            ['meter', 'foot'],
            ['mile', 'kilometer'],
            ['liter', 'meter^3'],
            ['meter / second', 'kilometer / hour'],
            ['newton', 'kilogram * meter / second^2'],
            ['joule', 'watt * second'],
            ['pascal', 'newton / meter^2'],
            ['volt', 'watt / ampere'],
            ['tesla', 'weber / meter^2'],
        ];
    }

    /**
     * @return list<array{string, string}>
     */
    private static function incompatibleUnitPairs(): array
    {
        return [
            ['meter', 'second'],
            ['newton', 'joule'],
            ['pascal', 'watt'],
            ['liter', 'kilogram'],
            ['ampere', 'volt'],
        ];
    }

    /**
     * @return list<string>
     */
    private static function normalizationInputs(): array
    {
        return [
            'kilometer / minute',
            'newton * second / meter',
            'liter / minute',
            'centimeter / (foot * second)',
            'volt * ampere / watt',
            'weber / meter^2',
        ];
    }

    /**
     * @return list<Expr>
     */
    private static function reductionInputs(): array
    {
        $meter = new Unit('meter');
        $second = new Unit('second');
        $ampere = new Unit('ampere');

        return [
            new Compound([
                new Constant(2),
                new Constant(3),
                $meter,
                new Term($meter, -1),
            ]),
            new Compound([
                $second,
                $meter,
                new Term($second, -2),
                new Term($ampere, 3),
                new Term($ampere, -1),
            ]),
            new Term(new Compound([
                new Constant(new Rational(2, 3)),
                $meter,
                new Term($second, -1),
            ]), -2),
            new Compound([
                new Term(new Compound([$meter, $second]), 2),
                new Term($meter, -1),
                new Term($second, -2),
            ]),
        ];
    }
}
