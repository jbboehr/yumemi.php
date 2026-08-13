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

namespace jbboehr\Yumemi\Tests;

use jbboehr\Yumemi\Dimension;
use jbboehr\Yumemi\Exception\IncompatibleQuantityContextException;
use jbboehr\Yumemi\Exception\InvalidArgumentException;
use jbboehr\Yumemi\Exception\LogicException;
use jbboehr\Yumemi\Exception\UnsupportedUnitAlgebraException;
use jbboehr\Yumemi\Number\Rational;
use jbboehr\Yumemi\PreferredUnitProfile;
use jbboehr\Yumemi\Registry\UnitRegistryBuilder;
use jbboehr\Yumemi\Units;
use PHPUnit\Framework\TestCase;

final class PreferredUnitProfileTest extends TestCase
{
    public function testConvertsToThePreferredCompoundTargetExactly(): void
    {
        $units = Units::default();
        $profile = $units->preferredUnitProfile(['kilometer / hour']);

        $preferred = $units->quantity(25, 'meter / second')->toPreferred($profile);

        self::assertSame('90', $preferred->valueToString());
        self::assertSame('kilometer / hour', $preferred->unitToString());
        self::assertSame($units, $preferred->units());
    }

    public function testPreservesTheConfiguredSymbolicTargetSpelling(): void
    {
        $units = Units::default();
        $profile = $units->preferredUnitProfile(['feet']);

        $preferred = $units->quantity(1, 'meter')->toPreferred($profile);

        self::assertSame('1250/381', $preferred->valueToString());
        self::assertSame('feet', $preferred->unitToString());
    }

    public function testReturnsTheSameQuantityWhenTheProfileHasNoMatchingDimension(): void
    {
        $units = Units::default();
        $profile = $units->preferredUnitProfile(['meter']);
        $duration = $units->quantity(5, 'second');

        self::assertSame($duration, $duration->toPreferred($profile));
    }

    public function testSupportsDimensionlessTargetsWithoutTreatingResolvedScaleAsAnExplicitMultiplier(): void
    {
        $units = Units::default();
        $profile = $units->preferredUnitProfile(['percent']);

        $preferred = $units->quantity(new Rational(1, 4), '1')->toPreferred($profile);

        self::assertSame('25', $preferred->valueToString());
        self::assertSame('percent', $preferred->unitToString());
    }

    public function testSupportsApplicationDefinedDimensionsInTheBoundContext(): void
    {
        $units = new Units(UnitRegistryBuilder::default()
            ->baseUnit('credit', Dimension::CURRENCY)
            ->define('millicredit = 0.001 * credit')
            ->build());
        $credit = self::runtimeUnit('credit');
        $millicredit = self::runtimeUnit('millicredit');
        $profile = $units->preferredUnitProfile([$millicredit]);

        $preferred = $units->quantity(2, $credit)->toPreferred($profile);

        self::assertSame('2000', $preferred->valueToString());
        self::assertSame('millicredit', $preferred->unitToString());
    }

    public function testRejectsMoreThanOneTargetForTheSameDimension(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('more than one target for dimension length');

        Units::default()->preferredUnitProfile(['meter', 'foot']);
    }

    public function testRejectsANonStringTargetAtTheInternalConstructionBoundary(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Preferred unit targets must be strings');

        new PreferredUnitProfile(Units::default(), [1]);
    }

    public function testRejectsAnExplicitNumericMultiplier(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must not contain an explicit numeric multiplier');

        Units::default()->preferredUnitProfile(['1000 * meter']);
    }

    public function testRejectsAffineAndLogarithmicTargetsThroughRuntimeSemantics(): void
    {
        foreach (['celsius', 'B'] as $target) {
            try {
                Units::default()->preferredUnitProfile([$target]);
                self::fail('Expected unsupported algebra for ' . $target);
            } catch (UnsupportedUnitAlgebraException) {
            }
        }

        self::addToAssertionCount(2);
    }

    public function testRejectsAProfileFromAnotherUnitsContextBeforeLookingForATarget(): void
    {
        $left = new Units(UnitRegistryBuilder::default()->build());
        $right = new Units(UnitRegistryBuilder::default()->build());
        $profile = $left->preferredUnitProfile(['meter']);

        $this->expectException(IncompatibleQuantityContextException::class);

        $right->quantity(1, 'second')->toPreferred($profile);
    }

    public function testProvidesCompactDebugInformationWithoutExposingTheUnitsGraph(): void
    {
        $profile = Units::default()->preferredUnitProfile(['kilometer / hour', 'millibar']);

        self::assertSame([
            'targets' => [
                'length / time' => 'kilometer / hour',
                'mass / (length * time ^ 2)' => 'millibar',
            ],
        ], $profile->__debugInfo());
    }

    public function testRejectsNativeSerialization(): void
    {
        $profile = Units::default()->preferredUnitProfile(['meter']);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('PreferredUnitProfile cannot be serialized');

        serialize($profile);
    }

    private static function runtimeUnit(string $unit): string
    {
        return $unit;
    }
}
