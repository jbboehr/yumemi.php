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

namespace jbboehr\Yumemi\Tests;

use jbboehr\Yumemi\Catalog\PrefixDecomposition;
use jbboehr\Yumemi\Catalog\PrefixDescriptor;
use jbboehr\Yumemi\Catalog\UnitDescriptor;
use jbboehr\Yumemi\Dimension;
use jbboehr\Yumemi\Exception\UnexpectedValueException;
use jbboehr\Yumemi\Number\Rational;
use jbboehr\Yumemi\PointQuantity;
use jbboehr\Yumemi\Quantity;
use jbboehr\Yumemi\Registry\UnitRegistryBuilder;
use jbboehr\Yumemi\Units;
use PHPUnit\Framework\TestCase;

final class SerializationTest extends TestCase
{
    public function testRationalProvidesExactJsonDebugAndNativeSerialization(): void
    {
        $value = new Rational(gmp_init('-123456789012345678901234567890'), gmp_init(43));

        $this->assertSame(
            ['numerator' => '-123456789012345678901234567890', 'denominator' => '43'],
            $value->__debugInfo(),
        );
        $this->assertSame(
            '{"numerator":"-123456789012345678901234567890","denominator":"43"}',
            json_encode($value, JSON_THROW_ON_ERROR),
        );

        $restored = unserialize(serialize($value));

        $this->assertInstanceOf(Rational::class, $restored);
        $this->assertTrue($restored->equals($value));
        $this->assertSame($value->toString(), $restored->toString());
    }

    public function testQuantityProvidesCompactJsonAndDebugOutput(): void
    {
        $quantity = Units::default()->quantity(new Rational(1, 3), 'meter / second');

        $debug = $quantity->__debugInfo();
        $this->assertSame($quantity->value(), $debug['value']);
        $this->assertSame('meter / second', $debug['unit']);
        $this->assertSame(Units::class . '#' . spl_object_id(Units::default()), $debug['context']);
        $this->assertSame(
            '{"value":{"numerator":"1","denominator":"3"},"unit":"meter / second"}',
            json_encode($quantity, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
        );

        ob_start();
        var_dump($quantity);
        $output = ob_get_clean();

        $this->assertStringNotContainsString('Udunits2UnitRegistry', $output);
        $this->assertStringNotContainsString('prefixMetadata', $output);
    }

    public function testDefaultQuantityRoundTripPreservesBehaviorAndDefaultContext(): void
    {
        $quantity = Units::default()->quantity(new Rational(2, 7), 'international_foot / second');

        $restored = unserialize(serialize($quantity));

        $this->assertInstanceOf(Quantity::class, $restored);
        $this->assertSame(Units::default(), $restored->units());
        $this->assertSame('2/7', $restored->valueToString());
        $this->assertSame('international_foot / second', $restored->unitToString());
        $this->assertSame('381/4375', $restored->valueIn('meter / second')->toString());
        $this->assertSame('4/7', $restored->mul(2)->valueToString());
    }

    public function testDefaultPointRoundTripPreservesAffineBehaviorAndContext(): void
    {
        $point = Units::default()->point(new Rational(641, 2), 'fahrenheit');

        $restored = unserialize(serialize($point));

        $this->assertInstanceOf(PointQuantity::class, $restored);
        $this->assertSame(Units::default(), $restored->units());
        $this->assertSame('641/2', $restored->valueToString());
        $this->assertSame('2885/18', $restored->valueIn('celsius')->toString());
        $this->assertSame([
            'value' => $restored->value(),
            'unit' => 'fahrenheit',
            'context' => Units::class . '#' . spl_object_id(Units::default()),
        ], $restored->__debugInfo());
        $this->assertSame(
            '{"value":{"numerator":"641","denominator":"2"},"unit":"fahrenheit"}',
            json_encode($restored, JSON_THROW_ON_ERROR),
        );
    }

    public function testCustomValuesRequireAndUseAnExplicitDeserializationContext(): void
    {
        $units = $this->customUnits(2, 10);
        $default = Units::default()->quantity(1, 'meter');
        $quantity = $units->quantity(3, 'widget');
        $point = $units->point(4, $this->customPointName());
        $serialized = serialize([
            'nested' => [
                'default' => $default,
                'quantity' => $quantity,
                'point' => $point,
            ],
        ]);

        try {
            unserialize($serialized);
            self::fail('Raw unserialize() should reject custom-context values.');
        } catch (UnexpectedValueException $exception) {
            $this->assertStringContainsString('Units::deserialize()', $exception->getMessage());
        }

        $restored = $units->deserialize($serialized);

        $this->assertIsArray($restored);
        $this->assertIsArray($restored['nested']);
        $restoredDefault = $restored['nested']['default'];
        $restoredQuantity = $restored['nested']['quantity'];
        $restoredPoint = $restored['nested']['point'];
        $this->assertInstanceOf(Quantity::class, $restoredDefault);
        $this->assertInstanceOf(Quantity::class, $restoredQuantity);
        $this->assertInstanceOf(PointQuantity::class, $restoredPoint);
        $this->assertSame(Units::default(), $restoredDefault->units());
        $this->assertSame($units, $restoredQuantity->units());
        $this->assertSame($units, $restoredPoint->units());
        $this->assertSame('6', $restoredQuantity->valueIn('meter')->toString());
        $this->assertSame('14', $restoredPoint->valueIn('meter')->toString());
    }

    public function testWrongCustomContextIsRejectedAndTheScopeIsRestoredAfterFailure(): void
    {
        $source = $this->customUnits(2, 10);
        $wrong = $this->customUnits(3, 20);
        $serialized = serialize([
            $source->quantity(1, 'widget'),
            $source->point(1, $this->customPointName()),
        ]);

        try {
            $wrong->deserialize($serialized);
            self::fail('A registry with different semantics should be rejected.');
        } catch (UnexpectedValueException $exception) {
            $this->assertStringContainsString('semantics do not match', $exception->getMessage());
        }

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Units::deserialize()');

        unserialize($serialized);
    }

    public function testNestedDeserializationRestoresTheOuterContext(): void
    {
        $outerUnits = $this->customUnits(2, 10);
        $innerUnits = $this->customUnits(3, 20);
        $innerSerialized = serialize($innerUnits->quantity(1, 'widget'));
        $serialized = serialize([
            $outerUnits->quantity(1, 'widget'),
            new NestedDeserializationFixture($innerSerialized),
            $outerUnits->quantity(1, 'widget'),
        ]);

        $restored = $outerUnits->deserialize($serialized);

        $this->assertIsArray($restored);
        $this->assertInstanceOf(Quantity::class, $restored[0]);
        $this->assertInstanceOf(NestedDeserializationFixture::class, $restored[1]);
        $this->assertInstanceOf(Quantity::class, $restored[1]->value);
        $this->assertInstanceOf(Quantity::class, $restored[2]);
        $this->assertSame($outerUnits, $restored[0]->units());
        $this->assertNotSame($outerUnits, $restored[1]->value->units());
        $this->assertSame($outerUnits, $restored[2]->units());
        $this->assertSame('2', $restored[0]->valueIn('meter')->toString());
        $this->assertSame('3', $restored[1]->value->valueIn('meter')->toString());
        $this->assertSame('2', $restored[2]->valueIn('meter')->toString());
    }

    public function testDeserializeForwardsNativeOptions(): void
    {
        $restored = Units::default()->deserialize(
            serialize(Units::default()->quantity(1, 'meter')),
            ['allowed_classes' => false],
        );

        $this->assertInstanceOf(\__PHP_Incomplete_Class::class, $restored);
    }

    public function testDeserializeAcceptsAnExplicitClassAllowList(): void
    {
        $quantity = Units::default()->quantity(1, 'meter');

        $restored = Units::default()->deserialize(
            serialize($quantity),
            ['allowed_classes' => [Quantity::class, Rational::class, Dimension::class, \GMP::class]],
        );

        $this->assertInstanceOf(Quantity::class, $restored);
        $this->assertSame('1', $restored->valueToString());
        $this->assertSame('meter', $restored->unitToString());
    }

    public function testDeserializeForwardsMaximumDepth(): void
    {
        $messages = [];
        set_error_handler(static function (int $severity, string $message) use (&$messages): bool {
            $messages[] = $message;

            return true;
        });

        try {
            $restored = Units::default()->deserialize(serialize([[['value']]]), ['max_depth' => 1]);
        } finally {
            restore_error_handler();
        }

        $this->assertFalse($restored);
        $this->assertStringContainsString('Maximum depth of 1 exceeded', implode("\n", $messages));
    }

    public function testTamperedQuantityAndPointSealsAreRejected(): void
    {
        $quantityData = Units::default()->quantity(1, 'meter')->__serialize();
        $quantityData['normalizedUnit'] = 'second';
        $quantity = (new \ReflectionClass(Quantity::class))->newInstanceWithoutConstructor();

        try {
            $quantity->__unserialize($quantityData);
            self::fail('A tampered quantity seal should be rejected.');
        } catch (UnexpectedValueException $exception) {
            $this->assertStringContainsString('semantics do not match', $exception->getMessage());
        }

        $pointData = Units::default()->point(0, 'celsius')->__serialize();
        $pointData['zeroInNormalizedDeltaUnit'] = new Rational(0);
        $point = (new \ReflectionClass(PointQuantity::class))->newInstanceWithoutConstructor();

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('semantics do not match');

        $point->__unserialize($pointData);
    }

    public function testCustomDimensionSealsRejectARegistryWithDifferentAxisMetadata(): void
    {
        $source = new Units(
            UnitRegistryBuilder::empty()
                ->baseUnit($this->customDimensionUnitName(), 'currency')
                ->define($this->customDimensionPointName() . ' = ' . $this->customDimensionUnitName() . ' @ 10')
                ->build(),
        );
        $wrong = new Units(
            UnitRegistryBuilder::empty()
                ->baseUnit($this->customDimensionUnitName(), 'information')
                ->define($this->customDimensionPointName() . ' = ' . $this->customDimensionUnitName() . ' @ 10')
                ->build(),
        );
        $values = [
            $source->quantity(1, $this->customDimensionUnitName()),
            $source->point(1, $this->customDimensionPointName()),
        ];

        foreach ($values as $value) {
            try {
                $wrong->deserialize(serialize($value));
                self::fail('A registry with different primitive-dimension metadata should be rejected.');
            } catch (UnexpectedValueException $exception) {
                $this->assertStringContainsString('semantics do not match', $exception->getMessage());
            }
        }

        $restored = $source->deserialize(serialize($values));
        $this->assertIsArray($restored);
        $this->assertInstanceOf(Quantity::class, $restored[0]);
        $this->assertInstanceOf(PointQuantity::class, $restored[1]);
        $this->assertSame('currency', $restored[0]->dimension()->toString());
        $this->assertSame('currency', $restored[1]->dimension()->toString());
    }

    public function testLegacyQuantityAndPointPayloadsRemainReadable(): void
    {
        $quantityPayload = Units::default()->quantity(1, 'meter')->__serialize();
        $quantityPayload['version'] = 1;
        unset($quantityPayload['dimension']);
        $quantity = (new \ReflectionClass(Quantity::class))->newInstanceWithoutConstructor();
        $quantity->__unserialize($quantityPayload);

        $pointPayload = Units::default()->point(0, 'celsius')->__serialize();
        $pointPayload['version'] = 1;
        unset($pointPayload['dimension']);
        $point = (new \ReflectionClass(PointQuantity::class))->newInstanceWithoutConstructor();
        $point->__unserialize($pointPayload);

        $this->assertSame('length', $quantity->dimension()->toString());
        $this->assertSame('temperature', $point->dimension()->toString());
    }

    public function testMalformedAndUnknownPayloadVersionsAreRejected(): void
    {
        $rational = (new \ReflectionClass(Rational::class))->newInstanceWithoutConstructor();

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Invalid serialized Rational payload');

        $rational->__unserialize([
            'version' => 2,
            'numerator' => gmp_init(1),
            'denominator' => gmp_init(1),
        ]);
    }

    public function testEverySerializedPayloadFieldIsValidated(): void
    {
        $rational = new Rational(2, 3);
        $this->assertInvalidPayloadVariants(
            $rational->__serialize(),
            [
                'version' => [2, '1'],
                'numerator' => [2, '2'],
                'denominator' => [3, '3', gmp_init(0)],
            ],
            static function (array $payload): void {
                $value = (new \ReflectionClass(Rational::class))->newInstanceWithoutConstructor();
                $value->__unserialize($payload);
            },
            'Rational',
        );

        $quantity = Units::default()->quantity(1, 'meter');
        $this->assertInvalidPayloadVariants(
            $quantity->__serialize(),
            [
                'version' => [1, 3, '2'],
                'context' => [1, 'unknown'],
                'value' => [1, '1'],
                'unit' => [1, []],
                'normalizedUnit' => [1, []],
                'dimension' => [1, 'length'],
            ],
            static function (array $payload): void {
                $value = (new \ReflectionClass(Quantity::class))->newInstanceWithoutConstructor();
                $value->__unserialize($payload);
            },
            'Quantity',
        );

        $point = Units::default()->point(0, 'celsius');
        $this->assertInvalidPayloadVariants(
            $point->__serialize(),
            [
                'version' => [1, 3, '2'],
                'context' => [1, 'unknown'],
                'value' => [0, '0'],
                'unit' => [0, []],
                'normalizedDeltaUnit' => [0, []],
                'zeroInNormalizedDeltaUnit' => [0, '0'],
                'oneInNormalizedDeltaUnit' => [1, '1'],
                'dimension' => [1, 'temperature'],
            ],
            static function (array $payload): void {
                $value = (new \ReflectionClass(PointQuantity::class))->newInstanceWithoutConstructor();
                $value->__unserialize($payload);
            },
            'PointQuantity',
        );

        $dimension = new Dimension(1, 2, 3, 4, 5, 6, 7);
        $this->assertInvalidPayloadVariants(
            $dimension->__serialize(),
            [
                'version' => [1, 3, '2'],
                'powers' => [
                    'not an array',
                    ['length' => 1],
                    [1, 2, 3, 4, 5, 6],
                    [1, 2, 3, 4, 5, 6, '7'],
                ],
                'additionalPowers' => [
                    'not an array',
                    [1],
                    ['Not Valid' => 1],
                    ['dimensionless' => 1],
                    ['currency' => '1'],
                    ['currency' => 10_001],
                    ['length' => 1],
                ],
            ],
            static function (array $payload): void {
                $value = (new \ReflectionClass(Dimension::class))->newInstanceWithoutConstructor();
                $value->__unserialize($payload);
            },
            'Dimension',
        );

        $unit = Units::default()->describe('millifoot');
        $prefix = Units::default()->describePrefix('milli');
        $this->assertNotNull($unit);
        $this->assertNotNull($prefix);
        $decomposition = $unit->prefixDecomposition;
        $this->assertNotNull($decomposition);

        $this->assertInvalidPayloadVariants(
            $prefix->__serialize(),
            [
                'version' => [2, '1'],
                'matchedName' => [1, []],
                'canonicalName' => [1, []],
                'matchedAs' => ['canonical', 1],
                'definitionExpression' => [1, []],
            ],
            static function (array $payload): void {
                $value = (new \ReflectionClass(PrefixDescriptor::class))->newInstanceWithoutConstructor();
                $value->__unserialize($payload);
            },
            'PrefixDescriptor',
        );

        $this->assertInvalidPayloadVariants(
            $decomposition->__serialize(),
            [
                'version' => [2, '1'],
                'prefix' => [1, 'prefix'],
                'unit' => [1, 'unit'],
            ],
            static function (array $payload): void {
                $value = (new \ReflectionClass(PrefixDecomposition::class))->newInstanceWithoutConstructor();
                $value->__unserialize($payload);
            },
            'PrefixDecomposition',
        );

        $this->assertInvalidPayloadVariants(
            $unit->__serialize(),
            [
                'version' => [3, '1'],
                'matchedName' => [1, []],
                'canonicalName' => [1, []],
                'matchedAs' => ['prefixed', 1],
                'kind' => ['derived', 1],
                'definitionExpression' => [1, []],
                'documentation' => [1, []],
                'comment' => [1, []],
                'aliases' => [['alias' => 'foot'], [1]],
                'symbols' => [['symbol' => 'ft'], [1]],
                'explicitPlurals' => [['plural' => 'feet'], [1]],
                'generatedPlurals' => [['plural' => 'foots'], [1]],
                'semantics' => ['multiplicative', 1],
                'prefixDecomposition' => [1, 'prefix'],
                'supportsConversion' => [1, 'true'],
            ],
            static function (array $payload): void {
                $value = (new \ReflectionClass(UnitDescriptor::class))->newInstanceWithoutConstructor();
                $value->__unserialize($payload);
            },
            'UnitDescriptor',
        );
    }

    public function testDimensionProvidesNamedJsonAndRoundTrips(): void
    {
        $dimension = new Dimension(1, 2, -3, 4, -5, 6, -7);
        $expected = [
            'length' => 1,
            'mass' => 2,
            'time' => -3,
            'electricCurrent' => 4,
            'temperature' => -5,
            'amountOfSubstance' => 6,
            'luminousIntensity' => -7,
        ];

        $this->assertSame($expected, $dimension->__debugInfo());
        $this->assertSame(
            $expected,
            json_decode(json_encode($dimension, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR),
        );

        $restored = unserialize(serialize($dimension));

        $this->assertInstanceOf(Dimension::class, $restored);
        $this->assertTrue($restored->equals($dimension));
    }

    public function testExtensionDimensionProvidesJsonAndVersionedRoundTrips(): void
    {
        $dimension = Dimension::fromNamedPowers([
            'length' => 1,
            'currency' => -2,
            'information' => 3,
        ]);
        $expected = [
            'length' => 1,
            'mass' => 0,
            'time' => 0,
            'electricCurrent' => 0,
            'temperature' => 0,
            'amountOfSubstance' => 0,
            'luminousIntensity' => 0,
            'additionalPowers' => [
                'currency' => -2,
                'information' => 3,
            ],
        ];

        $this->assertSame($expected, $dimension->__debugInfo());
        $this->assertSame(
            $expected,
            json_decode(json_encode($dimension, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR),
        );

        $restored = unserialize(serialize($dimension));
        $this->assertInstanceOf(Dimension::class, $restored);
        $this->assertTrue($restored->equals($dimension));

        $legacy = (new \ReflectionClass(Dimension::class))->newInstanceWithoutConstructor();
        $legacy->__unserialize([
            'version' => 1,
            'powers' => [1, 0, -1, 0, 0, 0, 0],
        ]);

        $this->assertSame(['length' => 1, 'time' => -1], $legacy->namedPowers());
        $this->assertSame('length / time', $legacy->toString());
    }

    public function testSerializedDimensionAcceptsExactExponentLimits(): void
    {
        $dimension = (new \ReflectionClass(Dimension::class))->newInstanceWithoutConstructor();
        $dimension->__unserialize([
            'version' => 1,
            'powers' => [-10_000, 10_000, 0, 0, 0, 0, 0],
        ]);

        $this->assertSame(-10_000, $dimension->length());
        $this->assertSame(10_000, $dimension->mass());
    }

    public function testCatalogDescriptorsProvideJsonDebugAndNativeRoundTrips(): void
    {
        $unit = Units::default()->describe('millifoot');
        $prefix = Units::default()->describePrefix('milli');
        $this->assertNotNull($unit);
        $this->assertNotNull($prefix);
        $this->assertNotNull($unit->prefixDecomposition);

        $restoredUnit = unserialize(serialize($unit));
        $restoredPrefix = unserialize(serialize($prefix));
        $restoredDecomposition = unserialize(serialize($unit->prefixDecomposition));

        $this->assertInstanceOf(UnitDescriptor::class, $restoredUnit);
        $this->assertInstanceOf(PrefixDescriptor::class, $restoredPrefix);
        $this->assertInstanceOf(PrefixDecomposition::class, $restoredDecomposition);
        $this->assertEquals($unit, $restoredUnit);
        $this->assertSame($unit->supportsConversion(), $restoredUnit->supportsConversion());
        $this->assertEquals($prefix, $restoredPrefix);
        $this->assertEquals($unit->prefixDecomposition, $restoredDecomposition);

        $legacyPayload = $unit->__serialize();
        $legacyPayload['version'] = 1;
        unset($legacyPayload['supportsConversion']);
        $legacyUnit = (new \ReflectionClass(UnitDescriptor::class))->newInstanceWithoutConstructor();
        $legacyUnit->__unserialize($legacyPayload);
        $this->assertSame($unit->semantics->supportsConversion(), $legacyUnit->supportsConversion());
        $this->assertSame([
            'matchedName' => $prefix->matchedName,
            'canonicalName' => $prefix->canonicalName,
            'matchedAs' => $prefix->matchedAs->value,
            'definitionExpression' => $prefix->definitionExpression,
        ], $prefix->jsonSerialize());
        $this->assertSame([
            'prefix' => $unit->prefixDecomposition->prefix,
            'unit' => $unit->prefixDecomposition->unit,
        ], $unit->prefixDecomposition->jsonSerialize());
        $this->assertSame([
            'matchedName' => $unit->matchedName,
            'canonicalName' => $unit->canonicalName,
            'matchedAs' => $unit->matchedAs->value,
            'kind' => $unit->kind->value,
            'definitionExpression' => $unit->definitionExpression,
            'documentation' => $unit->documentation,
            'comment' => $unit->comment,
            'aliases' => $unit->aliases,
            'symbols' => $unit->symbols,
            'explicitPlurals' => $unit->explicitPlurals,
            'generatedPlurals' => $unit->generatedPlurals,
            'semantics' => $unit->semantics->value,
            'prefixDecomposition' => $unit->prefixDecomposition,
        ], $unit->jsonSerialize());
        $this->assertSame($unit->jsonSerialize(), $unit->__debugInfo());
        $this->assertSame($prefix->jsonSerialize(), $prefix->__debugInfo());
        $this->assertSame($unit->prefixDecomposition->jsonSerialize(), $unit->prefixDecomposition->__debugInfo());

        $json = json_decode(json_encode($unit, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);
        $this->assertIsArray($json);
        $this->assertSame('millifoot', $json['matchedName']);
        $this->assertSame('prefixed', $json['matchedAs']);
        $this->assertSame('multiplicative', $json['semantics']);
        $decomposition = $json['prefixDecomposition'];
        $this->assertIsArray($decomposition);
        $prefixJson = $decomposition['prefix'];
        $unitJson = $decomposition['unit'];
        $this->assertIsArray($prefixJson);
        $this->assertIsArray($unitJson);
        $this->assertSame('milli', $prefixJson['canonicalName']);
        $this->assertSame('international_foot', $unitJson['canonicalName']);
    }

    /**
     * @return Units Custom registry where widget is a length and widget_point is an affine length coordinate.
     */
    private function customUnits(int $widgetScale, int $pointOffset): Units
    {
        return new Units(
            UnitRegistryBuilder::default()
                ->define('widget = ' . $widgetScale . ' * meter')
                ->define('widget_point = meter @ ' . $pointOffset)
                ->build(),
        );
    }

    private function customPointName(): string
    {
        return 'widget_point';
    }

    private function customDimensionUnitName(): string
    {
        return 'credit';
    }

    private function customDimensionPointName(): string
    {
        return 'credit_point';
    }

    /**
     * @param array<array-key, mixed>                         $valid
     * @param array<string, list<mixed>>                      $invalidValues
     * @param callable(array<array-key, mixed>): void         $restore
     */
    private function assertInvalidPayloadVariants(
        array $valid,
        array $invalidValues,
        callable $restore,
        string $type,
    ): void {
        $missing = $valid;
        array_pop($missing);
        $this->assertPayloadRejected($restore, $missing, $type);

        $extra = $valid;
        $extra['unexpected'] = true;
        $this->assertPayloadRejected($restore, $extra, $type);

        foreach ($invalidValues as $field => $values) {
            foreach ($values as $value) {
                $invalid = $valid;
                $invalid[$field] = $value;
                $this->assertPayloadRejected($restore, $invalid, $type);
            }
        }
    }

    /**
     * @param callable(array<array-key, mixed>): void $restore
     * @param array<array-key, mixed>                 $payload
     */
    private function assertPayloadRejected(callable $restore, array $payload, string $type): void
    {
        try {
            $restore($payload);
            self::fail('An invalid serialized ' . $type . ' payload should be rejected.');
        } catch (UnexpectedValueException $exception) {
            $this->assertStringContainsString('serialized ' . $type, $exception->getMessage());
        }
    }
}

final class NestedDeserializationFixture
{
    public mixed $value = null;

    public function __construct(
        private string $serialized,
    ) {
    }

    /**
     * @return array{serialized: string}
     */
    public function __serialize(): array
    {
        return ['serialized' => $this->serialized];
    }

    /**
     * @param array{serialized: string} $data
     */
    public function __unserialize(array $data): void
    {
        $this->serialized = $data['serialized'];
        $units = new Units(
            UnitRegistryBuilder::default()
                ->define('widget = 3 * meter')
                ->define('widget_point = meter @ 20')
                ->build(),
        );
        $this->value = $units->deserialize($this->serialized);
    }
}
