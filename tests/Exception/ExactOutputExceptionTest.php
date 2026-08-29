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

namespace jbboehr\Yumemi\Tests\Exception;

use jbboehr\Yumemi\Exception\ExceptionInterface;
use jbboehr\Yumemi\Exception\IncompatibleExpressionContextException;
use jbboehr\Yumemi\Exception\IncompatibleUnitException;
use jbboehr\Yumemi\Exception\NonExactOutputException;
use jbboehr\Yumemi\Exception\NonIntegralValueException;
use jbboehr\Yumemi\Exception\NonTerminatingDecimalException;
use jbboehr\Yumemi\Exception\OverflowException;
use jbboehr\Yumemi\Exception\UnexpectedValueException;
use jbboehr\Yumemi\Number\Rational;
use jbboehr\Yumemi\Registry\UnitRegistryBuilder;
use jbboehr\Yumemi\Units;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ExactOutputExceptionTest extends TestCase
{
    public function testSharedRecoveryCategoryIsAbstractAndPreservesExistingBroadCatches(): void
    {
        $reflection = new \ReflectionClass(NonExactOutputException::class);

        $this->assertTrue($reflection->isAbstract());
        $this->assertTrue($reflection->isSubclassOf(UnexpectedValueException::class));
        $this->assertTrue($reflection->isSubclassOf(\UnexpectedValueException::class));
        $this->assertTrue($reflection->implementsInterface(ExceptionInterface::class));
    }

    /**
     * @param \Closure(): mixed $operation
     * @param class-string<NonExactOutputException> $expectedClass
     */
    #[DataProvider('exactOutputFailureProvider')]
    public function testConcreteFailuresUseSpecificRecoveryCategories(
        \Closure $operation,
        string $expectedClass,
    ): void {
        $exception = self::capture($operation);

        $this->assertSame($expectedClass, $exception::class);
        $this->assertInstanceOf(NonExactOutputException::class, $exception);
    }

    /**
     * @return iterable<string, array{\Closure(): mixed, class-string<NonExactOutputException>}>
     */
    public static function exactOutputFailureProvider(): iterable
    {
        yield 'normalized negative non-integral value' => [
            static fn (): int => (new Rational(-6, 4))->toIntExact(),
            NonIntegralValueException::class,
        ];

        yield 'normalized negative non-terminating decimal' => [
            static fn (): string => (new Rational(-10, 30))->toDecimalExact(),
            NonTerminatingDecimalException::class,
        ];
    }

    #[DataProvider('nativeIntegerOverflowProvider')]
    public function testIntegralNativeOverflowRemainsOutsideExactOutputCategory(
        \Closure $operation,
        string $convertedValue,
    ): void {
        $exception = self::capture($operation);

        $this->assertSame(OverflowException::class, $exception::class);
        $this->assertSame(
            'Rational value does not fit in a native integer: ' . $convertedValue,
            $exception->getMessage(),
        );
    }

    /**
     * @return iterable<string, array{\Closure(): int, string}>
     */
    public static function nativeIntegerOverflowProvider(): iterable
    {
        $positive = gmp_strval(gmp_add(PHP_INT_MAX, 1));
        $negative = gmp_strval(gmp_sub(PHP_INT_MIN, 1));
        $positiveQuantityInput = new Rational(gmp_mul($positive, 100));
        $negativeQuantityInput = new Rational(gmp_mul($negative, 100));
        $kelvinOffsetTwentieths = 5463;
        $positivePointInput = new Rational(
            gmp_sub(gmp_mul($positive, 20), $kelvinOffsetTwentieths),
            20,
        );
        $negativePointInput = new Rational(
            gmp_sub(gmp_mul($negative, 20), $kelvinOffsetTwentieths),
            20,
        );

        yield 'rational positive overflow' => [
            static fn (): int => (new Rational(gmp_init($positive)))->toIntExact(),
            $positive,
        ];
        yield 'rational negative overflow' => [
            static fn (): int => (new Rational(gmp_init($negative)))->toIntExact(),
            $negative,
        ];
        yield 'quantity positive overflow after multiplicative conversion' => [
            static fn (): int => Units::default()
                ->quantity($positiveQuantityInput, 'centimeter')
                ->exactIntValueIn('meter'),
            $positive,
        ];
        yield 'quantity negative overflow after multiplicative conversion' => [
            static fn (): int => Units::default()
                ->quantity($negativeQuantityInput, 'centimeter')
                ->exactIntValueIn('meter'),
            $negative,
        ];
        yield 'point positive overflow after affine conversion' => [
            static fn (): int => Units::default()
                ->point($positivePointInput, 'celsius')
                ->exactIntValueIn('kelvin'),
            $positive,
        ];
        yield 'point negative overflow after affine conversion' => [
            static fn (): int => Units::default()
                ->point($negativePointInput, 'celsius')
                ->exactIntValueIn('kelvin'),
            $negative,
        ];
    }

    /**
     * @param \Closure(): mixed $operation
     * @param class-string<\Throwable> $expectedClass
     */
    #[DataProvider('conversionFailurePrecedenceProvider')]
    public function testConversionAndContextFailuresTakePrecedenceOverExactOutputClassification(
        \Closure $operation,
        string $expectedClass,
    ): void {
        $exception = self::capture($operation);

        $this->assertSame($expectedClass, $exception::class);
        $this->assertNotInstanceOf(NonExactOutputException::class, $exception);
    }

    /**
     * @return iterable<string, array{\Closure(): mixed, class-string<\Throwable>}>
     */
    public static function conversionFailurePrecedenceProvider(): iterable
    {
        yield 'quantity integer extraction rejects incompatible dimension first' => [
            static fn (): int => Units::default()
                ->quantity(new Rational(1, 2), 'meter')
                ->exactIntValueIn(self::incompatibleQuantityTarget()),
            IncompatibleUnitException::class,
        ];

        yield 'quantity decimal extraction rejects incompatible dimension first' => [
            static fn (): string => Units::default()
                ->quantity(new Rational(1, 3), 'meter')
                ->exactDecimalValueIn(self::incompatibleQuantityTarget()),
            IncompatibleUnitException::class,
        ];

        yield 'quantity integer extraction rejects foreign expression first' => [
            static function (): int {
                $source = new Units(UnitRegistryBuilder::default()->build());
                $foreign = new Units(UnitRegistryBuilder::default()->build());

                return $source
                    ->quantity(new Rational(1, 2), 'meter')
                    ->exactIntValueIn($foreign->parse('meter'));
            },
            IncompatibleExpressionContextException::class,
        ];

        yield 'quantity decimal extraction rejects foreign expression first' => [
            static function (): string {
                $source = new Units(UnitRegistryBuilder::default()->build());
                $foreign = new Units(UnitRegistryBuilder::default()->build());

                return $source
                    ->quantity(new Rational(1, 3), 'meter')
                    ->exactDecimalValueIn($foreign->parse('meter'));
            },
            IncompatibleExpressionContextException::class,
        ];

        yield 'point integer extraction rejects incompatible dimension first' => [
            static fn (): int => Units::default()
                ->point(1, 'fahrenheit')
                ->exactIntValueIn(self::incompatiblePointTarget()),
            IncompatibleUnitException::class,
        ];

        yield 'point decimal extraction rejects incompatible dimension first' => [
            static fn (): string => Units::default()
                ->point(1, 'fahrenheit')
                ->exactDecimalValueIn(self::incompatiblePointTarget()),
            IncompatibleUnitException::class,
        ];
    }

    /** @param \Closure(): mixed $operation */
    private static function capture(\Closure $operation): \Throwable
    {
        try {
            $operation();
        } catch (\Throwable $exception) {
            return $exception;
        }

        self::fail('Expected operation to throw.');
    }

    private static function incompatibleQuantityTarget(): string
    {
        return 'second';
    }

    private static function incompatiblePointTarget(): string
    {
        return 'meter';
    }
}
