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

namespace jbboehr\Yumemi\Tests\Property;

use Eris\Attributes\ErisRepeat;
use Eris\Generators;
use Eris\TestTrait;
use jbboehr\Yumemi\PHPStan\UnitExpression;
use jbboehr\Yumemi\PHPStan\UnitExpressionParser;
use jbboehr\Yumemi\PHPStan\UnitIntegerRangeMath;
use jbboehr\Yumemi\PHPStan\UnitIntegerTypeHelper;
use PHPStan\Type\Type;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('property')]
final class UnitIntegerRangeMathPropertyTest extends TestCase
{
    use TestTrait;

    private UnitExpression $unit;

    protected function setUp(): void
    {
        $parsed = (new UnitExpressionParser())->parse('meter');
        self::assertTrue($parsed->isOk());
        $this->unit = $parsed->expression();
    }

    #[ErisRepeat(250)]
    public function testGeneratedBinaryIntervalsMatchTheirEnumeratedHull(): void
    {
        $this->forAll(
            Generators::choose(-10_000, 10_000),
            Generators::choose(0, 20),
            Generators::choose(-10_000, 10_000),
            Generators::choose(0, 20),
        )
            ->then(function (int $leftMinimum, int $leftWidth, int $rightMinimum, int $rightWidth): void {
                $left = self::interval($leftMinimum, $leftWidth);
                $right = self::interval($rightMinimum, $rightWidth);

                $this->assertBinaryHull(
                    'addition',
                    $left,
                    $right,
                    UnitIntegerRangeMath::add($this->unit, $left, $right, true),
                    static fn (int $a, int $b): int => $a + $b,
                );
                $this->assertBinaryHull(
                    'subtraction',
                    $left,
                    $right,
                    UnitIntegerRangeMath::subtract($this->unit, $left, $right, true),
                    static fn (int $a, int $b): int => $a - $b,
                );
                $this->assertBinaryHull(
                    'multiplication',
                    $left,
                    $right,
                    UnitIntegerRangeMath::multiply($this->unit, $left, $right, true),
                    static fn (int $a, int $b): int => $a * $b,
                );
                $this->assertDivisionHull(
                    $left,
                    $right,
                    UnitIntegerRangeMath::divide($this->unit, $left, $right),
                );
            });
    }

    #[ErisRepeat(250)]
    public function testGeneratedPositivePowersMatchTheirEnumeratedHull(): void
    {
        $this->forAll(
            Generators::choose(-100, 100),
            Generators::choose(0, 10),
            Generators::choose(0, 4),
        )
            ->then(function (int $minimum, int $width, int $exponent): void {
                $bounds = self::interval($minimum, $width);
                $type = UnitIntegerRangeMath::power($this->unit, $bounds, $exponent, true);
                $metadata = UnitIntegerTypeHelper::extract($type);
                self::assertNotNull($metadata, self::describePower($bounds, $exponent));

                $expected = self::enumeratedPowerHull($bounds, $exponent);
                self::assertSame($expected['min'], $metadata['min'], self::describePower($bounds, $exponent));
                self::assertSame($expected['max'], $metadata['max'], self::describePower($bounds, $exponent));
            });
    }

    /** @return array{min: int, max: int} */
    private static function interval(int $minimum, int $width): array
    {
        return ['min' => $minimum, 'max' => $minimum + $width];
    }

    /**
     * @param array{min: int, max: int} $left
     * @param array{min: int, max: int} $right
     * @param callable(int, int): int $operation
     */
    private function assertBinaryHull(
        string $name,
        array $left,
        array $right,
        Type $type,
        callable $operation,
    ): void {
        $metadata = UnitIntegerTypeHelper::extract($type);
        $description = sprintf(
            '%s for [%d, %d] and [%d, %d]',
            $name,
            $left['min'],
            $left['max'],
            $right['min'],
            $right['max'],
        );
        self::assertNotNull($metadata, $description);

        $expected = self::enumeratedBinaryHull($left, $right, $operation);
        self::assertSame($expected['min'], $metadata['min'], $description);
        self::assertSame($expected['max'], $metadata['max'], $description);
    }

    /**
     * @param array{min: int, max: int} $left
     * @param array{min: int, max: int} $right
     * @param callable(int, int): int $operation
     *
     * @return array{min: int, max: int}
     */
    private static function enumeratedBinaryHull(array $left, array $right, callable $operation): array
    {
        $minimum = $operation($left['min'], $right['min']);
        $maximum = $minimum;

        for ($a = $left['min']; $a <= $left['max']; ++$a) {
            for ($b = $right['min']; $b <= $right['max']; ++$b) {
                $value = $operation($a, $b);
                $minimum = min($minimum, $value);
                $maximum = max($maximum, $value);
            }
        }

        return ['min' => $minimum, 'max' => $maximum];
    }

    /**
     * @param array{min: int, max: int} $left
     * @param array{min: int, max: int} $right
     */
    private function assertDivisionHull(array $left, array $right, Type $type): void
    {
        $metadata = UnitIntegerTypeHelper::extract($type);
        $description = sprintf(
            'integer division for [%d, %d] and [%d, %d]',
            $left['min'],
            $left['max'],
            $right['min'],
            $right['max'],
        );
        self::assertNotNull($metadata, $description);

        $values = [];
        for ($a = $left['min']; $a <= $left['max']; ++$a) {
            for ($b = $right['min']; $b <= $right['max']; ++$b) {
                if ($b !== 0) {
                    $values[] = intdiv($a, $b);
                }
            }
        }

        self::assertSame($values === [] ? null : min($values), $metadata['min'], $description);
        self::assertSame($values === [] ? null : max($values), $metadata['max'], $description);
    }

    /**
     * @param array{min: int, max: int} $bounds
     *
     * @return array{min: int, max: int}
     */
    private static function enumeratedPowerHull(array $bounds, int $exponent): array
    {
        $minimum = self::integerPower($bounds['min'], $exponent);
        $maximum = $minimum;

        for ($base = $bounds['min']; $base <= $bounds['max']; ++$base) {
            $value = self::integerPower($base, $exponent);
            $minimum = min($minimum, $value);
            $maximum = max($maximum, $value);
        }

        return ['min' => $minimum, 'max' => $maximum];
    }

    private static function integerPower(int $base, int $exponent): int
    {
        $result = 1;
        for ($i = 0; $i < $exponent; ++$i) {
            $result *= $base;
        }

        return $result;
    }

    /** @param array{min: int, max: int} $bounds */
    private static function describePower(array $bounds, int $exponent): string
    {
        return sprintf('power for [%d, %d] ^ %d', $bounds['min'], $bounds['max'], $exponent);
    }
}
