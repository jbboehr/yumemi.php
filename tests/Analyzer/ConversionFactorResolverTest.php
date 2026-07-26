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

use jbboehr\Yumemi\Analyzer\ConversionFactorResolver;
use jbboehr\Yumemi\Analyzer\UnitNormalizer;
use jbboehr\Yumemi\Exception\IncompatibleUnitException;
use jbboehr\Yumemi\Expr\Compound;
use jbboehr\Yumemi\Expr\Constant;
use jbboehr\Yumemi\Expr\Term;
use jbboehr\Yumemi\Expr\Unit;
use PHPUnit\Framework\TestCase;

final class ConversionFactorResolverTest extends TestCase
{
    public function testCompatibleUnitsResolveScaleFactor(): void
    {
        $meter = new Unit('meter');
        $kilometer = new Unit('kilometer', new Compound([
            new Constant(1000),
            $meter,
        ]));

        $resolver = new ConversionFactorResolver(new UnitNormalizer());

        $this->assertSame('1000', $resolver->resolve($kilometer, $meter)->toString());
        $this->assertSame('1/1000', $resolver->resolve($meter, $kilometer)->toString());
    }

    public function testIncompatibleUnitsFail(): void
    {
        $resolver = new ConversionFactorResolver(new UnitNormalizer());

        $this->expectException(IncompatibleUnitException::class);
        $this->expectExceptionMessage('Dimensions: length vs time');

        $resolver->resolve(new Unit('meter'), new Unit('second'));
    }

    public function testCompoundCompatibleUnitsResolveScaleFactor(): void
    {
        $meter = new Unit('meter');
        $second = new Unit('second');
        $kilometer = new Unit('kilometer', new Compound([
            new Constant(1000),
            $meter,
        ]));
        $minute = new Unit('minute', new Compound([
            new Constant(60),
            $second,
        ]));

        $resolver = new ConversionFactorResolver(new UnitNormalizer());

        $metersPerSecond = new Compound([
            $meter,
            new Term($second, -1),
        ]);
        $kilometersPerMinute = new Compound([
            $kilometer,
            new Term($minute, -1),
        ]);

        $this->assertTrue($resolver->compatible($metersPerSecond, $kilometersPerMinute));
        $this->assertSame('50/3', $resolver->resolve($kilometersPerMinute, $metersPerSecond)->toString());
        $this->assertSame('3/50', $resolver->resolve($metersPerSecond, $kilometersPerMinute)->toString());
    }
}
