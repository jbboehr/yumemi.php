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

use jbboehr\Yumemi\PHPStan\UnitConstantIntegerType;
use jbboehr\Yumemi\PHPStan\UnitExpression;
use jbboehr\Yumemi\PHPStan\UnitExpressionParser;
use jbboehr\Yumemi\PHPStan\UnitFloatType;
use jbboehr\Yumemi\PHPStan\UnitIntegerType;
use jbboehr\Yumemi\PHPStan\UnitIntegerTypeHelper;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\VerbosityLevel;
use PHPUnit\Framework\TestCase;

final class UnitIntegerTypeHelperTest extends TestCase
{
    private UnitExpression $meters;

    private UnitExpression $seconds;

    protected function setUp(): void
    {
        $parser = new UnitExpressionParser();
        $meters = $parser->parse('meter');
        $seconds = $parser->parse('second');
        self::assertTrue($meters->isOk());
        self::assertTrue($seconds->isOk());
        $this->meters = $meters->expression();
        $this->seconds = $seconds->expression();
    }

    public function testCreatesNarrowestBrandedIntegerRepresentation(): void
    {
        $constant = UnitIntegerTypeHelper::create($this->meters, 3, 3);
        $range = UnitIntegerTypeHelper::create($this->meters, 0, 100);
        $unbounded = UnitIntegerTypeHelper::create($this->meters, null, null);

        self::assertInstanceOf(UnitConstantIntegerType::class, $constant);
        self::assertSame("3&unit_int<'meter'>", $constant->describe(VerbosityLevel::precise()));
        self::assertSame("unit_int<'meter'>&int<0, 100>", $range->describe(VerbosityLevel::precise()));
        self::assertInstanceOf(UnitIntegerType::class, $unbounded);
    }

    public function testExtractsStandardRangeAndLiteralIntersections(): void
    {
        $brand = new UnitIntegerType($this->meters);
        $range = TypeCombinator::intersect($brand, IntegerRangeType::fromInterval(0, 100));
        $literal = TypeCombinator::intersect($brand, new ConstantIntegerType(7));

        self::assertSame(
            ['unit' => $this->meters, 'min' => 0, 'max' => 100],
            UnitIntegerTypeHelper::extract($range),
        );
        self::assertSame(
            ['unit' => $this->meters, 'min' => 7, 'max' => 7],
            UnitIntegerTypeHelper::extract($literal),
        );
    }

    public function testUnboundedBrandAcceptsSameUnitRangesAndConstantsOnly(): void
    {
        $meters = new UnitIntegerType($this->meters);
        $meterRange = UnitIntegerTypeHelper::create($this->meters, 0, 100);
        $meterConstant = UnitIntegerTypeHelper::create($this->meters, 3, 3);
        $secondRange = UnitIntegerTypeHelper::create($this->seconds, 0, 100);

        self::assertTrue($meters->accepts($meterRange, true)->yes());
        self::assertTrue($meters->accepts($meterConstant, true)->yes());
        self::assertTrue($meters->accepts($secondRange, true)->no());
        self::assertTrue($meters->accepts(IntegerRangeType::fromInterval(0, 100), true)->no());
    }

    public function testRangeIntersectionEnforcesUnitAndBounds(): void
    {
        $target = UnitIntegerTypeHelper::create($this->meters, 0, 100);

        self::assertTrue($target->accepts(UnitIntegerTypeHelper::create($this->meters, 10, 20), true)->yes());
        self::assertFalse($target->accepts(UnitIntegerTypeHelper::create($this->meters, -1, 20), true)->yes());
        self::assertTrue($target->accepts(UnitIntegerTypeHelper::create($this->seconds, 10, 20), true)->no());
        self::assertFalse($target->accepts(new UnitIntegerType($this->meters), true)->yes());
    }

    public function testBrandedConstantRetainsConstantAndUnitRelations(): void
    {
        $threeMeters = new UnitConstantIntegerType(3, $this->meters);

        self::assertSame(3, $threeMeters->getValue());
        self::assertTrue($threeMeters->accepts(new UnitConstantIntegerType(3, $this->meters), true)->yes());
        self::assertTrue($threeMeters->accepts(new UnitConstantIntegerType(4, $this->meters), true)->no());
        self::assertTrue($threeMeters->accepts(new UnitConstantIntegerType(3, $this->seconds), true)->no());
        self::assertTrue($threeMeters->accepts(new ConstantIntegerType(3), true)->no());
        self::assertTrue((new UnitFloatType($this->meters))->accepts($threeMeters, true)->yes());
        self::assertTrue((new UnitFloatType($this->meters))->accepts(
            UnitIntegerTypeHelper::create($this->meters, 1, 5),
            true,
        )->yes());
        self::assertInstanceOf(UnitIntegerType::class, $threeMeters->generalize(
            \PHPStan\Type\GeneralizePrecision::moreSpecific(),
        ));
    }

    public function testBrandsNativeIntegerPrecisionAndFiniteUnions(): void
    {
        $constant = UnitIntegerTypeHelper::brand(new ConstantIntegerType(2), $this->meters);
        $range = UnitIntegerTypeHelper::brand(IntegerRangeType::fromInterval(1, 5), $this->meters);
        $union = UnitIntegerTypeHelper::brand(TypeCombinator::union(
            new ConstantIntegerType(1),
            new ConstantIntegerType(3),
        ), $this->meters);

        self::assertSame("2&unit_int<'meter'>", $constant->describe(VerbosityLevel::precise()));
        self::assertSame("unit_int<'meter'>&int<1, 5>", $range->describe(VerbosityLevel::precise()));
        self::assertSame(
            "1&unit_int<'meter'>|3&unit_int<'meter'>",
            $union->describe(VerbosityLevel::precise()),
        );
        self::assertInstanceOf(UnitIntegerType::class, UnitIntegerTypeHelper::brand(new IntegerType(), $this->meters));
    }
}
