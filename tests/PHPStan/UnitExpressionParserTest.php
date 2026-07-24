<?php

namespace jbboehr\IudexMensurarumMysteriorum\Tests\PHPStan;

use jbboehr\IudexMensurarumMysteriorum\PHPStan\UnitExpressionParser;
use jbboehr\IudexMensurarumMysteriorum\Registry\UnitRegistryBuilder;
use jbboehr\IudexMensurarumMysteriorum\Units;
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
