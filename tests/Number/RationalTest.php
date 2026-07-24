<?php

namespace jbboehr\IudexMensurarumMysteriorum\Tests\Number;

use jbboehr\IudexMensurarumMysteriorum\Number\Rational;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RationalTest extends TestCase
{
    public function testAddsRationals(): void
    {
        $this->assertSame('5/6', (new Rational(1, 2))->add(new Rational(1, 3))->toString());
    }

    #[DataProvider('decimalStringProvider')]
    public function testParsesDecimalStringsExactly(string $input, string $expected): void
    {
        $this->assertSame($expected, Rational::fromDecimalString($input)->toString());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function decimalStringProvider(): iterable
    {
        yield 'leading zero decimal' => ['0.9', '9/10'];
        yield 'negative leading zero decimal' => ['-0.25', '-1/4'];
        yield 'decimal exponent' => ['0.9972696', '1246587/1250000'];
        yield 'zero decimal' => ['0.0', '0'];
    }

    public function testSubtractsRationals(): void
    {
        $this->assertSame('1/6', (new Rational(1, 2))->sub(new Rational(1, 3))->toString());
    }

    #[DataProvider('integerTruncationProvider')]
    public function testConvertsToIntByTruncatingTowardZero(Rational $rational, int $expected): void
    {
        $this->assertSame($expected, $rational->toInt());
    }

    /**
     * @return iterable<string, array{Rational, int}>
     */
    public static function integerTruncationProvider(): iterable
    {
        yield 'positive fraction' => [new Rational(3, 2), 1];
        yield 'negative fraction' => [new Rational(-3, 2), -1];
        yield 'positive proper fraction' => [new Rational(1, 2), 0];
        yield 'negative proper fraction' => [new Rational(-1, 2), 0];
        yield 'positive mixed fraction' => [new Rational(7, 3), 2];
        yield 'negative mixed fraction' => [new Rational(-7, 3), -2];
    }

    public function testConvertsExactIntegerToInt(): void
    {
        $this->assertSame(42, (new Rational(42))->toIntExact());
        $this->assertSame(-42, (new Rational(-42))->toIntExact());
    }

    public function testExactIntegerConversionRejectsFraction(): void
    {
        $this->expectException(\UnexpectedValueException::class);

        (new Rational(3, 2))->toIntExact();
    }

    public function testIntegerConversionRejectsOverflow(): void
    {
        $this->expectException(\OverflowException::class);

        (new Rational(gmp_add(PHP_INT_MAX, 1)))->toInt();
    }

    public function testExactIntegerConversionRejectsOverflow(): void
    {
        $this->expectException(\OverflowException::class);

        (new Rational(gmp_sub(PHP_INT_MIN, 1)))->toIntExact();
    }
}
