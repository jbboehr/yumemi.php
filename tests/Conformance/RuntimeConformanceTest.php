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

namespace jbboehr\Yumemi\Tests\Conformance;

use jbboehr\Yumemi\Exception\IncompatibleUnitException;
use jbboehr\Yumemi\Exception\NonMultiplicativeConversionException;
use jbboehr\Yumemi\Exception\NonExactRootException;
use jbboehr\Yumemi\Exception\RuntimeException;
use jbboehr\Yumemi\Exception\UnitNotFoundException;
use jbboehr\Yumemi\Exception\UnsupportedSyntaxException;
use jbboehr\Yumemi\Exception\UnsupportedUnitAlgebraException;
use jbboehr\Yumemi\Exception\UnsupportedUnitConversionException;
use jbboehr\Yumemi\Number\DecimalNotation;
use jbboehr\Yumemi\Number\Rational;
use jbboehr\Yumemi\Parser\ExpressionLimitExceededException;
use jbboehr\Yumemi\Parser\ParseException;
use jbboehr\Yumemi\Parser\SourceSpan;
use jbboehr\Yumemi\PointQuantity;
use jbboehr\Yumemi\Quantity;
use jbboehr\Yumemi\Units;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RuntimeConformanceTest extends TestCase
{
    private const SCHEMA = 'yumemi.conformance/v1';

    /** @var array<string, list<string>> */
    private const FIXTURE_GROUPS = [
        'expressions.json' => ['cases', 'roots'],
        'conversions.json' => ['factors', 'conversions'],
        'quantities.json' => [
            'quantityAdditions',
            'quantityOperations',
            'quantityRoots',
            'quantityForms',
            'pointConversions',
            'pointDifferences',
            'pointTranslations',
            'deltaUnits',
        ],
        'numeric-output.json' => [
            'rationalSignificantDecimals',
            'quantitySignificantDecimals',
            'pointSignificantDecimals',
        ],
        'errors.json' => ['cases'],
    ];

    /** @var array<string, class-string<\Throwable>> */
    private const ERROR_CLASSES = [
        'expression-limit' => ExpressionLimitExceededException::class,
        'syntax-error' => ParseException::class,
        'unknown-unit' => UnitNotFoundException::class,
        'incompatible-unit' => IncompatibleUnitException::class,
        'unsupported-syntax' => UnsupportedSyntaxException::class,
        'unsupported-unit-algebra' => UnsupportedUnitAlgebraException::class,
        'unsupported-unit-conversion' => UnsupportedUnitConversionException::class,
        'non-multiplicative-conversion' => NonMultiplicativeConversionException::class,
        'non-exact-root' => NonExactRootException::class,
    ];

    /**
     * @param array<string, mixed> $case
     */
    #[DataProvider('expressionProvider')]
    public function testExpressionConformance(array $case): void
    {
        $context = self::context('expressions.json', $case);
        self::assertKeys($case, ['id', 'input', 'parsed', 'normalized', 'dimension'], $context);

        $input = self::string($case, 'input', $context);
        $units = Units::default();

        self::assertSame(self::string($case, 'parsed', $context), $units->parse($input)->toString(), $context);
        self::assertSame(self::string($case, 'normalized', $context), $units->normalize($input)->toString(), $context);
        self::assertSame(
            self::dimension($case['dimension'] ?? null, $context . '.dimension'),
            $units->dimension($input)->jsonSerialize(),
            $context,
        );
    }

    /**
     * @param array<string, mixed> $case
     */
    #[DataProvider('expressionRootProvider')]
    public function testExpressionRootConformance(array $case): void
    {
        $context = self::context('expressions.json', $case);
        self::assertKeys($case, ['id', 'input', 'degree', 'expected'], $context);

        $actual = Units::default()
            ->parse(self::string($case, 'input', $context))
            ->root(self::integer($case, 'degree', $context));

        self::assertSame(self::string($case, 'expected', $context), $actual->toString(), $context);
    }

    /**
     * @param array<string, mixed> $case
     */
    #[DataProvider('factorProvider')]
    public function testConversionFactorConformance(array $case): void
    {
        $context = self::context('conversions.json', $case);
        self::assertKeys($case, ['id', 'from', 'to', 'expected'], $context);

        $actual = Units::default()->conversionFactor(
            self::string($case, 'from', $context),
            self::string($case, 'to', $context),
        );

        self::assertRational($case['expected'] ?? null, $actual, $context . '.expected');
    }

    /**
     * @param array<string, mixed> $case
     */
    #[DataProvider('conversionProvider')]
    public function testExactConversionConformance(array $case): void
    {
        $context = self::context('conversions.json', $case);
        self::assertKeys($case, ['id', 'value', 'from', 'to', 'expected'], $context);

        $actual = Units::default()->convert(
            self::rational($case['value'] ?? null, $context . '.value'),
            self::string($case, 'from', $context),
            self::string($case, 'to', $context),
        );

        self::assertRational($case['expected'] ?? null, $actual, $context . '.expected');
    }

    /**
     * @param array<string, mixed> $case
     */
    #[DataProvider('quantityAdditionProvider')]
    public function testQuantityAdditionConformance(array $case): void
    {
        $context = self::context('quantities.json', $case);
        self::assertKeys($case, ['id', 'left', 'right', 'expected'], $context);

        $actual = self::quantity($case['left'] ?? null, $context . '.left')
            ->add(self::quantity($case['right'] ?? null, $context . '.right'));

        self::assertQuantity($case['expected'] ?? null, $actual, $context . '.expected');
    }

    /**
     * @param array<string, mixed> $case
     */
    #[DataProvider('quantityOperationProvider')]
    public function testQuantityOperationConformance(array $case): void
    {
        $context = self::context('quantities.json', $case);
        self::assertKeys($case, ['id', 'operation', 'left', 'right', 'expected'], $context);

        $left = self::quantity($case['left'] ?? null, $context . '.left');
        $right = self::quantity($case['right'] ?? null, $context . '.right');
        $operation = self::string($case, 'operation', $context);
        $actual = match ($operation) {
            'multiply' => $left->mul($right),
            'divide' => $left->div($right),
            default => throw new \UnexpectedValueException(sprintf('%s.operation is not supported: %s', $context, $operation)),
        };

        self::assertQuantity($case['expected'] ?? null, $actual, $context . '.expected');
    }

    /**
     * @param array<string, mixed> $case
     */
    #[DataProvider('quantityRootProvider')]
    public function testQuantityRootConformance(array $case): void
    {
        $context = self::context('quantities.json', $case);
        self::assertKeys($case, ['id', 'quantity', 'degree', 'expected'], $context);

        $actual = self::quantity($case['quantity'] ?? null, $context . '.quantity')
            ->root(self::integer($case, 'degree', $context));

        self::assertQuantity($case['expected'] ?? null, $actual, $context . '.expected');
    }

    /**
     * @param array<string, mixed> $case
     */
    #[DataProvider('quantityFormProvider')]
    public function testQuantityFormConformance(array $case): void
    {
        $context = self::context('quantities.json', $case);
        self::assertKeys($case, ['id', 'quantity', 'normalized', 'simplified'], $context);

        $quantity = self::quantity($case['quantity'] ?? null, $context . '.quantity');

        self::assertQuantity($case['normalized'] ?? null, $quantity->normalize(), $context . '.normalized');
        self::assertQuantity($case['simplified'] ?? null, $quantity->simplify(), $context . '.simplified');
    }

    /**
     * @param array<string, mixed> $case
     */
    #[DataProvider('pointConversionProvider')]
    public function testPointConversionConformance(array $case): void
    {
        $context = self::context('quantities.json', $case);
        self::assertKeys($case, ['id', 'point', 'to', 'expected'], $context);

        $actual = self::point($case['point'] ?? null, $context . '.point')
            ->to(self::string($case, 'to', $context));

        self::assertPoint($case['expected'] ?? null, $actual, $context . '.expected');
    }

    /**
     * @param array<string, mixed> $case
     */
    #[DataProvider('pointDifferenceProvider')]
    public function testPointDifferenceConformance(array $case): void
    {
        $context = self::context('quantities.json', $case);
        self::assertKeys($case, ['id', 'left', 'right', 'expected', 'equivalent'], $context);

        $actual = self::point($case['left'] ?? null, $context . '.left')
            ->difference(self::point($case['right'] ?? null, $context . '.right'));

        self::assertQuantity($case['expected'] ?? null, $actual, $context . '.expected');

        $equivalent = self::object($case['equivalent'] ?? null, $context . '.equivalent');
        self::assertKeys($equivalent, ['value', 'unit'], $context . '.equivalent');
        self::assertRational(
            $equivalent['value'] ?? null,
            $actual->valueIn(self::string($equivalent, 'unit', $context . '.equivalent')),
            $context . '.equivalent.value',
        );
    }

    /**
     * @param array<string, mixed> $case
     */
    #[DataProvider('pointTranslationProvider')]
    public function testPointTranslationConformance(array $case): void
    {
        $context = self::context('quantities.json', $case);
        self::assertKeys($case, ['id', 'operation', 'point', 'delta', 'expected'], $context);

        $point = self::point($case['point'] ?? null, $context . '.point');
        $delta = self::quantity($case['delta'] ?? null, $context . '.delta');
        $operation = self::string($case, 'operation', $context);
        $actual = match ($operation) {
            'add' => $point->add($delta),
            'subtract' => $point->sub($delta),
            default => throw new \UnexpectedValueException(sprintf('%s.operation is not supported: %s', $context, $operation)),
        };

        self::assertPoint($case['expected'] ?? null, $actual, $context . '.expected');
    }

    /**
     * @param array<string, mixed> $case
     */
    #[DataProvider('deltaUnitProvider')]
    public function testDeltaUnitConformance(array $case): void
    {
        $context = self::context('quantities.json', $case);
        self::assertKeys($case, ['id', 'unit', 'expected'], $context);

        self::assertSame(
            self::string($case, 'expected', $context),
            Units::default()->deltaUnit(self::string($case, 'unit', $context))->toString(),
            $context,
        );
    }

    /**
     * @param array<string, mixed> $case
     */
    #[DataProvider('rationalSignificantDecimalProvider')]
    public function testRationalSignificantDecimalConformance(array $case): void
    {
        $context = self::context('numeric-output.json', $case);
        self::assertKeys($case, ['id', 'value', 'precision', 'rounding', 'notation', 'expected'], $context);

        $actual = self::rational($case['value'] ?? null, $context . '.value')->toSignificantDecimal(
            self::integer($case, 'precision', $context),
            self::roundingMode($case, $context),
            self::decimalNotation($case, $context),
        );

        self::assertSame(self::string($case, 'expected', $context), $actual, $context);
    }

    /**
     * @param array<string, mixed> $case
     */
    #[DataProvider('quantitySignificantDecimalProvider')]
    public function testQuantitySignificantDecimalConformance(array $case): void
    {
        $context = self::context('numeric-output.json', $case);
        self::assertKeys(
            $case,
            ['id', 'quantity', 'target', 'precision', 'rounding', 'notation', 'expected'],
            $context,
        );

        $actual = self::quantity($case['quantity'] ?? null, $context . '.quantity')->significantDecimalValueIn(
            self::string($case, 'target', $context),
            self::integer($case, 'precision', $context),
            self::roundingMode($case, $context),
            self::decimalNotation($case, $context),
        );

        self::assertSame(self::string($case, 'expected', $context), $actual, $context);
    }

    /**
     * @param array<string, mixed> $case
     */
    #[DataProvider('pointSignificantDecimalProvider')]
    public function testPointSignificantDecimalConformance(array $case): void
    {
        $context = self::context('numeric-output.json', $case);
        self::assertKeys(
            $case,
            ['id', 'point', 'target', 'precision', 'rounding', 'notation', 'expected'],
            $context,
        );

        $actual = self::point($case['point'] ?? null, $context . '.point')->significantDecimalValueIn(
            self::string($case, 'target', $context),
            self::integer($case, 'precision', $context),
            self::roundingMode($case, $context),
            self::decimalNotation($case, $context),
        );

        self::assertSame(self::string($case, 'expected', $context), $actual, $context);
    }

    /**
     * @param array<string, mixed> $case
     */
    #[DataProvider('errorProvider')]
    public function testErrorConformance(array $case): void
    {
        $context = self::context('errors.json', $case);
        $operation = self::string($case, 'operation', $context);
        $expectedKeys = match ($operation) {
            'parse' => ['id', 'operation', 'input', 'error'],
            'factor' => ['id', 'operation', 'from', 'to', 'error'],
            'convert' => ['id', 'operation', 'value', 'from', 'to', 'error'],
            'expression-root' => ['id', 'operation', 'input', 'degree', 'error'],
            'quantity-root' => ['id', 'operation', 'quantity', 'degree', 'error'],
            default => throw new \UnexpectedValueException(sprintf('%s.operation is not supported: %s', $context, $operation)),
        };
        self::assertKeys($case, $expectedKeys, $context, optional: ['span']);

        $error = self::string($case, 'error', $context);
        $expectedClass = self::ERROR_CLASSES[$error] ?? throw new \UnexpectedValueException(sprintf(
            '%s.error is not recognized: %s',
            $context,
            $error,
        ));

        $caught = null;
        try {
            self::executeErrorOperation($case, $operation, $context);
        } catch (\Throwable $exception) {
            $caught = $exception;
        }

        self::assertNotNull($caught, $context . ' did not throw');
        self::assertInstanceOf($expectedClass, $caught, $context);

        if (array_key_exists('span', $case)) {
            self::assertSpan($case['span'], self::exceptionSpan($caught), $context . '.span');
        }
    }

    public function testFixtureIdentifiersAreUniqueAcrossTheCorpus(): void
    {
        $seen = [];

        foreach (self::FIXTURE_GROUPS as $file => $groups) {
            foreach ($groups as $group) {
                foreach (self::group($file, $group) as $case) {
                    $id = self::string($case, 'id', $file . '.' . $group);
                    self::assertArrayNotHasKey($id, $seen, sprintf(
                        'Duplicate conformance case ID %s in %s.%s; first seen in %s.',
                        $id,
                        $file,
                        $group,
                        $seen[$id] ?? '',
                    ));
                    $seen[$id] = $file . '.' . $group;
                }
            }
        }

        self::assertGreaterThanOrEqual(35, count($seen));
    }

    /** @return iterable<string, array{array<string, mixed>}> */
    public static function expressionProvider(): iterable
    {
        yield from self::provider('expressions.json', 'cases');
    }

    /** @return iterable<string, array{array<string, mixed>}> */
    public static function expressionRootProvider(): iterable
    {
        yield from self::provider('expressions.json', 'roots');
    }

    /** @return iterable<string, array{array<string, mixed>}> */
    public static function factorProvider(): iterable
    {
        yield from self::provider('conversions.json', 'factors');
    }

    /** @return iterable<string, array{array<string, mixed>}> */
    public static function conversionProvider(): iterable
    {
        yield from self::provider('conversions.json', 'conversions');
    }

    /** @return iterable<string, array{array<string, mixed>}> */
    public static function quantityAdditionProvider(): iterable
    {
        yield from self::provider('quantities.json', 'quantityAdditions');
    }

    /** @return iterable<string, array{array<string, mixed>}> */
    public static function quantityOperationProvider(): iterable
    {
        yield from self::provider('quantities.json', 'quantityOperations');
    }

    /** @return iterable<string, array{array<string, mixed>}> */
    public static function quantityRootProvider(): iterable
    {
        yield from self::provider('quantities.json', 'quantityRoots');
    }

    /** @return iterable<string, array{array<string, mixed>}> */
    public static function quantityFormProvider(): iterable
    {
        yield from self::provider('quantities.json', 'quantityForms');
    }

    /** @return iterable<string, array{array<string, mixed>}> */
    public static function pointConversionProvider(): iterable
    {
        yield from self::provider('quantities.json', 'pointConversions');
    }

    /** @return iterable<string, array{array<string, mixed>}> */
    public static function pointDifferenceProvider(): iterable
    {
        yield from self::provider('quantities.json', 'pointDifferences');
    }

    /** @return iterable<string, array{array<string, mixed>}> */
    public static function pointTranslationProvider(): iterable
    {
        yield from self::provider('quantities.json', 'pointTranslations');
    }

    /** @return iterable<string, array{array<string, mixed>}> */
    public static function deltaUnitProvider(): iterable
    {
        yield from self::provider('quantities.json', 'deltaUnits');
    }

    /** @return iterable<string, array{array<string, mixed>}> */
    public static function rationalSignificantDecimalProvider(): iterable
    {
        yield from self::provider('numeric-output.json', 'rationalSignificantDecimals');
    }

    /** @return iterable<string, array{array<string, mixed>}> */
    public static function quantitySignificantDecimalProvider(): iterable
    {
        yield from self::provider('numeric-output.json', 'quantitySignificantDecimals');
    }

    /** @return iterable<string, array{array<string, mixed>}> */
    public static function pointSignificantDecimalProvider(): iterable
    {
        yield from self::provider('numeric-output.json', 'pointSignificantDecimals');
    }

    /** @return iterable<string, array{array<string, mixed>}> */
    public static function errorProvider(): iterable
    {
        yield from self::provider('errors.json', 'cases');
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    private static function provider(string $file, string $group): iterable
    {
        foreach (self::group($file, $group) as $case) {
            $id = self::string($case, 'id', $file . '.' . $group);
            yield sprintf('%s:%s', pathinfo($file, PATHINFO_FILENAME), $id) => [$case];
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function group(string $file, string $group): array
    {
        $groups = self::FIXTURE_GROUPS[$file] ?? throw new \UnexpectedValueException('Unknown fixture: ' . $file);
        if (!in_array($group, $groups, true)) {
            throw new \UnexpectedValueException(sprintf('Unknown fixture group: %s.%s', $file, $group));
        }

        $path = __DIR__ . '/v1/' . $file;
        $json = file_get_contents($path);
        if ($json === false) {
            throw new \UnexpectedValueException('Could not read conformance fixture: ' . $path);
        }

        try {
            $document = self::object(json_decode($json, true, flags: JSON_THROW_ON_ERROR), $file);
        } catch (\JsonException $exception) {
            throw new \UnexpectedValueException('Invalid JSON in ' . $file, previous: $exception);
        }

        self::assertKeys($document, ['schema', ...$groups], $file);
        if (self::string($document, 'schema', $file) !== self::SCHEMA) {
            throw new \UnexpectedValueException(sprintf('%s.schema must be %s', $file, self::SCHEMA));
        }

        $cases = [];
        foreach (self::list($document[$group] ?? null, $file . '.' . $group) as $index => $value) {
            $case = self::object($value, sprintf('%s.%s[%d]', $file, $group, $index));
            self::string($case, 'id', sprintf('%s.%s[%d]', $file, $group, $index));
            $cases[] = $case;
        }

        if ($cases === []) {
            throw new \UnexpectedValueException(sprintf('%s.%s must not be empty', $file, $group));
        }

        return $cases;
    }

    /**
     * @param array<string, mixed> $case
     */
    private static function executeErrorOperation(array $case, string $operation, string $context): void
    {
        $units = Units::default();

        match ($operation) {
            'parse' => $units->parse(self::string($case, 'input', $context)),
            'factor' => $units->conversionFactor(
                self::string($case, 'from', $context),
                self::string($case, 'to', $context),
            ),
            'convert' => $units->convert(
                self::rational($case['value'] ?? null, $context . '.value'),
                self::string($case, 'from', $context),
                self::string($case, 'to', $context),
            ),
            'expression-root' => $units
                ->parse(self::string($case, 'input', $context))
                ->root(self::integer($case, 'degree', $context)),
            'quantity-root' => self::quantity($case['quantity'] ?? null, $context . '.quantity')
                ->root(self::integer($case, 'degree', $context)),
            default => throw new \UnexpectedValueException(sprintf('%s.operation is not supported: %s', $context, $operation)),
        };
    }

    /**
     * @param array<string, mixed> $case
     */
    private static function context(string $file, array $case): string
    {
        return sprintf('%s:%s', pathinfo($file, PATHINFO_FILENAME), self::string($case, 'id', $file));
    }

    /**
     * @param array<string, mixed> $case
     */
    private static function decimalNotation(array $case, string $context): DecimalNotation
    {
        return match (self::string($case, 'notation', $context)) {
            'plain' => DecimalNotation::Plain,
            'scientific' => DecimalNotation::Scientific,
            default => throw new \UnexpectedValueException($context . '.notation is not recognized'),
        };
    }

    /**
     * @param array<string, mixed> $case
     */
    private static function roundingMode(array $case, string $context): \RoundingMode
    {
        return match (self::string($case, 'rounding', $context)) {
            'towards-zero' => \RoundingMode::TowardsZero,
            'away-from-zero' => \RoundingMode::AwayFromZero,
            'negative-infinity' => \RoundingMode::NegativeInfinity,
            'positive-infinity' => \RoundingMode::PositiveInfinity,
            'half-away-from-zero' => \RoundingMode::HalfAwayFromZero,
            'half-towards-zero' => \RoundingMode::HalfTowardsZero,
            'half-even' => \RoundingMode::HalfEven,
            'half-odd' => \RoundingMode::HalfOdd,
            default => throw new \UnexpectedValueException($context . '.rounding is not recognized'),
        };
    }

    private static function quantity(mixed $value, string $context): Quantity
    {
        $quantity = self::object($value, $context);
        self::assertKeys($quantity, ['value', 'unit'], $context);

        return Units::default()->quantity(
            self::rational($quantity['value'] ?? null, $context . '.value'),
            self::string($quantity, 'unit', $context),
        );
    }

    private static function point(mixed $value, string $context): PointQuantity
    {
        $point = self::object($value, $context);
        self::assertKeys($point, ['value', 'unit'], $context);

        return Units::default()->point(
            self::rational($point['value'] ?? null, $context . '.value'),
            self::string($point, 'unit', $context),
        );
    }

    private static function assertQuantity(mixed $expected, Quantity $actual, string $context): void
    {
        $quantity = self::object($expected, $context);
        self::assertKeys($quantity, ['value', 'unit'], $context);
        self::assertRational($quantity['value'] ?? null, $actual->value(), $context . '.value');
        self::assertSame(self::string($quantity, 'unit', $context), $actual->unitToString(), $context . '.unit');
    }

    private static function assertPoint(mixed $expected, PointQuantity $actual, string $context): void
    {
        $point = self::object($expected, $context);
        self::assertKeys($point, ['value', 'unit'], $context);
        self::assertRational($point['value'] ?? null, $actual->value(), $context . '.value');
        self::assertSame(self::string($point, 'unit', $context), $actual->unit(), $context . '.unit');
    }

    private static function assertRational(mixed $expected, Rational $actual, string $context): void
    {
        self::assertSame(self::rationalData($expected, $context), $actual->jsonSerialize(), $context);
    }

    private static function rational(mixed $value, string $context): Rational
    {
        $data = self::rationalData($value, $context);

        return new Rational(gmp_init($data['numerator']), gmp_init($data['denominator']));
    }

    /**
     * @return array{numerator: string, denominator: string}
     */
    private static function rationalData(mixed $value, string $context): array
    {
        $data = self::object($value, $context);
        self::assertKeys($data, ['numerator', 'denominator'], $context);
        $numerator = self::string($data, 'numerator', $context);
        $denominator = self::string($data, 'denominator', $context);

        if (preg_match('/^-?(?:0|[1-9][0-9]*)$/D', $numerator) !== 1) {
            throw new \UnexpectedValueException($context . '.numerator must be a canonical decimal integer');
        }
        if (preg_match('/^[1-9][0-9]*$/D', $denominator) !== 1) {
            throw new \UnexpectedValueException($context . '.denominator must be a positive decimal integer');
        }

        $canonical = (new Rational(gmp_init($numerator), gmp_init($denominator)))->jsonSerialize();
        $result = ['numerator' => $numerator, 'denominator' => $denominator];
        if ($canonical !== $result) {
            throw new \UnexpectedValueException($context . ' must be a reduced canonical rational');
        }

        return $result;
    }

    /**
     * @return array{
     *     length: int,
     *     mass: int,
     *     time: int,
     *     electricCurrent: int,
     *     temperature: int,
     *     amountOfSubstance: int,
     *     luminousIntensity: int
     * }
     */
    private static function dimension(mixed $value, string $context): array
    {
        $dimension = self::object($value, $context);
        $keys = [
            'length',
            'mass',
            'time',
            'electricCurrent',
            'temperature',
            'amountOfSubstance',
            'luminousIntensity',
        ];
        self::assertKeys($dimension, $keys, $context);

        $result = [];
        foreach ($keys as $key) {
            $result[$key] = self::integer($dimension, $key, $context);
        }

        return $result;
    }

    private static function assertSpan(mixed $expected, ?SourceSpan $actual, string $context): void
    {
        $span = self::object($expected, $context);
        self::assertKeys($span, ['start', 'end'], $context);
        self::assertNotNull($actual, $context . ' was not retained');
        self::assertSame(self::integer($span, 'start', $context), $actual->start, $context . '.start');
        self::assertSame(self::integer($span, 'end', $context), $actual->end, $context . '.end');
    }

    private static function exceptionSpan(\Throwable $exception): ?SourceSpan
    {
        return match (true) {
            $exception instanceof ExpressionLimitExceededException => $exception->span,
            $exception instanceof ParseException => $exception->span,
            $exception instanceof RuntimeException => $exception->span,
            default => null,
        };
    }

    /**
     * @param array<string, mixed> $value
     * @param list<string> $required
     * @param list<string> $optional
     */
    private static function assertKeys(array $value, array $required, string $context, array $optional = []): void
    {
        $actual = array_keys($value);
        $allowed = [...$required, ...$optional];
        sort($actual);
        sort($allowed);

        if ($actual !== $allowed) {
            $requiredActual = array_keys(array_intersect_key($value, array_fill_keys($required, true)));
            sort($requiredActual);
            $requiredSorted = $required;
            sort($requiredSorted);

            if ($requiredActual !== $requiredSorted) {
                throw new \UnexpectedValueException(sprintf(
                    '%s must contain required keys [%s]; got [%s]',
                    $context,
                    implode(', ', $requiredSorted),
                    implode(', ', $actual),
                ));
            }

            $presentAllowed = array_values(array_intersect($allowed, $actual));
            sort($presentAllowed);
            if ($presentAllowed !== $actual) {
                throw new \UnexpectedValueException(sprintf(
                    '%s contains unknown keys; allowed [%s], got [%s]',
                    $context,
                    implode(', ', $allowed),
                    implode(', ', $actual),
                ));
            }
        }
    }

    /** @return array<string, mixed> */
    private static function object(mixed $value, string $context): array
    {
        if (!is_array($value) || array_is_list($value)) {
            throw new \UnexpectedValueException($context . ' must be a JSON object');
        }

        $result = [];
        foreach ($value as $key => $item) {
            if (!is_string($key)) {
                throw new \UnexpectedValueException($context . ' must use string object keys');
            }

            $result[$key] = $item;
        }

        return $result;
    }

    /** @return list<mixed> */
    private static function list(mixed $value, string $context): array
    {
        if (!is_array($value) || !array_is_list($value)) {
            throw new \UnexpectedValueException($context . ' must be a JSON array');
        }

        return $value;
    }

    /** @param array<string, mixed> $value */
    private static function string(array $value, string $key, string $context): string
    {
        $result = $value[$key] ?? null;
        if (!is_string($result) || $result === '') {
            throw new \UnexpectedValueException(sprintf('%s.%s must be a non-empty string', $context, $key));
        }

        return $result;
    }

    /** @param array<string, mixed> $value */
    private static function integer(array $value, string $key, string $context): int
    {
        $result = $value[$key] ?? null;
        if (!is_int($result)) {
            throw new \UnexpectedValueException(sprintf('%s.%s must be an integer', $context, $key));
        }

        return $result;
    }
}
