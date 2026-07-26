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
