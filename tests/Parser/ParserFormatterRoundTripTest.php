<?php

/**
 * Iudex Mensurarum Mysticarum『夢見』〜ＹＵＭＥＭＩ〜
 *
 * Copyright (c) anno Domini nostri Jesu Christi MMXXVI, John Boehr & contributors
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

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
