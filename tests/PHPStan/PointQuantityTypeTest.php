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

namespace jbboehr\Yumemi\Tests\PHPStan;

use jbboehr\Yumemi\PHPStan\PointQuantityType;
use jbboehr\Yumemi\PHPStan\UnitExpressionParser;
use jbboehr\Yumemi\PointQuantity;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\IntegerType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\VerbosityLevel;

final class PointQuantityTypeTest extends PHPStanTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        self::getContainer();
    }

    public function testDescribesTheCoordinateUnit(): void
    {
        $this->assertSame(
            "PointQuantity<'celsius'>",
            $this->point('celsius')->describe(VerbosityLevel::precise()),
        );
    }

    public function testEquivalentCoordinateAliasesAreEqualAndAssignable(): void
    {
        $celsius = $this->point('celsius');
        $degreeCelsius = $this->point('degree_Celsius');

        $this->assertTrue($celsius->equals($degreeCelsius));
        $this->assertTrue($celsius->accepts($degreeCelsius, true)->yes());
        $this->assertTrue($celsius->isSuperTypeOf($degreeCelsius)->yes());
    }

    public function testSameDimensionDifferentCoordinateScaleIsNotAssignable(): void
    {
        $celsius = $this->point('celsius');
        $kelvin = $this->point('kelvin');

        $this->assertFalse($celsius->equals($kelvin));

        $accepts = $celsius->accepts($kelvin, true);
        $this->assertTrue($accepts->no());
        $this->assertStringContainsString('coordinate scales differ', implode("\n", $accepts->reasons));
        $this->assertTrue($celsius->isSuperTypeOf($kelvin)->no());
    }

    public function testUnbrandedPointQuantityIsNotAssignable(): void
    {
        $celsius = $this->point('celsius');
        $plain = new ObjectType(PointQuantity::class);

        $accepts = $celsius->accepts($plain, true);
        $this->assertTrue($accepts->no());
        $this->assertStringContainsString('without a static coordinate unit', implode("\n", $accepts->reasons));
        $this->assertTrue($celsius->isSuperTypeOf($plain)->no());
    }

    public function testBrandedPointQuantityIsAssignableToUnbranded(): void
    {
        $plain = new ObjectType(PointQuantity::class);

        $this->assertTrue($plain->isSuperTypeOf($this->point('celsius'))->yes());
    }

    public function testNonPointQuantityTypeIsNotAssignable(): void
    {
        $this->assertTrue($this->point('celsius')->accepts(new IntegerType(), true)->no());
    }

    private function point(string $unit): PointQuantityType
    {
        $parsed = (new UnitExpressionParser())->parsePoint($unit);
        $this->assertTrue($parsed->isOk(), $parsed->errorMessage() ?? $unit);

        return new PointQuantityType($parsed->expression());
    }
}
