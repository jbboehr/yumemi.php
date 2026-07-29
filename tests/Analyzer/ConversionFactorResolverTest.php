<?php

/**
 * +--------------------------------------------------------------------------------------------------------------+
 * |        *                 .                         *                  .                         *            |
 * |   .              *                      .                    *                      .                        |
 * |             .                 .                  *                         .                 *               |
 * -      *                    .             *                    .                         .                     -
 *
 *                               Iudex Mensurarum Mysticarum『夢見』〜ＹＵＭＥＭＩ〜
 *
 * -                                          .----------------.                                                  -
 * |                                      .--'        __        '--.                                              |
 * |                                  .--'          .'  '.          '--.                                          |
 * |                             .---'            .'      '.            '---.                                     |
 * +--------------------------------------------------------------------------------------------------------------+
 *
 * Copyright (c) anno Domini nostri Jesu Christi MMXXVI, John Boehr & contributors
 *
 * SPDX-License-Identifier: AGPL-3.0-only WITH romic-exception
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License version 3,
 * as published by the Free Software Foundation, together with the Romic
 * Exception (an additional permission under section 7 of that license).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * and the Romic Exception along with this program.  If not, see
 * <http://www.gnu.org/licenses/> and the LICENSE_EXCEPTION file.
 */

namespace jbboehr\Yumemi\Tests\Analyzer;

use jbboehr\Yumemi\Analyzer\ConversionFactorResolver;
use jbboehr\Yumemi\Analyzer\UnitNormalizer;
use jbboehr\Yumemi\Exception\IncompatibleUnitException;
use jbboehr\Yumemi\Expr\Product;
use jbboehr\Yumemi\Expr\Constant;
use jbboehr\Yumemi\Expr\Power;
use jbboehr\Yumemi\Expr\Unit;
use PHPUnit\Framework\TestCase;

final class ConversionFactorResolverTest extends TestCase
{
    public function testCompatibleUnitsResolveScaleFactor(): void
    {
        $meter = new Unit('meter');
        $kilometer = new Unit('kilometer', new Product([
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
        $kilometer = new Unit('kilometer', new Product([
            new Constant(1000),
            $meter,
        ]));
        $minute = new Unit('minute', new Product([
            new Constant(60),
            $second,
        ]));

        $resolver = new ConversionFactorResolver(new UnitNormalizer());

        $metersPerSecond = new Product([
            $meter,
            new Power($second, -1),
        ]);
        $kilometersPerMinute = new Product([
            $kilometer,
            new Power($minute, -1),
        ]);

        $this->assertTrue($resolver->areCompatible($metersPerSecond, $kilometersPerMinute));
        $this->assertSame('50/3', $resolver->resolve($kilometersPerMinute, $metersPerSecond)->toString());
        $this->assertSame('3/50', $resolver->resolve($metersPerSecond, $kilometersPerMinute)->toString());
    }
}
