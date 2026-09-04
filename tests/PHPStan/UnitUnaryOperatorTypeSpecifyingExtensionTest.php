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

use jbboehr\Yumemi\PointQuantity;
use jbboehr\Yumemi\Quantity;
use jbboehr\Yumemi\PHPStan\QuantityType;
use jbboehr\Yumemi\PHPStan\UnitExpression;
use jbboehr\Yumemi\PHPStan\UnitExpressionParser;
use jbboehr\Yumemi\PHPStan\UnitConstantFloatType;
use jbboehr\Yumemi\PHPStan\UnitFloatType;
use jbboehr\Yumemi\PHPStan\UnitIntegerType;
use jbboehr\Yumemi\PHPStan\UnitIntegerTypeHelper;
use jbboehr\Yumemi\PHPStan\UnitUnaryOperatorTypeSpecifyingExtension;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\UnionType;
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

    public function testQuantityOperatorsRemainDisabledByDefault(): void
    {
        $meters = $this->quantity('meter');

        $this->assertFalse($this->extension->isOperatorSupported('+', $meters));
        $this->assertFalse($this->extension->isOperatorSupported('-', $meters));
    }

    public function testOptInUnarySignsPreserveBrandedAndUnbrandedQuantities(): void
    {
        $extension = new UnitUnaryOperatorTypeSpecifyingExtension(quantityOperators: true);
        $meters = $this->quantity('meter');
        $quantity = new ObjectType(Quantity::class);

        foreach (['+', '-'] as $operator) {
            $this->assertTrue($extension->isOperatorSupported($operator, $meters));
            $this->assertSame($meters, $extension->specifyType($operator, $meters));
            $this->assertTrue($extension->isOperatorSupported($operator, $quantity));
            $this->assertSame($quantity, $extension->specifyType($operator, $quantity));
        }
    }

    public function testOptInDoesNotClaimMixedObjectUnions(): void
    {
        $extension = new UnitUnaryOperatorTypeSpecifyingExtension(quantityOperators: true);
        $operand = new UnionType([
            $this->quantity('meter'),
            new ObjectType(PointQuantity::class),
        ]);

        $this->assertFalse($extension->isOperatorSupported('+', $operand));
        $this->assertFalse($extension->isOperatorSupported('-', $operand));
    }

    public function testDirectSpecificationRejectsAnUnbrandedOperand(): void
    {
        $result = $this->extension->specifyType('-', new IntegerType());

        $this->assertInstanceOf(ErrorType::class, $result);
        $this->assertSame('Unary unit operator requires a unit_int or unit_float operand.', $result->getReason());
    }

    public function testUnaryMinusAllowsFloatOverflow(): void
    {
        $meters = $this->unitInt('meter');
        $result = $this->extension->specifyType('-', $meters);

        $this->assertInstanceOf(BenevolentUnionType::class, $result);
        $this->assertSame(
            sprintf("((unit_int<'meter'>&int<%d, max>)|unit_float<'meter'>)", -PHP_INT_MAX),
            $result->describe(VerbosityLevel::precise()),
        );
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

    public function testUnaryMinusPreservesAConstantFloatValue(): void
    {
        $meters = new UnitConstantFloatType(1.5, $this->unitFloat('meter')->getUnitExpression());

        $result = $this->extension->specifyType('-', $meters);

        $this->assertSame("-1.5&unit_float<'meter'>", $result->describe(VerbosityLevel::precise()));
    }

    public function testUnaryMinusNegatesRangeBounds(): void
    {
        $meters = UnitIntegerTypeHelper::create($this->unitInt('meter')->getUnitExpression(), -5, 10);

        $result = $this->extension->specifyType('-', $meters);

        $this->assertSame(
            "unit_int<'meter'>&int<-10, 5>",
            $result->describe(VerbosityLevel::precise()),
        );
    }

    public function testUnaryMinusDistinguishesPartialAndGuaranteedMinimumOverflow(): void
    {
        $unit = $this->unitInt('meter')->getUnitExpression();
        $partial = UnitIntegerTypeHelper::create($unit, PHP_INT_MIN, -1);
        $minimum = UnitIntegerTypeHelper::create($unit, PHP_INT_MIN, PHP_INT_MIN);

        $partialResult = $this->extension->specifyType('-', $partial);
        $minimumResult = $this->extension->specifyType('-', $minimum);

        $this->assertInstanceOf(BenevolentUnionType::class, $partialResult);
        $this->assertSame(
            "((unit_int<'meter'>&int<1, max>)|unit_float<'meter'>)",
            $partialResult->describe(VerbosityLevel::precise()),
        );
        $this->assertInstanceOf(UnitFloatType::class, $minimumResult);
    }

    private function unitInt(string $unit): UnitIntegerType
    {
        return new UnitIntegerType($this->unit($unit));
    }

    private function quantity(string $unit): QuantityType
    {
        return new QuantityType($this->unit($unit));
    }

    private function unitFloat(string $unit): UnitFloatType
    {
        return new UnitFloatType($this->unit($unit));
    }

    private function unit(string $unit): UnitExpression
    {
        $parsed = (new UnitExpressionParser())->parse($unit);
        $this->assertTrue($parsed->isOk(), $parsed->errorMessage() ?? '');

        return $parsed->expression();
    }
}
