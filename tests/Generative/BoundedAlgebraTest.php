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

namespace jbboehr\Yumemi\Tests\Generative;

use jbboehr\Yumemi\Analyzer\AstConverter;
use jbboehr\Yumemi\Analyzer\ExprReducer;
use jbboehr\Yumemi\Dimension;
use jbboehr\Yumemi\Formatter\DivisionStyle;
use jbboehr\Yumemi\Formatter\FormatOptions;
use jbboehr\Yumemi\Formatter\Typography;
use jbboehr\Yumemi\Formatter\UnitNameStyle;
use jbboehr\Yumemi\Number\Rational;
use jbboehr\Yumemi\Parser\Parser;
use jbboehr\Yumemi\Units;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class BoundedAlgebraTest extends TestCase
{
    private const EXPRESSION_CASES_PER_DEPTH = 16;
    private const MAX_EXPRESSION_DEPTH = 3;

    /** @var list<string> */
    private const EXPRESSION_ATOMS = [
        'meter',
        'second',
        'kilogram',
        'ampere',
        'kelvin',
        'kilometer',
        'hour',
        'newton',
        'watt',
        'centimeter',
        'international_foot',
        '2',
        '1.25',
        '1e-3',
    ];

    /** @var array<string, list<string>> */
    private const COMPATIBLE_UNIT_GROUPS = [
        'length' => ['meter', 'centimeter', 'kilometer', 'international_foot'],
        'time' => ['second', 'minute', 'hour', 'day'],
        'speed' => ['meter / second', 'kilometer / hour', 'international_foot / minute'],
        'temperature delta' => ['kelvin', 'delta_celsius', 'delta_fahrenheit'],
    ];

    /** @var list<string> */
    private const POINT_UNITS = ['kelvin', 'celsius', 'fahrenheit'];

    /** @var list<string> */
    private const DELTA_UNITS = ['kelvin', 'delta_celsius', 'delta_fahrenheit'];

    public function testNamedDimensionAlgebraPreservesVectorIdentities(): void
    {
        $dimensions = [
            Dimension::dimensionless(),
            Dimension::fromNamedPowers(['length' => 1, 'currency' => -1]),
            Dimension::fromNamedPowers(['time' => -2, 'information' => 3]),
            Dimension::fromNamedPowers(['currency' => 2, 'information' => -1]),
        ];

        foreach ($dimensions as $left) {
            self::assertTrue($left->mul(Dimension::dimensionless())->equals($left));
            self::assertTrue($left->div($left)->isDimensionless());

            foreach ($dimensions as $right) {
                self::assertTrue($left->mul($right)->equals($right->mul($left)));
                self::assertTrue($left->mul($right)->div($right)->equals($left));

                foreach ($dimensions as $third) {
                    self::assertTrue(
                        $left->mul($right)->mul($third)->equals($left->mul($right->mul($third))),
                    );
                }
            }
        }
    }

    #[DataProvider('rationalProvider')]
    public function testRationalFieldIdentities(Rational $left, Rational $right, Rational $third): void
    {
        $zero = new Rational(0);
        $one = new Rational(1);

        self::assertTrue($left->add($zero)->equals($left), 'a + 0 = a');
        self::assertTrue($left->mul($one)->equals($left), 'a * 1 = a');
        self::assertTrue($left->add($right)->sub($right)->equals($left), '(a + b) - b = a');
        self::assertTrue(
            $left->mul($right->add($third))->equals($left->mul($right)->add($left->mul($third))),
            'a * (b + c) = a * b + a * c',
        );

        if (!$right->equals($zero)) {
            self::assertTrue($left->mul($right)->div($right)->equals($left), '(a * b) / b = a');
        }
    }

    /**
     * @return iterable<string, array{Rational, Rational, Rational}>
     */
    public static function rationalProvider(): iterable
    {
        $values = self::rationalValues();
        $count = count($values);

        foreach ($values as $index => $left) {
            $right = $values[($index * 17 + 7) % $count];
            $third = $values[($index * 29 + 11) % $count];

            yield sprintf(
                'rational case=%02d a=%s b=%s c=%s',
                $index,
                $left->toString(),
                $right->toString(),
                $third->toString(),
            ) => [$left, $right, $third];
        }
    }

    #[DataProvider('expressionProvider')]
    public function testReductionAndNormalizationAreIdempotent(string $input): void
    {
        $unreduced = AstConverter::symbolic()->convert(Parser::parseString($input));
        $reduced = ExprReducer::reduce($unreduced);
        $reducedAgain = ExprReducer::reduce($reduced);

        self::assertSame($reduced->toString(), $reducedAgain->toString(), 'reduction: ' . $input);

        $units = Units::default();
        $normalized = $units->normalize($input);
        $normalizedAgain = $units->normalize($normalized);

        self::assertSame($normalized->toString(), $normalizedAgain->toString(), 'normalization: ' . $input);
    }

    #[DataProvider('expressionProvider')]
    public function testParserFormatterRoundTripsPreserveNormalizedMeaning(string $input): void
    {
        $units = Units::default();
        $expected = $units->normalize($input)->toString();

        foreach (self::parserCompatibleFormatOptions() as $label => $options) {
            $formatted = $units->format($units->parse($input), $options);
            $actual = $units->normalize($units->parse($formatted))->toString();

            self::assertSame(
                $expected,
                $actual,
                sprintf('input=%s; format=%s; output=%s', $input, $label, $formatted),
            );
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function expressionProvider(): iterable
    {
        foreach (self::expressions() as $label => $input) {
            yield $label => [$input];
        }
    }

    #[DataProvider('conversionProvider')]
    public function testConversionsComposeAndReverseExactly(
        Rational $value,
        string $from,
        string $via,
        string $to,
    ): void {
        $units = Units::default();
        $directFactor = $units->conversionFactor($from, $to);
        $composedFactor = $units->conversionFactor($from, $via)
            ->mul($units->conversionFactor($via, $to));

        self::assertTrue($directFactor->equals($composedFactor), 'conversion factors compose');
        self::assertTrue(
            $units->conversionFactor($from, $to)
                ->mul($units->conversionFactor($to, $from))
                ->equals(new Rational(1)),
            'conversion factors reverse',
        );

        $direct = $units->convert($value, $from, $to);
        $composed = $units->convert($units->convert($value, $from, $via), $via, $to);
        $reversed = $units->convert($direct, $to, $from);

        self::assertTrue($direct->equals($composed), 'converted values compose');
        self::assertTrue($value->equals($reversed), 'converted values reverse');
    }

    /**
     * @return iterable<string, array{Rational, string, string, string}>
     */
    public static function conversionProvider(): iterable
    {
        $values = self::rationalValues();
        $case = 0;

        foreach (self::COMPATIBLE_UNIT_GROUPS as $group => $units) {
            foreach ($units as $from) {
                foreach ($units as $via) {
                    foreach ($units as $to) {
                        $value = $values[$case % count($values)];

                        yield sprintf(
                            'conversion case=%03d group=%s value=%s from=%s via=%s to=%s',
                            $case,
                            $group,
                            $value->toString(),
                            $from,
                            $via,
                            $to,
                        ) => [$value, $from, $via, $to];

                        ++$case;
                    }
                }
            }
        }
    }

    #[DataProvider('quantityProvider')]
    public function testQuantityArithmeticIdentities(
        Rational $leftValue,
        string $leftUnit,
        Rational $rightValue,
        string $rightUnit,
    ): void {
        $units = Units::default();
        $left = $units->quantity($leftValue, $leftUnit);
        $right = $units->quantity($rightValue, $rightUnit);

        self::assertTrue($left->add($right)->equals($right->add($left)), 'a + b = b + a');
        self::assertTrue($left->add($right)->sub($right)->equals($left), '(a + b) - b = a');
        self::assertTrue($left->sub($right)->add($right)->equals($left), '(a - b) + b = a');
        self::assertTrue($left->mul($right)->div($right)->equals($left), '(a * b) / b = a');
        self::assertTrue($left->div($right)->mul($right)->equals($left), '(a / b) * b = a');
        self::assertTrue($left->neg()->neg()->equals($left), '-(-a) = a');
    }

    /**
     * @return iterable<string, array{Rational, string, Rational, string}>
     */
    public static function quantityProvider(): iterable
    {
        $values = self::rationalValues();
        $nonZeroValues = array_values(array_filter(
            $values,
            static fn (Rational $value): bool => !$value->equals(new Rational(0)),
        ));
        $case = 0;

        foreach (self::COMPATIBLE_UNIT_GROUPS as $group => $units) {
            foreach ($units as $leftUnit) {
                foreach ($units as $rightUnit) {
                    $leftValue = $values[($case * 11 + 3) % count($values)];
                    $rightValue = $nonZeroValues[($case * 19 + 5) % count($nonZeroValues)];

                    yield sprintf(
                        'quantity case=%02d group=%s left=%s %s right=%s %s',
                        $case,
                        $group,
                        $leftValue->toString(),
                        $leftUnit,
                        $rightValue->toString(),
                        $rightUnit,
                    ) => [$leftValue, $leftUnit, $rightValue, $rightUnit];

                    ++$case;
                }
            }
        }
    }

    #[DataProvider('pointProvider')]
    public function testPointConversionDifferenceAndTranslationIdentities(
        Rational $value,
        string $unit,
        string $targetUnit,
        Rational $otherValue,
        string $otherUnit,
        Rational $deltaValue,
        string $deltaUnit,
    ): void {
        $units = Units::default();
        $point = $units->point($value, $unit);
        $other = $units->point($otherValue, $otherUnit);
        $delta = $units->quantity($deltaValue, $deltaUnit);

        self::assertTrue($point->to($targetUnit)->to($unit)->equals($point), 'point conversion reverses');

        $difference = $point->differenceFrom($other);
        self::assertTrue($other->add($difference)->equals($point), 'q + (p - q) = p');
        self::assertTrue($point->sub($difference)->equals($other), 'p - (p - q) = q');

        $translatedForward = $point->add($delta);
        $translatedBackward = $point->sub($delta);
        self::assertTrue($translatedForward->sub($delta)->equals($point), '(p + d) - d = p');
        self::assertTrue($translatedBackward->add($delta)->equals($point), '(p - d) + d = p');
        self::assertTrue($translatedForward->differenceFrom($point)->equals($delta), '(p + d) - p = d');
    }

    /**
     * @return iterable<string, array{Rational, string, string, Rational, string, Rational, string}>
     */
    public static function pointProvider(): iterable
    {
        $values = self::rationalValues();
        $nonZeroValues = array_values(array_filter(
            $values,
            static fn (Rational $value): bool => !$value->equals(new Rational(0)),
        ));
        $case = 0;

        foreach (self::POINT_UNITS as $unit) {
            foreach (self::POINT_UNITS as $targetUnit) {
                foreach (self::POINT_UNITS as $otherUnit) {
                    $value = $values[($case * 7 + 2) % count($values)];
                    $otherValue = $values[($case * 13 + 9) % count($values)];
                    $deltaValue = $nonZeroValues[($case * 23 + 4) % count($nonZeroValues)];
                    $deltaUnit = self::DELTA_UNITS[$case % count(self::DELTA_UNITS)];

                    yield sprintf(
                        'point case=%02d p=%s %s target=%s q=%s %s delta=%s %s',
                        $case,
                        $value->toString(),
                        $unit,
                        $targetUnit,
                        $otherValue->toString(),
                        $otherUnit,
                        $deltaValue->toString(),
                        $deltaUnit,
                    ) => [$value, $unit, $targetUnit, $otherValue, $otherUnit, $deltaValue, $deltaUnit];

                    ++$case;
                }
            }
        }
    }

    /**
     * @return array<string, string>
     */
    private static function expressions(): array
    {
        $expressions = [];
        $previous = self::EXPRESSION_ATOMS;
        $pool = $previous;

        foreach ($previous as $case => $input) {
            $expressions[sprintf('expression atom=%02d input=%s', $case, $input)] = $input;
        }

        $exponents = [-3, -2, -1, 0, 2, 3];

        for ($depth = 1; $depth <= self::MAX_EXPRESSION_DEPTH; ++$depth) {
            $current = [];

            for ($case = 0; $case < self::EXPRESSION_CASES_PER_DEPTH; ++$case) {
                $left = self::expressionAt($previous, $case);
                $right = self::expressionAt($pool, $case * 7 + $depth * 3);
                $input = match ($case % 5) {
                    0 => sprintf('(%s) * (%s)', $left, $right),
                    1 => sprintf('(%s) / (%s)', $left, $right),
                    2 => sprintf('(%s) ^ %d', $left, $exponents[($case + $depth) % count($exponents)]),
                    3 => sprintf('(%s) . (%s)', $left, $right),
                    4 => sprintf('(%s) · (%s)', $left, $right),
                };
                $current[] = $input;
                $expressions[sprintf(
                    'expression depth=%d case=%02d input=%s',
                    $depth,
                    $case,
                    $input,
                )] = $input;
            }

            $pool = [...$pool, ...$current];
            $previous = $current;
        }

        return $expressions;
    }

    /**
     * @param list<string> $expressions
     */
    private static function expressionAt(array $expressions, int $index): string
    {
        if ($expressions === []) {
            throw new \LogicException('Cannot select from an empty expression set.');
        }

        return $expressions[$index % count($expressions)];
    }

    /**
     * @return iterable<string, FormatOptions>
     */
    private static function parserCompatibleFormatOptions(): iterable
    {
        foreach (UnitNameStyle::cases() as $unitNameStyle) {
            foreach (Typography::cases() as $typography) {
                foreach (DivisionStyle::cases() as $divisionStyle) {
                    $label = sprintf(
                        'names=%s typography=%s division=%s',
                        $unitNameStyle->value,
                        $typography->value,
                        $divisionStyle->value,
                    );

                    yield $label => new FormatOptions(
                        unitNameStyle: $unitNameStyle,
                        typography: $typography,
                        divisionStyle: $divisionStyle,
                    );
                }
            }
        }
    }

    /**
     * @return list<Rational>
     */
    private static function rationalValues(): array
    {
        $values = [];

        for ($numerator = -6; $numerator <= 6; ++$numerator) {
            for ($denominator = 1; $denominator <= 5; ++$denominator) {
                $values[] = new Rational($numerator, $denominator);
            }
        }

        return $values;
    }
}
