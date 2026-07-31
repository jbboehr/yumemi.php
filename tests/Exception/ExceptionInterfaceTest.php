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

use jbboehr\Yumemi\Exception\DivisionByZeroError;
use jbboehr\Yumemi\Exception\ExceptionInterface;
use jbboehr\Yumemi\Exception\IncompatibleQuantityContextException;
use jbboehr\Yumemi\Exception\IncompatibleUnitException;
use jbboehr\Yumemi\Exception\InvalidArgumentException;
use jbboehr\Yumemi\Exception\LogicException;
use jbboehr\Yumemi\Exception\NonMultiplicativeConversionException;
use jbboehr\Yumemi\Exception\OverflowException;
use jbboehr\Yumemi\Exception\RuntimeException;
use jbboehr\Yumemi\Exception\UnderflowException;
use jbboehr\Yumemi\Exception\UnexpectedValueException;
use jbboehr\Yumemi\Exception\UnitNotFoundException;
use jbboehr\Yumemi\Exception\UnresolvableUnitDimensionException;
use jbboehr\Yumemi\Exception\UnsupportedSyntaxException;
use jbboehr\Yumemi\Exception\UnsupportedUnitAlgebraException;
use jbboehr\Yumemi\Exception\UnsupportedUnitConversionException;
use jbboehr\Yumemi\Number\Rational;
use jbboehr\Yumemi\Parser\ParseException;
use jbboehr\Yumemi\Parser\Parser;
use jbboehr\Yumemi\PHPStan\ConfiguredUnitRegistryProvider;
use jbboehr\Yumemi\PHPStan\UnitExpressionParseResult;
use jbboehr\Yumemi\PHPStan\UnitRegistryFactory;
use jbboehr\Yumemi\Registry\UnitRegistry;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ExceptionInterfaceTest extends TestCase
{
    /**
     * @param class-string<\Throwable> $exceptionClass
     * @param class-string<\Throwable> $nativeClass
     */
    #[DataProvider('exceptionClassProvider')]
    public function testExceptionImplementsCommonAndNativeContracts(string $exceptionClass, string $nativeClass): void
    {
        $this->assertTrue(is_a($exceptionClass, ExceptionInterface::class, true));
        $this->assertTrue(is_a($exceptionClass, $nativeClass, true));
    }

    /**
     * @param \Closure(): void $operation
     * @param class-string<ExceptionInterface> $expectedClass
     */
    #[DataProvider('explicitThrowProvider')]
    public function testExplicitNativeThrowSitesUseYumemiExceptions(
        \Closure $operation,
        string $expectedClass,
    ): void {
        try {
            $operation();
            self::fail('Expected operation to throw.');
        } catch (ExceptionInterface $exception) {
            $this->assertInstanceOf($expectedClass, $exception);
        }
    }

    /**
     * @return iterable<string, array{class-string<\Throwable>, class-string<\Throwable>}>
     */
    public static function exceptionClassProvider(): iterable
    {
        yield 'division by zero error' => [DivisionByZeroError::class, \DivisionByZeroError::class];
        yield 'invalid argument exception' => [InvalidArgumentException::class, \InvalidArgumentException::class];
        yield 'logic exception' => [LogicException::class, \LogicException::class];
        yield 'overflow exception' => [OverflowException::class, \OverflowException::class];
        yield 'runtime exception' => [RuntimeException::class, \RuntimeException::class];
        yield 'underflow exception' => [UnderflowException::class, \UnderflowException::class];
        yield 'unexpected value exception' => [UnexpectedValueException::class, \UnexpectedValueException::class];

        yield 'incompatible quantity context' => [IncompatibleQuantityContextException::class, \RuntimeException::class];
        yield 'incompatible unit' => [IncompatibleUnitException::class, \RuntimeException::class];
        yield 'non-multiplicative conversion' => [NonMultiplicativeConversionException::class, \RuntimeException::class];
        yield 'unit not found' => [UnitNotFoundException::class, \RuntimeException::class];
        yield 'unresolvable unit dimension' => [UnresolvableUnitDimensionException::class, \RuntimeException::class];
        yield 'unsupported syntax' => [UnsupportedSyntaxException::class, \RuntimeException::class];
        yield 'unsupported unit algebra' => [UnsupportedUnitAlgebraException::class, \RuntimeException::class];
        yield 'unsupported unit conversion' => [UnsupportedUnitConversionException::class, \RuntimeException::class];

        yield 'parse exception' => [ParseException::class, \Exception::class];
    }

    /**
     * @return iterable<string, array{\Closure(): void, class-string<ExceptionInterface>}>
     */
    public static function explicitThrowProvider(): iterable
    {
        yield 'division by zero error' => [
            static function (): void {
                new Rational(1, 0);
            },
            DivisionByZeroError::class,
        ];

        yield 'invalid argument exception' => [
            static function (): void {
                Rational::fromDecimalString('invalid');
            },
            InvalidArgumentException::class,
        ];

        yield 'logic exception' => [
            static function (): void {
                UnitExpressionParseResult::invalid('invalid')->expression();
            },
            LogicException::class,
        ];

        yield 'overflow exception' => [
            static function (): void {
                (new Rational(gmp_add(PHP_INT_MAX, 1)))->toInt();
            },
            OverflowException::class,
        ];

        yield 'runtime exception' => [
            static function (): void {
                $factory = new class () implements UnitRegistryFactory {
                    public static function create(): UnitRegistry
                    {
                        throw new \RuntimeException('fixture registry failure');
                    }
                };

                (new ConfiguredUnitRegistryProvider($factory::class))->getRegistry();
            },
            RuntimeException::class,
        ];

        yield 'underflow exception' => [
            static function (): void {
                (new Rational(1, gmp_pow(2, 1075)))->toFloat();
            },
            UnderflowException::class,
        ];

        yield 'unexpected value exception' => [
            static function (): void {
                (new Rational(1, 3))->toDecimalExact();
            },
            UnexpectedValueException::class,
        ];

        yield 'parse exception' => [
            static function (): void {
                Parser::parseString('meter /');
            },
            ParseException::class,
        ];
    }
}
