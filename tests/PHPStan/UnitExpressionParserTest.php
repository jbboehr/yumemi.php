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
    }

    public function testRejectsMorphologyFalseFriend(): void
    {
        $parser = new UnitExpressionParser();
        $result = $parser->parse('mass');

        $this->assertFalse($result->isOk());
        $this->assertStringContainsString('Unit not found', $result->errorMessage() ?? '');
    }

    public function testRejectsEmptyString(): void
    {
        $parser = new UnitExpressionParser();
        $result = $parser->parse('   ');

        $this->assertFalse($result->isOk());
        $this->assertStringContainsString('empty', strtolower($result->errorMessage() ?? ''));
    }

    public function testRejectsAffineTemperatureSyntax(): void
    {
        $parser = new UnitExpressionParser();
        $result = $parser->parse('degree_Celsius');

        $this->assertFalse($result->isOk());
        $this->assertStringContainsString('Unsupported', $result->errorMessage() ?? '');
    }

    public function testUsesCustomRegistryFromUnits(): void
    {
        $registry = UnitRegistryBuilder::default()
            ->define('widget = 12 * meter')
            ->build();
        $parser = new UnitExpressionParser(new Units($registry));

        $result = $parser->parse('widget / second');

        $this->assertTrue($result->isOk());
        $this->assertSame('widget / second', $result->expression()->displayString);
        $this->assertSame('length / time', $result->expression()->dimension->toString());
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
