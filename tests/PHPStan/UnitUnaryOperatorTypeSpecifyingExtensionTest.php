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

use jbboehr\Yumemi\PHPStan\UnitExpressionParser;
use jbboehr\Yumemi\PHPStan\UnitFloatType;
use jbboehr\Yumemi\PHPStan\UnitIntegerType;
use jbboehr\Yumemi\PHPStan\UnitUnaryOperatorTypeSpecifyingExtension;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\VerbosityLevel;
use PHPUnit\Framework\TestCase;

final class UnitUnaryOperatorTypeSpecifyingExtensionTest extends TestCase
{
    private UnitUnaryOperatorTypeSpecifyingExtension $extension;

    protected function setUp(): void
    {
        $this->extension = new UnitUnaryOperatorTypeSpecifyingExtension();
    }

    public function testSupportsUnaryPlusAndMinusOnUnitTypesOnly(): void
    {
        $meters = $this->unitFloat('meter');

        $this->assertTrue($this->extension->isOperatorSupported('-', $meters));
        $this->assertTrue($this->extension->isOperatorSupported('+', $meters));
        $this->assertFalse($this->extension->isOperatorSupported('~', $meters));
        $this->assertFalse($this->extension->isOperatorSupported('-', new IntegerType()));
    }

    public function testUnaryMinusAllowsFloatOverflow(): void
    {
        $meters = $this->unitInt('meter');
        $result = $this->extension->specifyType('-', $meters);

        $this->assertInstanceOf(BenevolentUnionType::class, $result);
        $this->assertSame("(unit_float<'meter'>|unit_int<'meter'>)", $result->describe(VerbosityLevel::precise()));
    }

    public function testUnaryMinusOverflowPromotionCanBeDisabled(): void
    {
        $meters = $this->unitInt('meter');
        $result = (new UnitUnaryOperatorTypeSpecifyingExtension(false))->specifyType('-', $meters);

        $this->assertInstanceOf(UnitIntegerType::class, $result);
        $this->assertSame("unit_int<'meter'>", $result->describe(VerbosityLevel::precise()));
    }

    public function testUnaryPlusPreservesUnitInteger(): void
    {
        $meters = $this->unitInt('meter');
        $result = $this->extension->specifyType('+', $meters);

        $this->assertInstanceOf(UnitIntegerType::class, $result);
        $this->assertSame("unit_int<'meter'>", $result->describe(VerbosityLevel::precise()));
    }

    public function testUnaryPlusPreservesUnitFloat(): void
    {
        $speed = $this->unitFloat('meter / second');
        $result = $this->extension->specifyType('+', $speed);

        $this->assertInstanceOf(UnitFloatType::class, $result);
        $this->assertSame("unit_float<'meter / second'>", $result->describe(VerbosityLevel::precise()));
    }

    private function unitInt(string $unit): UnitIntegerType
    {
        $parsed = (new UnitExpressionParser())->parse($unit);
        $this->assertTrue($parsed->isOk(), $parsed->errorMessage() ?? '');

        return new UnitIntegerType($parsed->expression());
    }

    private function unitFloat(string $unit): UnitFloatType
    {
        $parsed = (new UnitExpressionParser())->parse($unit);
        $this->assertTrue($parsed->isOk(), $parsed->errorMessage() ?? '');

        return new UnitFloatType($parsed->expression());
    }
}
