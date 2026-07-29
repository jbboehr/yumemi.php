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

namespace jbboehr\Yumemi\Tests\PHPStan;

use jbboehr\Yumemi\PHPStan\UnitExpressionParser;
use jbboehr\Yumemi\Registry\UnitRegistryBuilder;
use jbboehr\Yumemi\Units;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class UnitExpressionParserTest extends TestCase
{
    public function testParsesKnownCompoundUnit(): void
    {
        $parser = new UnitExpressionParser();
        $result = $parser->parse('meter / second');

        $this->assertTrue($result->isOk());
        $expression = $result->expression();
        $this->assertSame('meter / second', $expression->displayString);
        $this->assertSame('length / time', $expression->dimension->toString());
    }

    #[DataProvider('parsedQuantityUnitProvider')]
    public function testParsesQuantityUnit(string $input, string $expectedUnit): void
    {
        $result = (new UnitExpressionParser())->parseQuantityUnit($input);

        $this->assertTrue($result->isOk());
        $this->assertSame($expectedUnit, $result->expression()->displayString);
    }

    public static function parsedQuantityUnitProvider(): iterable
    {
        yield 'catalog alias' => ['2 foot', 'international_foot'];
        yield 'distributed constants' => ['2 meter / (4 second)', 'meter / second'];
        yield 'powered constant' => ['(2 meter)^2', 'meter ^ 2'];
        yield 'implicit one' => ['meter / second', 'meter / second'];
        yield 'dimensionless' => ['1000', '1'];
    }

    public function testRejectsInvalidQuantity(): void
    {
        $result = (new UnitExpressionParser())->parseQuantityUnit('2 not_a_real_unit_xyz');

        $this->assertFalse($result->isOk());
        $this->assertStringContainsString('Unit not found', $result->errorMessage() ?? '');
    }

    public function testRejectsEmptyQuantity(): void
    {
        $result = (new UnitExpressionParser())->parseQuantityUnit('   ');

        $this->assertFalse($result->isOk());
        $this->assertStringContainsString('empty', strtolower($result->errorMessage() ?? ''));
    }

    public function testParsesReorderedFactorsAsEqual(): void
    {
        $parser = new UnitExpressionParser();
        $left = $parser->parse('meter * second');
        $right = $parser->parse('second * meter');

        $this->assertTrue($left->isOk());
        $this->assertTrue($right->isOk());
        $this->assertTrue($left->expression()->equals($right->expression()));
    }

    public function testRejectsUnknownUnitWithSuggestion(): void
    {
        $parser = new UnitExpressionParser();
        $result = $parser->parse('metr');

        $this->assertFalse($result->isOk());
        $message = $result->errorMessage();
        $this->assertNotNull($message);
        $this->assertStringContainsString('Unit not found', $message);
        $this->assertStringContainsString('Did you mean', $message);
        $this->assertNull($result->errorSpan());
    }

    public function testRejectsMorphologyFalseFriend(): void
    {
        $parser = new UnitExpressionParser();
        $result = $parser->parse('mass');

        $this->assertFalse($result->isOk());
        $this->assertStringContainsString('Unit not found', $result->errorMessage() ?? '');
    }

    public function testUsesGeneratedPluralAliasesWithoutRuntimeMorphology(): void
    {
        $parser = new UnitExpressionParser();

        $meters = $parser->parse('meters');
        $suppressed = $parser->parse('percents');

        $this->assertTrue($meters->isOk());
        $this->assertSame('meter', $meters->expression()->displayString);
        $this->assertFalse($suppressed->isOk());
        $this->assertStringContainsString('Unit not found', $suppressed->errorMessage() ?? '');
    }

    public function testRejectsEmptyString(): void
    {
        $parser = new UnitExpressionParser();
        $result = $parser->parse('   ');

        $this->assertFalse($result->isOk());
        $this->assertStringContainsString('empty', strtolower($result->errorMessage() ?? ''));
    }

    #[DataProvider('unsupportedUnitProvider')]
    public function testRejectsKnownUnsupportedCatalogUnits(string $unit, string $reason): void
    {
        $parser = new UnitExpressionParser();
        $result = $parser->parse($unit);

        $this->assertFalse($result->isOk());
        $this->assertStringContainsString(
            $reason . ' semantics, which are not supported by multiplicative unit algebra',
            $result->errorMessage() ?? '',
        );
        $this->assertNull($result->errorSpan());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function unsupportedUnitProvider(): iterable
    {
        yield 'affine' => ['degree_Celsius', 'affine'];
        yield 'logarithmic' => ['B', 'logarithmic'];
    }

    public function testRejectsMalformedSyntaxWithAMessage(): void
    {
        $parser = new UnitExpressionParser();
        $result = $parser->parse('  meter * / second');

        $this->assertFalse($result->isOk());
        $span = $result->errorSpan();
        $this->assertNotNull($span);
        $this->assertSame(10, $span->start);
        $this->assertSame(11, $span->end);
        $this->assertSame(
            "Syntax error, unexpected '/' at line 1, column 11 (byte offset 10).\n"
                . "|   meter * / second\n"
                . '|           ^',
            $result->errorMessage(),
        );
    }

    public function testUsesCustomRegistryFromUnits(): void
    {
        $registry = UnitRegistryBuilder::default()
            ->define('widget = 12 * meter')
            ->build();
        $parser = new UnitExpressionParser(new Units($registry));

        $result = $parser->parse('widget / second');
        $quantityResult = $parser->parseQuantityUnit('2 widget / second');

        $this->assertTrue($result->isOk());
        $this->assertSame('widget / second', $result->expression()->displayString);
        $this->assertSame('length / time', $result->expression()->dimension->toString());
        $this->assertTrue($quantityResult->isOk());
        $this->assertSame('widget / second', $quantityResult->expression()->displayString);
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    public static function compatiblePairProvider(): array
    {
        return [
            ['meter', 'foot'],
            ['newton', 'kilogram * meter / second^2'],
        ];
    }

    #[DataProvider('compatiblePairProvider')]
    public function testSameDimensionForCompatibleUnits(string $left, string $right): void
    {
        $parser = new UnitExpressionParser();
        $a = $parser->parse($left);
        $b = $parser->parse($right);

        $this->assertTrue($a->isOk());
        $this->assertTrue($b->isOk());
        $this->assertTrue($a->expression()->sameDimension($b->expression()));
        $this->assertFalse($a->expression()->equals($b->expression()));
    }
}
