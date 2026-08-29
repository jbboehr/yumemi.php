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
use jbboehr\Yumemi\Exception\IncompatibleExpressionContextException;
use jbboehr\Yumemi\Exception\IncompatibleQuantityContextException;
use jbboehr\Yumemi\Exception\IncompatibleUnitException;
use jbboehr\Yumemi\Exception\InvalidArgumentException;
use jbboehr\Yumemi\Exception\LogicException;
use jbboehr\Yumemi\Exception\NonExactOutputException;
use jbboehr\Yumemi\Exception\NonIntegralValueException;
use jbboehr\Yumemi\Exception\NonMultiplicativeConversionException;
use jbboehr\Yumemi\Exception\NonTerminatingDecimalException;
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
use jbboehr\Yumemi\Parser\ExpressionLimitExceededException;
use jbboehr\Yumemi\Parser\ParseException;
use jbboehr\Yumemi\Parser\Parser;
use jbboehr\Yumemi\PHPStan\ConfiguredUnitRegistryProvider;
use jbboehr\Yumemi\PHPStan\ShouldNotHappenException;
use jbboehr\Yumemi\PHPStan\UnitExpressionParseResult;
use jbboehr\Yumemi\PHPStan\UnitRegistryFactory;
use jbboehr\Yumemi\Registry\UnitRegistry;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ExceptionInterfaceTest extends TestCase
{
    /**
     * @param class-string<\Throwable> $exceptionClass
     * @param class-string<\Throwable> $parentClass
     */
    #[DataProvider('exceptionClassProvider')]
    public function testExceptionImplementsExpectedContracts(string $exceptionClass, string $parentClass): void
    {
        $this->assertTrue(is_a($exceptionClass, ExceptionInterface::class, true));
        $this->assertTrue(is_a($exceptionClass, $parentClass, true));
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
        yield 'should not happen exception' => [ShouldNotHappenException::class, \RuntimeException::class];
        yield 'underflow exception' => [UnderflowException::class, \UnderflowException::class];
        yield 'unexpected value exception' => [UnexpectedValueException::class, \UnexpectedValueException::class];
        yield 'non-exact output' => [NonExactOutputException::class, \UnexpectedValueException::class];
        yield 'non-integral value' => [NonIntegralValueException::class, NonExactOutputException::class];
        yield 'non-terminating decimal' => [NonTerminatingDecimalException::class, NonExactOutputException::class];

        yield 'incompatible expression context' => [
            IncompatibleExpressionContextException::class,
            \RuntimeException::class,
        ];
        yield 'incompatible quantity context' => [IncompatibleQuantityContextException::class, \RuntimeException::class];
        yield 'incompatible unit' => [IncompatibleUnitException::class, \RuntimeException::class];
        yield 'non-multiplicative conversion' => [NonMultiplicativeConversionException::class, \RuntimeException::class];
        yield 'unit not found' => [UnitNotFoundException::class, \RuntimeException::class];
        yield 'unresolvable unit dimension' => [UnresolvableUnitDimensionException::class, \RuntimeException::class];
        yield 'unsupported syntax' => [UnsupportedSyntaxException::class, \RuntimeException::class];
        yield 'unsupported unit algebra' => [UnsupportedUnitAlgebraException::class, \RuntimeException::class];
        yield 'unsupported unit conversion' => [UnsupportedUnitConversionException::class, \RuntimeException::class];

        yield 'parse exception' => [ParseException::class, \Exception::class];
        yield 'expression limit exceeded' => [ExpressionLimitExceededException::class, \LengthException::class];
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

        yield 'non-terminating decimal exception' => [
            static function (): void {
                (new Rational(1, 3))->toDecimalExact();
            },
            NonTerminatingDecimalException::class,
        ];

        yield 'non-integral value exception' => [
            static function (): void {
                (new Rational(3, 2))->toIntExact();
            },
            NonIntegralValueException::class,
        ];

        yield 'parse exception' => [
            static function (): void {
                Parser::parseString('meter /');
            },
            ParseException::class,
        ];

        yield 'expression limit exceeded' => [
            static function (): void {
                Parser::parseString(str_repeat('a', 1025));
            },
            ExpressionLimitExceededException::class,
        ];
    }
}
