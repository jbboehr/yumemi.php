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

namespace jbboehr\Yumemi\Tests\Parser;

use jbboehr\Yumemi\Formatter\DivisionStyle;
use jbboehr\Yumemi\Formatter\FormatOptions;
use jbboehr\Yumemi\Formatter\Typography;
use jbboehr\Yumemi\Formatter\UnitNameStyle;
use jbboehr\Yumemi\Units;
use PHPUnit\Framework\TestCase;

final class ParserFormatterRoundTripTest extends TestCase
{
    public function testFormattedParsedExpressionsCanBeParsedAgainWithSameMeaning(): void
    {
        $units = Units::default();

        foreach (self::roundTripInputs() as $input) {
            $parsed = $units->parse($input)->reduce();
            $formatted = $units->formatter()->format($parsed);
            $reparsed = $units->parse($formatted)->reduce();

            $this->assertSame(
                $units->normalize($parsed)->toString(),
                $units->normalize($reparsed)->toString(),
                $input . ' formatted as ' . $formatted,
            );
        }
    }

    public function testUnicodeSymbolOutputCanBeParsedAgainWithSameMeaning(): void
    {
        $units = Units::default();
        $formatter = $units->formatter(new FormatOptions(
            unitNameStyle: UnitNameStyle::Symbol,
            typography: Typography::Unicode,
        ));

        foreach (self::roundTripInputs() as $input) {
            $parsed = $units->parse($input)->reduce();
            $formatted = $formatter->format($parsed);
            $reparsed = $units->parse($formatted)->reduce();

            $this->assertSame(
                $units->normalize($parsed)->toString(),
                $units->normalize($reparsed)->toString(),
                $input . ' formatted as ' . $formatted,
            );
        }
    }

    public function testNegativePowerOutputCanBeParsedAgainWithSameMeaning(): void
    {
        $units = Units::default();
        $options = [
            FormatOptions::create()->withDivisionStyle(DivisionStyle::NegativePowers),
            FormatOptions::create()
                ->withUnitNameStyle(UnitNameStyle::Symbol)
                ->withTypography(Typography::Unicode)
                ->withDivisionStyle(DivisionStyle::NegativePowers),
        ];

        foreach ($options as $formatOptions) {
            $formatter = $units->formatter($formatOptions);

            foreach (self::roundTripInputs() as $input) {
                $parsed = $units->parse($input)->reduce();
                $formatted = $formatter->format($parsed);
                $reparsed = $units->parse($formatted)->reduce();

                $this->assertSame(
                    $units->normalize($parsed)->toString(),
                    $units->normalize($reparsed)->toString(),
                    $input . ' formatted as ' . $formatted,
                );
            }
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
