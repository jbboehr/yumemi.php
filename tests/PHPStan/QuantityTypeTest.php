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

use jbboehr\Yumemi\PHPStan\QuantityType;
use jbboehr\Yumemi\PHPStan\UnitExpressionParser;
use jbboehr\Yumemi\Quantity;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\IntegerType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\VerbosityLevel;

/**
 * In-process coverage of the branded Quantity object type's assignment/super-type semantics.
 *
 * These paths are otherwise exercised only through out-of-process PHPStan fixtures, which register no
 * PCOV coverage. Extends PHPStanTestCase so the underlying ObjectType has a booted reflection provider.
 */
final class QuantityTypeTest extends PHPStanTestCase
{
    public function testDescribesTheUnit(): void
    {
        $this->assertSame("Quantity<'meter / second'>", $this->quantity('meter / second')->describe(VerbosityLevel::precise()));
    }

    public function testEquivalentUnitsAreEqualAndAssignable(): void
    {
        $km = $this->quantity('kilometer');
        $thousandMeters = $this->quantity('1000 * meter');

        $this->assertTrue($km->equals($thousandMeters));
        $this->assertTrue($km->accepts($thousandMeters, true)->yes());
        $this->assertTrue($km->isSuperTypeOf($thousandMeters)->yes());
    }

    public function testSameDimensionDifferentScaleIsNotAssignable(): void
    {
        $meters = $this->quantity('meter');
        $feet = $this->quantity('foot');

        $this->assertFalse($meters->equals($feet));

        $accepts = $meters->accepts($feet, true);
        $this->assertTrue($accepts->no());
        $this->assertStringContainsString('normalized forms differ', implode("\n", $accepts->reasons));
        $this->assertTrue($meters->isSuperTypeOf($feet)->no());
    }

    public function testUnbrandedQuantityIsNotAssignable(): void
    {
        $meters = $this->quantity('meter');
        $plain = new ObjectType(Quantity::class);

        $accepts = $meters->accepts($plain, true);
        $this->assertTrue($accepts->no());
        $this->assertStringContainsString('keep the unit annotation', implode("\n", $accepts->reasons));
        $this->assertTrue($meters->isSuperTypeOf($plain)->no());
    }

    public function testBrandedQuantityIsAssignableToUnbranded(): void
    {
        // The unbranded Quantity object type is a super-type of any branded Quantity.
        $plain = new ObjectType(Quantity::class);

        $this->assertTrue($plain->isSuperTypeOf($this->quantity('meter'))->yes());
    }

    public function testNonQuantityTypeIsNotAssignable(): void
    {
        $this->assertTrue($this->quantity('meter')->accepts(new IntegerType(), true)->no());
    }

    private function quantity(string $unit): QuantityType
    {
        $parsed = (new UnitExpressionParser())->parse($unit);
        $this->assertTrue($parsed->isOk(), $parsed->errorMessage() ?? $unit);

        return new QuantityType($parsed->expression());
    }
}
