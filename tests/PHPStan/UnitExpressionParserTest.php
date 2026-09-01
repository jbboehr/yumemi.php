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

use jbboehr\Yumemi\Exception\UnexpectedValueException;
use jbboehr\Yumemi\PHPStan\UnitExpressionParser;
use jbboehr\Yumemi\Registry\UnitRegistry;
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

    public function testRetainsSymbolicSpellingAlongsideCanonicalStaticExpression(): void
    {
        $result = (new UnitExpressionParser())->parse('kilometer * millimeter');

        $this->assertTrue($result->isOk());
        $expression = $result->expression();
        $this->assertSame('meter ^ 2', $expression->displayString);
        $this->assertSame('kilometer * millimeter', $expression->symbolicExpr->toString());
    }

    public function testParsesAffinePointWithExactOriginAndDeltaScale(): void
    {
        $result = (new UnitExpressionParser())->parsePoint(' celsius ');

        $this->assertTrue($result->isOk());
        $expression = $result->expression();
        $this->assertSame('celsius', $expression->displayString);
        $this->assertSame('temperature', $expression->deltaUnit->dimension->toString());
        $this->assertSame('delta_degree_Celsius', $expression->deltaUnit->displayString);
        $this->assertSame('5463/20', $expression->canonicalOrigin->toString());
    }

    public function testPointAliasesAreEquivalent(): void
    {
        $parser = new UnitExpressionParser();
        $celsius = $parser->parsePoint('celsius');
        $degreeCelsius = $parser->parsePoint('degree_Celsius');

        $this->assertTrue($celsius->isOk());
        $this->assertTrue($degreeCelsius->isOk());
        $this->assertTrue($celsius->expression()->equivalent($degreeCelsius->expression()));
    }

    public function testPointCoordinateScalesCanShareADimensionWithoutBeingEquivalent(): void
    {
        $parser = new UnitExpressionParser();
        $celsius = $parser->parsePoint('celsius');
        $fahrenheit = $parser->parsePoint('fahrenheit');
        $kelvin = $parser->parsePoint('kelvin');

        $this->assertTrue($celsius->isOk());
        $this->assertTrue($fahrenheit->isOk());
        $this->assertTrue($kelvin->isOk());
        $this->assertTrue($celsius->expression()->sameDimension($fahrenheit->expression()));
        $this->assertTrue($celsius->expression()->sameDimension($kelvin->expression()));
        $this->assertFalse($celsius->expression()->equivalent($fahrenheit->expression()));
        $this->assertFalse($celsius->expression()->equivalent($kelvin->expression()));
        $this->assertSame('45967/180', $fahrenheit->expression()->canonicalOrigin->toString());
    }

    public function testRejectsCompoundAndLogarithmicPointUnits(): void
    {
        $parser = new UnitExpressionParser();
        $compound = $parser->parsePoint('celsius / second');
        $logarithmic = $parser->parsePoint('B');

        $this->assertFalse($compound->isOk());
        $this->assertStringContainsString('single named coordinate unit', $compound->errorMessage() ?? '');
        $this->assertFalse($logarithmic->isOk());
        $this->assertStringContainsString('logarithmic semantics', $logarithmic->errorMessage() ?? '');
        $this->assertNotNull($logarithmic->errorSpan());
        $this->assertSame(0, $logarithmic->errorSpan()->start);
        $this->assertSame(1, $logarithmic->errorSpan()->end);
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
        $this->assertNotNull($result->errorSpan());
        $this->assertSame(2, $result->errorSpan()->start);
        $this->assertSame(21, $result->errorSpan()->end);
    }

    public function testRejectsMalformedQuantityWithSourceSpan(): void
    {
        $result = (new UnitExpressionParser())->parseQuantityUnit('2 meter * / second');

        $this->assertFalse($result->isOk());
        $span = $result->errorSpan();
        $this->assertNotNull($span);
        $this->assertSame(10, $span->start);
        $this->assertSame(11, $span->end);
        $this->assertStringStartsWith("Syntax error, unexpected '/'", $result->errorMessage() ?? '');
    }

    #[DataProvider('unexpectedFailureProvider')]
    public function testUnexpectedFailuresPropagate(\Closure $parse): void
    {
        $units = new Units(new UnitRegistry([], [
            'orphan' => ['type' => 'alias', 'name' => 'orphan'],
        ]));
        $parser = new UnitExpressionParser($units);

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Catalog alias is missing target: orphan');

        $parse($parser);
    }

    /**
     * @return iterable<string, array{\Closure(UnitExpressionParser): void}>
     */
    public static function unexpectedFailureProvider(): iterable
    {
        yield 'unit' => [
            static function (UnitExpressionParser $parser): void {
                $parser->parse('orphan');
            },
        ];

        yield 'quantity' => [
            static function (UnitExpressionParser $parser): void {
                $parser->parseQuantityUnit('2 orphan');
            },
        ];

        yield 'point' => [
            static function (UnitExpressionParser $parser): void {
                $parser->parsePoint('orphan');
            },
        ];
    }

    public function testRejectsEmptyQuantity(): void
    {
        $result = (new UnitExpressionParser())->parseQuantityUnit('   ');

        $this->assertFalse($result->isOk());
        $this->assertStringContainsString('empty', strtolower($result->errorMessage() ?? ''));
    }

    public function testResourceLimitsBecomeExpectedParseResults(): void
    {
        $parser = new UnitExpressionParser();

        foreach ([
            $parser->parse(str_repeat(' ', 4097)),
            $parser->parseQuantityUnit(str_repeat(' ', 4097)),
            $parser->parsePoint(str_repeat(' ', 4097)),
        ] as $result) {
            $this->assertFalse($result->isOk());
            $this->assertSame(
                'Unit expression exceeds the input byte length limit of 4096 (observed 4097).',
                $result->errorMessage(),
            );
            $this->assertNull($result->errorSpan());
        }
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
        $this->assertSame(
            'Unit not found: metr. Did you mean: meter, metre, degR, year, meters?',
            $message,
        );
        $this->assertNotNull($result->errorSpan());
        $this->assertSame(0, $result->errorSpan()->start);
        $this->assertSame(4, $result->errorSpan()->end);
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
        $this->assertSame('meters', $meters->expression()->symbolicDisplayString());
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

    #[DataProvider('invalidExactNumericExpressionProvider')]
    public function testRejectsInvalidExactNumericExpressions(
        string $unit,
        string $message,
        int $expectedStart,
        int $expectedEnd,
    ): void {
        $parser = new UnitExpressionParser();

        foreach ([$parser->parse($unit), $parser->parseQuantityUnit($unit)] as $result) {
            $this->assertFalse($result->isOk());
            $this->assertStringContainsString($message, $result->errorMessage() ?? '');
            $this->assertNotNull($result->errorSpan());
            $this->assertSame($expectedStart, $result->errorSpan()->start);
            $this->assertSame($expectedEnd, $result->errorSpan()->end);
        }
    }

    #[DataProvider('invalidExactNumericPointDefinitionProvider')]
    public function testRejectsInvalidExactNumericPointDefinitions(string $definition, string $message): void
    {
        $units = new Units(new UnitRegistry([], [
            'kelvin' => ['type' => 'base', 'name' => 'kelvin'],
            'broken_scale' => [
                'type' => 'unit',
                'name' => 'broken_scale',
                'def' => $definition,
            ],
            'broken_point' => [
                'type' => 'unit',
                'name' => 'broken_point',
                'def' => 'broken_scale @ 1',
                'semantics' => 'affine',
            ],
        ]));
        $result = (new UnitExpressionParser($units))->parsePoint(' broken_point ');

        $this->assertFalse($result->isOk());
        $this->assertStringContainsString($message, $result->errorMessage() ?? '');
        $this->assertNotNull($result->errorSpan());
        $this->assertSame(1, $result->errorSpan()->start);
        $this->assertSame(13, $result->errorSpan()->end);
    }

    /**
     * @return iterable<string, array{string, string, int, int}>
     */
    public static function invalidExactNumericExpressionProvider(): iterable
    {
        yield 'division by zero' => [' 1 / 0 ', 'denominator must not be zero', 1, 6];
        yield 'exponent overflow' => [' meter ^ 10001 ', 'exceeds the supported range', 1, 14];
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function invalidExactNumericPointDefinitionProvider(): iterable
    {
        yield 'division by zero' => ['kelvin / 0', 'denominator must not be zero'];
        yield 'exponent overflow' => ['kelvin ^ 10001', 'exceeds the supported range'];
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
        $this->assertNotNull($result->errorSpan());
        $this->assertSame(0, $result->errorSpan()->start);
        $this->assertSame(strlen($unit), $result->errorSpan()->end);
    }

    public function testRejectsUnsupportedSyntaxWithSourceSpan(): void
    {
        $result = (new UnitExpressionParser())->parse(' meter + second ');

        $this->assertFalse($result->isOk());
        $this->assertStringContainsString('Addition and subtraction', $result->errorMessage() ?? '');
        $this->assertNotNull($result->errorSpan());
        $this->assertSame(1, $result->errorSpan()->start);
        $this->assertSame(15, $result->errorSpan()->end);
    }

    public function testNestedDefinitionFailureUsesTheOuterIdentifierSpan(): void
    {
        $registry = new UnitRegistry([], [
            'meter' => ['type' => 'base', 'name' => 'meter'],
            'broken' => ['type' => 'unit', 'name' => 'broken', 'def' => 'missing_dependency'],
            'alias' => ['type' => 'alias', 'name' => 'alias', 'def' => 'broken'],
        ]);
        $result = (new UnitExpressionParser(new Units($registry)))->parse('meter / alias');

        $this->assertFalse($result->isOk());
        $this->assertStringContainsString('missing_dependency', $result->errorMessage() ?? '');
        $this->assertNotNull($result->errorSpan());
        $this->assertSame(8, $result->errorSpan()->start);
        $this->assertSame(13, $result->errorSpan()->end);
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

    public function testUsesCustomPrimitiveDimensionsFromUnits(): void
    {
        $registry = UnitRegistryBuilder::default()
            ->baseUnit('USD', 'currency')
            ->define('EUR = 100 / 107 * USD')
            ->build();
        $parser = new UnitExpressionParser(new Units($registry));

        $dollars = $parser->parse('USD / second');
        $euros = $parser->parse('EUR / second');
        $meters = $parser->parse('meter / second');

        $this->assertTrue($dollars->isOk());
        $this->assertTrue($euros->isOk());
        $this->assertTrue($meters->isOk());
        $this->assertSame('currency / time', $dollars->expression()->dimension->toString());
        $this->assertTrue($dollars->expression()->sameDimension($euros->expression()));
        $this->assertFalse($dollars->expression()->sameDimension($meters->expression()));
        $this->assertFalse($dollars->expression()->equals($euros->expression()));
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
