<?php

namespace jbboehr\IudexMensurarumMysteriorum\Tests\Number;

use jbboehr\IudexMensurarumMysteriorum\Number\Rational;
use PHPUnit\Framework\TestCase;

final class RationalTest extends TestCase
{
    public function testAddsRationals(): void
    {
        $this->assertSame('5/6', (new Rational(1, 2))->add(new Rational(1, 3))->toString());
    }

    /**
     * @dataProvider decimalStringProvider
     */
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
}
