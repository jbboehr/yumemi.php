<?php

namespace jbboehr\Yumemi\Tests\Parser;

use jbboehr\Yumemi\Formatter\ExprFormatter;
use jbboehr\Yumemi\Units;
use PHPUnit\Framework\TestCase;

final class ParserFormatterRoundTripTest extends TestCase
{
    public function testFormattedParsedExpressionsCanBeParsedAgainWithSameMeaning(): void
    {
        $units = Units::default();

        foreach (self::roundTripInputs() as $input) {
            $parsed = $units->parse($input)->reduce();
            $formatted = ExprFormatter::format($parsed);
            $reparsed = $units->parse($formatted)->reduce();

            $this->assertSame(
                $units->normalize($parsed)->toString(),
                $units->normalize($reparsed)->toString(),
                $input . ' formatted as ' . $formatted,
            );
        }
    }

    /**
     * @return list<string>
     */
    private static function roundTripInputs(): array
    {
        return [
            'meter',
            'meter second',
            'meter / second',
            '(meter / second)^2',
            'second^-2',
            '1.25 meter / second^2',
            '1e-3 kilogram * meter / second^2',
            'centimeter / (foot * second)',
            'kilometer / hour',
            'watt * hour',
            'volt * ampere / watt',
        ];
    }
}
