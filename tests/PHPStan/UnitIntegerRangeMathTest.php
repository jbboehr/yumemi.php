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
use jbboehr\Yumemi\PHPStan\UnitIntegerRangeMath;
use jbboehr\Yumemi\PHPStan\UnitIntegerTypeHelper;
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
}
