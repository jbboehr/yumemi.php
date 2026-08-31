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

use jbboehr\Yumemi\Exception\IncompatibleQuantityContextException;
use jbboehr\Yumemi\Exception\IncompatibleUnitException;
use jbboehr\Yumemi\Exception\NonIntegralValueException;
use jbboehr\Yumemi\Exception\NonTerminatingDecimalException;
use jbboehr\Yumemi\Formatter\FormatOptions;
use jbboehr\Yumemi\Formatter\Typography;
use jbboehr\Yumemi\Formatter\UnitNameStyle;
use jbboehr\Yumemi\Number\DecimalNotation;
use jbboehr\Yumemi\Number\FloatRangePolicy;
use jbboehr\Yumemi\Number\Rational;
use jbboehr\Yumemi\PointQuantity;
use jbboehr\Yumemi\Registry\UnitRegistryBuilder;
use jbboehr\Yumemi\Units;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PointQuantityTest extends TestCase
{
    public function testDefaultCatalogProvidesExactMultiplicativeDeltaUnits(): void
    {
        $units = Units::default();

        $this->assertSame('1', $units->conversionFactor('delta_celsius', 'kelvin')->toString());
        $this->assertSame('5/9', $units->conversionFactor('delta_fahrenheit', 'kelvin')->toString());
        $this->assertSame('18', $units->convert(10, 'delta_celsius', 'delta_fahrenheit')->toString());
        $this->assertSame('10', $units->convert(18, 'Δ°F', 'Δ°C')->toString());
        $this->assertSame('delta_degree_Celsius', $units->deltaUnit('celsius')->toString());
        $this->assertSame('kelvin', $units->deltaUnit('kelvin')->toString());
        $this->assertSame('meter * second ^ -1', $units->deltaUnit('meter / second')->toString());
    }

    public function testDeltaSymbolsParticipateInCatalogFormatting(): void
    {
        $units = Units::default();
        $unicodeSymbols = new FormatOptions(
            unitNameStyle: UnitNameStyle::Symbol,
            typography: Typography::Unicode,
        );
        $asciiSymbols = new FormatOptions(
            unitNameStyle: UnitNameStyle::Symbol,
            typography: Typography::Ascii,
        );

        $this->assertSame('Δ°C', $units->format('delta_celsius', $unicodeSymbols));
        $this->assertSame('Δ°F', $units->format('delta_fahrenheit', $unicodeSymbols));
        $this->assertSame('delta_degree_Celsius', $units->format('delta_celsius', $asciiSymbols));
    }

    public function testPointConstructionAndExactConversionPreservePointIdentity(): void
    {
        $units = Units::default();
        $freezing = $units->point(0, ' celsius ');

        $this->assertSame('5463/20', $freezing->valueIn('kelvin')->toString());
        $this->assertSame('32', $freezing->valueIn('fahrenheit')->toString());
        $this->assertSame('temperature', $freezing->dimension()->toString());
        $this->assertSame('0 * celsius', $freezing->toString());
        $this->assertSame('0 * celsius', (string) $freezing);
        $this->assertSame('celsius', $freezing->unit());
        $symbolOptions = FormatOptions::create()
            ->withUnitNameStyle(UnitNameStyle::Symbol)
            ->withTypography(Typography::Unicode);
        $this->assertSame('0 · ℃', $freezing->format($symbolOptions));
        $this->assertSame('℃', $freezing->formatUnit($symbolOptions));

        $fahrenheit = $freezing->to('fahrenheit');
        $this->assertSame('32', $fahrenheit->valueToString());
        $this->assertSame('fahrenheit', $fahrenheit->unitToString());
        $this->assertTrue($fahrenheit->equals($freezing));

        $kelvin = $freezing->to('kelvin');
        $this->assertSame('5463/20', $kelvin->valueToString());
        $this->assertSame('kelvin', $kelvin->unit());
        $this->assertTrue($kelvin->equals($freezing));
    }

    public function testPointTranslationUsesDeltaScaleAndPreservesCoordinateUnit(): void
    {
        $units = Units::default();
        $freezing = $units->point(0, 'celsius');
        $rise = $units->quantity(18, 'delta_fahrenheit');

        $warmer = $freezing->add($rise);

        $this->assertSame('10', $warmer->valueToString());
        $this->assertSame('celsius', $warmer->unit());
        $this->assertTrue($warmer->sub($rise)->equals($freezing));
    }

    public function testPointDifferenceReturnsLeftCoordinateDeltaUnit(): void
    {
        $units = Units::default();
        $boiling = $units->point(100, 'celsius');
        $freezingFahrenheit = $units->point(32, 'fahrenheit');

        $difference = $boiling->differenceFrom($freezingFahrenheit);

        $this->assertSame('100', $difference->valueToString());
        $this->assertSame('delta_celsius', $difference->unitToString());
        $this->assertSame('180', $difference->valueIn('delta_fahrenheit')->toString());
    }

    public function testDifferenceFromMakesTheSubtractionDirectionExplicit(): void
    {
        $units = Units::default();
        $boiling = $units->point(212, 'fahrenheit');
        $freezing = $units->point(0, 'celsius');

        $rise = $boiling->differenceFrom(origin: $freezing);
        $fall = $freezing->differenceFrom($boiling);

        $this->assertSame('180', $rise->valueToString());
        $this->assertSame('delta_fahrenheit', $rise->unitToString());
        $this->assertSame('-100', $fall->valueToString());
        $this->assertSame('delta_celsius', $fall->unitToString());
    }

    public function testDifferenceRemainsAnAliasForDifferenceFrom(): void
    {
        $units = Units::default();
        $boiling = $units->point(100, 'celsius');
        $freezing = $units->point(32, 'fahrenheit');

        // @phpstan-ignore method.deprecated (legacy compatibility is the behavior under test)
        $legacy = $boiling->difference(other: $freezing);

        $this->assertTrue($legacy->equals($boiling->differenceFrom($freezing)));
        $this->assertSame('delta_celsius', $legacy->unitToString());
    }

    public function testPointComparisonConvertsCoordinatesExactly(): void
    {
        $units = Units::default();
        $freezing = $units->point(0, 'celsius');
        $freezingFahrenheit = $units->point(32, 'fahrenheit');
        $boiling = $units->point(212, 'fahrenheit');

        $this->assertSame(-1, $freezing->compareTo($boiling));
        $this->assertTrue($freezing->lessThan($boiling));
        $this->assertTrue($freezing->lessThanOrEqualTo($boiling));
        $this->assertTrue($boiling->greaterThan($freezing));
        $this->assertTrue($boiling->greaterThanOrEqualTo($freezing));
        $this->assertTrue($freezing->equals($freezingFahrenheit));
        $this->assertFalse($freezing->lessThan($freezingFahrenheit));
        $this->assertTrue($freezing->lessThanOrEqualTo($freezingFahrenheit));
        $this->assertFalse($freezing->greaterThan($freezingFahrenheit));
        $this->assertTrue($freezing->greaterThanOrEqualTo($freezingFahrenheit));
    }

    public function testChecksPointCompatibilityWithoutConverting(): void
    {
        $units = Units::default();
        $freezing = $units->point(0, 'celsius');

        $this->assertTrue($freezing->isCompatibleWith($units->point(32, 'fahrenheit')));
        $this->assertTrue($freezing->isCompatibleWith($units->point(273, 'kelvin')));
        $this->assertFalse($freezing->isCompatibleWith($units->point(1, 'meter')));
    }

    public function testPointsFromDifferentContextsAreNotCompatible(): void
    {
        $leftUnits = new Units(UnitRegistryBuilder::default()->build());
        $rightUnits = new Units(UnitRegistryBuilder::default()->build());

        $this->assertFalse(
            $leftUnits->point(0, 'celsius')->isCompatibleWith($rightUnits->point(32, 'fahrenheit')),
        );
    }

    public function testPointEqualsReturnsFalseForIncompatibleDimensions(): void
    {
        $units = Units::default();

        $temperature = $units->point(0, self::temperatureUnit());
        $length = $units->point(0, self::lengthUnit());

        $this->assertFalse($temperature->equals($length));
        $this->assertFalse($length->equals($temperature));
    }

    public function testPointEqualsReturnsFalseForDifferentUnitsContexts(): void
    {
        $leftUnits = new Units(UnitRegistryBuilder::default()->build());
        $rightUnits = new Units(UnitRegistryBuilder::default()->build());

        $left = $leftUnits->point(0, 'celsius');
        $right = $rightUnits->point(32, 'fahrenheit');

        $this->assertFalse($left->equals($right));
        $this->assertFalse($right->equals($left));
    }

    public function testPointEqualsRemainsTotalAndSymmetricForZeroScaleUnits(): void
    {
        $units = new Units(UnitRegistryBuilder::default()->define('zero_meter = 0 * meter')->build());
        $zeroScale = $units->point(7, self::zeroScaleUnit());
        $otherZeroScale = $units->point(9, self::zeroScaleUnit());
        $zero = $units->point(0, 'meter');
        $one = $units->point(1, 'meter');

        $this->assertTrue($zeroScale->equals($zero));
        $this->assertTrue($zero->equals($zeroScale));
        $this->assertTrue($zeroScale->equals($otherZeroScale));
        $this->assertFalse($zeroScale->equals($one));
        $this->assertFalse($one->equals($zeroScale));
    }

    public function testPointEqualsIsAnEquivalenceRelationAcrossZeroScaleAffineAliasesAndOffsets(): void
    {
        $units = new Units(UnitRegistryBuilder::default()
            ->define('zero_kelvin = 0 * kelvin')
            ->alias('nothing_temperature', 'zero_kelvin')
            ->define('frozen_scale = zero_kelvin @ 5')
            ->alias('frozen_scale_alias', 'frozen_scale')
            ->define('shifted_kelvin = kelvin @ 5')
            ->alias('shifted_kelvin_alias', 'shifted_kelvin')
            ->define('third_kelvin = 1/3 * kelvin')
            ->build());

        $points = [
            'zero-scale unit' => ['zero kelvin', self::pointWithRuntimeUnit($units, 17, 'zero_kelvin')],
            'zero-scale unit alias' => [
                'zero kelvin',
                self::pointWithRuntimeUnit($units, -5, 'nothing_temperature'),
            ],
            'zero-scale affine unit' => ['zero kelvin', self::pointWithRuntimeUnit($units, 101, 'frozen_scale')],
            'zero-scale affine alias' => [
                'zero kelvin',
                self::pointWithRuntimeUnit($units, -9, 'frozen_scale_alias'),
            ],
            'zero kelvin' => ['zero kelvin', $units->point(0, 'kelvin')],
            'five kelvin' => ['five kelvin', $units->point(5, 'kelvin')],
            'shifted kelvin origin' => ['five kelvin', self::pointWithRuntimeUnit($units, 0, 'shifted_kelvin')],
            'shifted kelvin alias' => [
                'five kelvin',
                self::pointWithRuntimeUnit($units, 0, 'shifted_kelvin_alias'),
            ],
            'exact third in kelvin' => ['one third kelvin', $units->point(new Rational(1, 3), 'kelvin')],
            'exact third in a custom scale' => [
                'one third kelvin',
                self::pointWithRuntimeUnit($units, 1, 'third_kelvin'),
            ],
            'zero meters' => ['zero length', $units->point(0, 'meter')],
        ];

        foreach ($points as $leftName => [$leftClass, $left]) {
            foreach ($points as $rightName => [$rightClass, $right]) {
                $this->assertSame(
                    $leftClass === $rightClass,
                    $left->equals($right),
                    $leftName . ' compared with ' . $rightName,
                );
            }
        }
    }

    public function testPointNumericExtractionUsesAffineConversionBeforeOutput(): void
    {
        $point = Units::default()->point(32, 'fahrenheit');

        $this->assertSame(0, $point->exactIntValueIn('celsius'));
        $this->assertSame(0, $point->intValueIn('celsius'));
        $this->assertSame('0', $point->exactDecimalValueIn('celsius'));
        $this->assertSame('0.00', $point->decimalValueIn('celsius', 2, \RoundingMode::HalfEven));
        $this->assertSame('0.00', $point->significantDecimalValueIn('celsius', 3, \RoundingMode::HalfEven));
        $this->assertSame(
            '0.00e+0',
            $point->significantDecimalValueIn(
                'celsius',
                3,
                \RoundingMode::HalfEven,
                DecimalNotation::Scientific,
            ),
        );
        $this->assertSame(0.0, $point->floatValueIn('celsius'));
    }

    public function testExactPointIntegerExtractionReportsANonIntegralValue(): void
    {
        $point = Units::default()->point(1, 'fahrenheit');

        $this->expectException(NonIntegralValueException::class);

        $point->exactIntValueIn('celsius');
    }

    public function testExactPointDecimalExtractionReportsANonTerminatingExpansion(): void
    {
        $point = Units::default()->point(1, 'fahrenheit');

        $this->expectException(NonTerminatingDecimalException::class);

        $point->exactDecimalValueIn('celsius');
    }

    public function testFloatValueCanReturnSignedZeroAfterConversion(): void
    {
        $point = Units::default()->point(new Rational(-1, gmp_pow(2, 1075)), 'kelvin');

        $this->assertSame(-INF, fdiv(1.0, $point->floatValueIn('kelvin', FloatRangePolicy::Ieee754)));
    }

    public function testPointUnitsMustBeSingleNamedCoordinateUnits(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('single named coordinate unit');

        Units::default()->point(1, self::invalidPointUnit());
    }

    public function testPointUnitsMustSupportCoordinateConversion(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('logarithmic semantics');

        Units::default()->point(1, self::logarithmicUnit());
    }

    #[DataProvider('incompatiblePointOperationProvider')]
    public function testPointOperationsRejectIncompatibleDimensions(string $operation): void
    {
        $units = Units::default();
        $point = $units->point(0, self::temperatureUnit());
        $lengthPoint = $units->point(0, self::lengthUnit());
        $length = $units->quantity(1, self::lengthUnit());

        $this->expectException(IncompatibleUnitException::class);

        match ($operation) {
            'add' => $point->add($length),
            'sub' => $point->sub($length),
            'difference' => $point->differenceFrom($lengthPoint),
            'compareTo' => $point->compareTo($lengthPoint),
            'lessThan' => $point->lessThan($lengthPoint),
            'lessThanOrEqualTo' => $point->lessThanOrEqualTo($lengthPoint),
            'greaterThan' => $point->greaterThan($lengthPoint),
            'greaterThanOrEqualTo' => $point->greaterThanOrEqualTo($lengthPoint),
            'convert' => $point->to('meter'),
            default => throw new \LogicException('Unknown point operation: ' . $operation),
        };
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function incompatiblePointOperationProvider(): iterable
    {
        yield 'translation forward' => ['add'];
        yield 'translation backward' => ['sub'];
        yield 'difference' => ['difference'];
        yield 'three-way comparison' => ['compareTo'];
        yield 'less than' => ['lessThan'];
        yield 'less than or equal to' => ['lessThanOrEqualTo'];
        yield 'greater than' => ['greaterThan'];
        yield 'greater than or equal to' => ['greaterThanOrEqualTo'];
        yield 'conversion' => ['convert'];
    }

    public function testPointAndDeltaMustShareRegistryContext(): void
    {
        $leftUnits = new Units(UnitRegistryBuilder::default()->build());
        $rightUnits = new Units(UnitRegistryBuilder::default()->build());
        $point = $leftUnits->point(0, 'celsius');
        $delta = $rightUnits->quantity(1, 'delta_celsius');

        $this->expectException(IncompatibleQuantityContextException::class);

        $point->add($delta);
    }

    public function testPointsMustShareRegistryContextForComparison(): void
    {
        $leftUnits = new Units(UnitRegistryBuilder::default()->build());
        $rightUnits = new Units(UnitRegistryBuilder::default()->build());
        $left = $leftUnits->point(0, 'celsius');
        $right = $rightUnits->point(32, 'fahrenheit');

        $this->expectException(IncompatibleQuantityContextException::class);

        $left->compareTo($right);
    }

    public function testPointsMustShareRegistryContextForDifference(): void
    {
        $leftUnits = new Units(UnitRegistryBuilder::default()->build());
        $rightUnits = new Units(UnitRegistryBuilder::default()->build());
        $left = $leftUnits->point(0, 'celsius');
        $right = $rightUnits->point(32, 'fahrenheit');

        $this->expectException(IncompatibleQuantityContextException::class);

        // @phpstan-ignore method.deprecated (legacy compatibility is the behavior under test)
        $left->difference($right);
    }

    public function testDifferenceFromPreservesTheRegistryContextBoundary(): void
    {
        $leftUnits = new Units(UnitRegistryBuilder::default()->build());
        $rightUnits = new Units(UnitRegistryBuilder::default()->build());
        $left = $leftUnits->point(0, 'celsius');
        $right = $rightUnits->point(32, 'fahrenheit');

        $this->expectException(IncompatibleQuantityContextException::class);

        $left->differenceFrom($right);
    }

    public function testPointAndDeltaMustShareRegistryContextForSubtraction(): void
    {
        $leftUnits = new Units(UnitRegistryBuilder::default()->build());
        $rightUnits = new Units(UnitRegistryBuilder::default()->build());
        $point = $leftUnits->point(0, 'celsius');
        $delta = $rightUnits->quantity(1, 'delta_celsius');

        $this->expectException(IncompatibleQuantityContextException::class);

        $point->sub($delta);
    }

    public function testCustomAffineDefinitionsReceivePointAndDeltaBehavior(): void
    {
        $units = new Units(UnitRegistryBuilder::default()
            ->define('double_kelvin = 2 * kelvin')
            ->define('degree_widget = double_kelvin @ 100')
            ->build());
        $origin = $units->point(0, 'degree_widget');
        $translated = $origin->add($units->quantity(3, 'delta_degree_widget'));

        $this->assertSame('200', $origin->valueIn('kelvin')->toString());
        $this->assertSame('3', $translated->valueToString());
        $this->assertSame('6', $translated->differenceFrom($origin)->valueIn('kelvin')->toString());
    }

    private static function invalidPointUnit(): string
    {
        return 'celsius / second';
    }

    private static function temperatureUnit(): string
    {
        return 'celsius';
    }

    private static function lengthUnit(): string
    {
        return 'meter';
    }

    private static function logarithmicUnit(): string
    {
        return 'B';
    }

    private static function zeroScaleUnit(): string
    {
        return 'zero_meter';
    }

    private static function pointWithRuntimeUnit(
        Units $units,
        int|Rational $value,
        string $unit,
    ): PointQuantity {
        return $units->point($value, $unit);
    }
}
