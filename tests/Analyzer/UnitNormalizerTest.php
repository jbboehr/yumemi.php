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

use jbboehr\Yumemi\Analyzer\UnitNormalizer;
use jbboehr\Yumemi\Analyzer\ExprReducer;
use jbboehr\Yumemi\Expr\Compound;
use jbboehr\Yumemi\Expr\Constant;
use jbboehr\Yumemi\Expr\Term;
use jbboehr\Yumemi\Expr\Unit;
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
