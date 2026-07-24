<?php

namespace jbboehr\IudexMensurarumMysteriorum\Tests\Analyzer;

use jbboehr\IudexMensurarumMysteriorum\Analyzer\UnitNormalizer;
use jbboehr\IudexMensurarumMysteriorum\Analyzer\ExprReducer;
use jbboehr\IudexMensurarumMysteriorum\Expr\Compound;
use jbboehr\IudexMensurarumMysteriorum\Expr\Constant;
use jbboehr\IudexMensurarumMysteriorum\Expr\Term;
use jbboehr\IudexMensurarumMysteriorum\Expr\Unit;
use PHPUnit\Framework\TestCase;

final class UnitNormalizerTest extends TestCase
{
    public function testDerivedUnitNormalizesToBaseDefinition(): void
    {
        $meter = new Unit('meter');
        $kilometer = new Unit('kilometer', new Compound([
            new Constant(1000),
            $meter,
        ]));

        $normalizer = new UnitNormalizer();

        $this->assertSame('1000 * meter', $normalizer->normalize($kilometer)->toString());
    }

    public function testDerivedUnitPowersNormalizeToBaseDefinition(): void
    {
        $meter = new Unit('meter');
        $kilometer = new Unit('kilometer', new Compound([
            new Constant(1000),
            $meter,
        ]));

        $normalizer = new UnitNormalizer();

        $expr = $normalizer->normalize(new Term($kilometer, 2));

        $this->assertSame('1000000 * meter ^ 2', $expr->toString());
    }

    public function testCompoundDerivedUnitsNormalizeAndCancel(): void
    {
        $meter = new Unit('meter');
        $second = new Unit('second');
        $minute = new Unit('minute', new Compound([
            new Constant(60),
            $second,
        ]));

        $normalizer = new UnitNormalizer();

        $expr = $normalizer->normalize(new Compound([
            $meter,
            new Term($minute, -1),
            $second,
        ]));

        $this->assertSame('1/60 * meter', $expr->toString());
    }

    public function testInitialReductionPreservesDefinitionsForSubstitution(): void
    {
        $meter = new Unit('meter');
        $kilometer = new Unit('kilometer', new Compound([
            new Constant(1000),
            $meter,
        ]));

        $normalizer = new UnitNormalizer();

        $this->assertSame('1000 * meter', $normalizer->normalize(ExprReducer::reduce($kilometer))->toString());
    }
}
