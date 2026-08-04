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

use jbboehr\Yumemi\PHPStan\UnitExpression;
use jbboehr\Yumemi\PHPStan\UnitExpressionParser;
use jbboehr\Yumemi\PHPStan\UnitFloatType;
use jbboehr\Yumemi\PHPStan\UnitIntegerRangeMath;
use jbboehr\Yumemi\PHPStan\UnitIntegerType;
use jbboehr\Yumemi\PHPStan\UnitIntegerTypeHelper;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\Type;
use PHPUnit\Framework\TestCase;

final class UnitIntegerRangeMathTest extends TestCase
{
    private UnitExpression $unit;

    protected function setUp(): void
    {
        $parsed = (new UnitExpressionParser())->parse('meter');
        self::assertTrue($parsed->isOk());
        $this->unit = $parsed->expression();
    }

    public function testBoundedBinaryIntervalsContainExactlyTheEnumeratedHull(): void
    {
        $intervals = self::smallIntervals();
        foreach ($intervals as $left) {
            foreach ($intervals as $right) {
                $cases = [
                    'addition' => [
                        UnitIntegerRangeMath::add($this->unit, $left, $right, true),
                        static fn (int $a, int $b): int => $a + $b,
                    ],
                    'subtraction' => [
                        UnitIntegerRangeMath::subtract($this->unit, $left, $right, true),
                        static fn (int $a, int $b): int => $a - $b,
                    ],
                    'multiplication' => [
                        UnitIntegerRangeMath::multiply($this->unit, $left, $right, true),
                        static fn (int $a, int $b): int => $a * $b,
                    ],
                ];

                foreach ($cases as $name => [$type, $operation]) {
                    $values = [$operation($left['min'], $right['min'])];
                    for ($a = $left['min']; $a <= $left['max']; ++$a) {
                        for ($b = $right['min']; $b <= $right['max']; ++$b) {
                            $values[] = $operation($a, $b);
                        }
                    }

                    $metadata = UnitIntegerTypeHelper::extract($type);
                    self::assertNotNull($metadata, sprintf(
                        '%s failed for [%d, %d] and [%d, %d]',
                        $name,
                        $left['min'],
                        $left['max'],
                        $right['min'],
                        $right['max'],
                    ));
                    self::assertSame(min($values), $metadata['min']);
                    self::assertSame(max($values), $metadata['max']);
                }
            }
        }
    }

    public function testBoundedPositivePowersContainExactlyTheEnumeratedHull(): void
    {
        foreach (self::smallIntervals() as $bounds) {
            foreach (range(0, 5) as $exponent) {
                $type = UnitIntegerRangeMath::power($this->unit, $bounds, $exponent, true);
                $metadata = UnitIntegerTypeHelper::extract($type);
                self::assertNotNull($metadata);

                $values = [self::integerPower($bounds['min'], $exponent)];
                for ($value = $bounds['min']; $value <= $bounds['max']; ++$value) {
                    $values[] = self::integerPower($value, $exponent);
                }

                self::assertSame(min($values), $metadata['min']);
                self::assertSame(max($values), $metadata['max']);
            }
        }
    }

    public function testExactNativeIntegerLimitsRemainBrandedConstants(): void
    {
        $maximum = UnitIntegerRangeMath::add(
            $this->unit,
            ['min' => PHP_INT_MAX - 1, 'max' => PHP_INT_MAX - 1],
            ['min' => 1, 'max' => 1],
            true,
        );
        $minimum = UnitIntegerRangeMath::subtract(
            $this->unit,
            ['min' => PHP_INT_MIN + 1, 'max' => PHP_INT_MIN + 1],
            ['min' => 1, 'max' => 1],
            true,
        );

        self::assertSame(
            ['unit' => $this->unit, 'min' => PHP_INT_MAX, 'max' => PHP_INT_MAX],
            UnitIntegerTypeHelper::extract($maximum),
        );
        self::assertSame(
            ['unit' => $this->unit, 'min' => PHP_INT_MIN, 'max' => PHP_INT_MIN],
            UnitIntegerTypeHelper::extract($minimum),
        );
    }

    public function testPartialOverflowAndUnderflowPreserveTheirIntegerSubranges(): void
    {
        $upperAddition = UnitIntegerRangeMath::add(
            $this->unit,
            ['min' => PHP_INT_MAX - 1, 'max' => PHP_INT_MAX],
            ['min' => 0, 'max' => 1],
            true,
        );
        $lowerAddition = UnitIntegerRangeMath::add(
            $this->unit,
            ['min' => PHP_INT_MIN, 'max' => PHP_INT_MIN + 1],
            ['min' => -1, 'max' => 0],
            true,
        );
        $upperSubtraction = UnitIntegerRangeMath::subtract(
            $this->unit,
            ['min' => PHP_INT_MAX - 1, 'max' => PHP_INT_MAX],
            ['min' => -1, 'max' => 0],
            true,
        );
        $lowerSubtraction = UnitIntegerRangeMath::subtract(
            $this->unit,
            ['min' => PHP_INT_MIN, 'max' => PHP_INT_MIN + 1],
            ['min' => 0, 'max' => 1],
            true,
        );
        $upperMultiplication = UnitIntegerRangeMath::multiply(
            $this->unit,
            ['min' => intdiv(PHP_INT_MAX, 2), 'max' => intdiv(PHP_INT_MAX, 2) + 1],
            ['min' => 2, 'max' => 2],
            true,
        );
        $lowerMultiplication = UnitIntegerRangeMath::multiply(
            $this->unit,
            ['min' => intdiv(PHP_INT_MIN, 2) - 1, 'max' => intdiv(PHP_INT_MIN, 2)],
            ['min' => 2, 'max' => 2],
            true,
        );
        $negation = UnitIntegerRangeMath::negate(
            $this->unit,
            ['min' => PHP_INT_MIN, 'max' => PHP_INT_MIN + 1],
            true,
        );

        $this->assertIntegerAndFloat($upperAddition, PHP_INT_MAX - 1, null);
        $this->assertIntegerAndFloat($lowerAddition, null, PHP_INT_MIN + 1);
        $this->assertIntegerAndFloat($upperSubtraction, PHP_INT_MAX - 1, null);
        $this->assertIntegerAndFloat($lowerSubtraction, null, PHP_INT_MIN + 1);
        $this->assertIntegerAndFloat($upperMultiplication, PHP_INT_MAX - 1, null);
        $this->assertIntegerAndFloat($lowerMultiplication, PHP_INT_MIN, PHP_INT_MIN);
        $this->assertIntegerAndFloat($negation, PHP_INT_MAX, PHP_INT_MAX);
    }

    public function testGuaranteedOverflowAndUnderflowPromoteSymmetrically(): void
    {
        $cases = [
            UnitIntegerRangeMath::add(
                $this->unit,
                ['min' => PHP_INT_MAX, 'max' => PHP_INT_MAX],
                ['min' => 1, 'max' => 1],
                true,
            ),
            UnitIntegerRangeMath::add(
                $this->unit,
                ['min' => PHP_INT_MIN, 'max' => PHP_INT_MIN],
                ['min' => -1, 'max' => -1],
                true,
            ),
            UnitIntegerRangeMath::subtract(
                $this->unit,
                ['min' => PHP_INT_MAX, 'max' => PHP_INT_MAX],
                ['min' => -1, 'max' => -1],
                true,
            ),
            UnitIntegerRangeMath::subtract(
                $this->unit,
                ['min' => PHP_INT_MIN, 'max' => PHP_INT_MIN],
                ['min' => 1, 'max' => 1],
                true,
            ),
            UnitIntegerRangeMath::multiply(
                $this->unit,
                ['min' => PHP_INT_MAX, 'max' => PHP_INT_MAX],
                ['min' => 2, 'max' => 2],
                true,
            ),
            UnitIntegerRangeMath::multiply(
                $this->unit,
                ['min' => PHP_INT_MIN, 'max' => PHP_INT_MIN],
                ['min' => 2, 'max' => 2],
                true,
            ),
            UnitIntegerRangeMath::negate(
                $this->unit,
                ['min' => PHP_INT_MIN, 'max' => PHP_INT_MIN],
                true,
            ),
            UnitIntegerRangeMath::power(
                $this->unit,
                ['min' => PHP_INT_MAX, 'max' => PHP_INT_MAX],
                2,
                true,
            ),
            UnitIntegerRangeMath::power(
                $this->unit,
                ['min' => PHP_INT_MIN, 'max' => PHP_INT_MIN],
                2,
                true,
            ),
        ];

        foreach ($cases as $type) {
            self::assertInstanceOf(UnitFloatType::class, $type);
            self::assertTrue($this->unit->equivalent($type->getUnitExpression()));
        }
    }

    public function testDisabledPromotionWidensPartialAndGuaranteedBoundaryResultsToUnitInteger(): void
    {
        $cases = [
            UnitIntegerRangeMath::add(
                $this->unit,
                ['min' => PHP_INT_MAX - 1, 'max' => PHP_INT_MAX],
                ['min' => 0, 'max' => 1],
                false,
            ),
            UnitIntegerRangeMath::add(
                $this->unit,
                ['min' => PHP_INT_MIN, 'max' => PHP_INT_MIN + 1],
                ['min' => -1, 'max' => 0],
                false,
            ),
            UnitIntegerRangeMath::add(
                $this->unit,
                ['min' => PHP_INT_MAX, 'max' => PHP_INT_MAX],
                ['min' => 1, 'max' => 1],
                false,
            ),
            UnitIntegerRangeMath::add(
                $this->unit,
                ['min' => PHP_INT_MIN, 'max' => PHP_INT_MIN],
                ['min' => -1, 'max' => -1],
                false,
            ),
        ];

        foreach ($cases as $type) {
            self::assertInstanceOf(UnitIntegerType::class, $type);
            self::assertTrue($this->unit->equivalent($type->getUnitExpression()));
        }
    }

    /** @return list<array{min: int, max: int}> */
    private static function smallIntervals(): array
    {
        $intervals = [];
        for ($min = -3; $min <= 3; ++$min) {
            for ($max = $min; $max <= 3; ++$max) {
                $intervals[] = ['min' => $min, 'max' => $max];
            }
        }

        return $intervals;
    }

    private static function integerPower(int $base, int $exponent): int
    {
        $result = 1;
        for ($i = 0; $i < $exponent; ++$i) {
            $result *= $base;
        }

        return $result;
    }

    private function assertIntegerAndFloat(Type $type, ?int $integerMin, ?int $integerMax): void
    {
        self::assertInstanceOf(BenevolentUnionType::class, $type);
        self::assertCount(2, $type->getTypes());

        $integer = null;
        $hasFloat = false;
        foreach ($type->getTypes() as $innerType) {
            if ($innerType instanceof UnitFloatType) {
                self::assertTrue($this->unit->equivalent($innerType->getUnitExpression()));
                $hasFloat = true;

                continue;
            }

            $integer = UnitIntegerTypeHelper::extract($innerType);
        }

        self::assertTrue($hasFloat);
        self::assertSame(
            ['unit' => $this->unit, 'min' => $integerMin, 'max' => $integerMax],
            $integer,
        );
    }
}
