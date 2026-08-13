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

namespace jbboehr\Yumemi\Tests\Compatibility;

use jbboehr\Yumemi\Catalog\CatalogNameKind;
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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ReleasePersistenceCompatibilityTest extends TestCase
{
    private const FIXTURE_ROOT = __DIR__ . '/fixtures';

    private const CASE_CLASSES = [
        'rational-large-negative' => Rational::class,
        'dimension-fixed-and-custom' => Dimension::class,
        'quantity-default' => Quantity::class,
        'quantity-named-dimension' => Quantity::class,
        'point-default-affine' => PointQuantity::class,
        'prefix-descriptor-milli' => PrefixDescriptor::class,
        'unit-descriptor-millifoot' => UnitDescriptor::class,
        'prefix-decomposition-millifoot' => PrefixDecomposition::class,
    ];

    private const RELEASES = [
        'v0.1.0' => [
            'version' => '0.1.0',
            'sourceReference' => '80d022de4ee0b5d5f7a9656ad307c09602d85452',
            'cases' => [
                'rational-large-negative' => 'rational',
                'dimension-fixed-and-custom' => 'dimension',
                'quantity-default' => 'quantity',
                'quantity-named-dimension' => 'quantity',
                'point-default-affine' => 'point-quantity',
                'custom-registry-graph' => 'custom-registry-graph',
                'prefix-descriptor-milli' => 'prefix-descriptor',
                'unit-descriptor-millifoot' => 'unit-descriptor',
                'prefix-decomposition-millifoot' => 'prefix-decomposition',
            ],
        ],
    ];

    /**
     * @return iterable<string, array{string, string, class-string<object>}>
     */
    public static function defaultFixtureProvider(): iterable
    {
        foreach (self::RELEASES as $directory => $release) {
            foreach ($release['cases'] as $id => $kind) {
                if ($kind === 'custom-registry-graph') {
                    continue;
                }

                if (!array_key_exists($id, self::CASE_CLASSES)) {
                    throw new \LogicException(sprintf(
                        'Release fixture %s/%s has no semantic object assertion.',
                        $directory,
                        $id,
                    ));
                }

                yield $directory . '/' . $id => [$directory, $id, self::CASE_CLASSES[$id]];
            }
        }
    }

    /**
     * @return iterable<string, array{string, string, string, array<string, string>}>
     */
    public static function releaseProvider(): iterable
    {
        foreach (self::RELEASES as $directory => $release) {
            yield $directory => [
                $directory,
                $release['version'],
                $release['sourceReference'],
                $release['cases'],
            ];
        }
    }

    /**
     * @return iterable<string, array{string, array<string, string>}>
     */
    public static function releaseCasesProvider(): iterable
    {
        foreach (self::RELEASES as $directory => $release) {
            yield $directory => [$directory, $release['cases']];
        }
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function customFixtureProvider(): iterable
    {
        foreach (self::RELEASES as $directory => $release) {
            foreach ($release['cases'] as $id => $kind) {
                if ($kind !== 'custom-registry-graph') {
                    continue;
                }

                if ($id !== 'custom-registry-graph') {
                    throw new \LogicException(sprintf(
                        'Release fixture %s/%s has no custom-registry semantic assertion.',
                        $directory,
                        $id,
                    ));
                }

                yield $directory . '/' . $id => [$directory, $id];
            }
        }
    }

    public function testEveryReleaseDirectoryIsExplicitlyRegistered(): void
    {
        $expected = array_keys(self::RELEASES);
        $actual = self::releaseDirectories();
        sort($expected, SORT_NATURAL);
        sort($actual, SORT_NATURAL);

        $this->assertSame($expected, $actual);
    }

    /**
     * @param array<string, string> $expectedCases
     */
    #[DataProvider('releaseProvider')]
    public function testManifestRecordsTheExactReleaseAndCompleteInventory(
        string $directory,
        string $version,
        string $sourceReference,
        array $expectedCases,
    ): void {
        $manifest = self::readJson($directory, 'manifest.json');

        $this->assertSame('yumemi.release-persistence/v1', $manifest['schema'] ?? null);
        $this->assertSame($version, $manifest['release'] ?? null);
        $this->assertSame($sourceReference, $manifest['sourceReference'] ?? null);
        $this->assertIsArray($manifest['producer'] ?? null);
        $this->assertIsString($manifest['producer']['php'] ?? null);
        $this->assertIsString($manifest['producer']['gmp'] ?? null);
        $this->assertIsArray($manifest['cases'] ?? null);

        $actualCases = [];

        foreach ($manifest['cases'] as $case) {
            $this->assertIsArray($case);
            $this->assertIsString($case['id'] ?? null);
            $this->assertIsString($case['kind'] ?? null);
            $this->assertSame('serialized/' . $case['id'] . '.b64', $case['serialized'] ?? null);
            $this->assertSame('json/' . $case['id'] . '.json', $case['json'] ?? null);
            $this->assertFileExists(self::fixturePath($directory, $case['serialized']));
            $this->assertFileExists(self::fixturePath($directory, $case['json']));
            $actualCases[$case['id']] = $case['kind'];
        }

        $this->assertSame($expectedCases, $actualCases);
    }

    /**
     * @param class-string<object> $class
     */
    #[DataProvider('defaultFixtureProvider')]
    public function testCurrentCodeRestoresDefaultContextReleasePayloads(
        string $directory,
        string $id,
        string $class,
    ): void {
        $restored = self::unserializeFixture($directory, $id);

        $this->assertInstanceOf($class, $restored);
        $this->assertDefaultSemantics($id, $restored);

        $reserialized = unserialize(serialize($restored));

        $this->assertInstanceOf($class, $reserialized);
        $this->assertDefaultSemantics($id, $reserialized);
    }

    #[DataProvider('customFixtureProvider')]
    public function testCustomRegistryReleaseGraphRequiresItsOriginalSemantics(string $directory, string $id): void
    {
        $serialized = self::serializedFixture($directory, $id);

        try {
            unserialize($serialized);
            self::fail('Raw unserialize() should reject tagged custom-context values.');
        } catch (UnexpectedValueException $exception) {
            $this->assertStringContainsString('Units::deserialize()', $exception->getMessage());
        }

        $units = self::customUnits(2, 10);
        $restored = $units->deserialize($serialized);

        $this->assertCustomGraphSemantics($restored, $units);
        $this->assertCustomGraphSemantics($units->deserialize(serialize($restored)), $units);
    }

    #[DataProvider('customFixtureProvider')]
    public function testWrongRegistryRejectsTheTaggedCustomGraph(string $directory, string $id): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('semantics do not match');

        self::customUnits(3, 20)->deserialize(self::serializedFixture($directory, $id));
    }

    /**
     * @param array<string, string> $expectedCases
     */
    #[DataProvider('releaseCasesProvider')]
    public function testCurrentObjectsRetainTheTaggedJsonShapes(
        string $directory,
        array $expectedCases,
    ): void {
        foreach ($expectedCases as $id => $kind) {
            if ($kind === 'custom-registry-graph') {
                $value = self::customUnits(2, 10)->deserialize(self::serializedFixture($directory, $id));
            } else {
                $value = self::unserializeFixture($directory, $id);
            }

            $this->assertJsonShape($directory, $id, $value);
        }
    }

    private function assertDefaultSemantics(string $id, object $value): void
    {
        if ($id === 'rational-large-negative') {
            $this->assertInstanceOf(Rational::class, $value);
            $this->assertSame('-123456789012345678901234567890/43', $value->toString());

            return;
        }

        if ($id === 'dimension-fixed-and-custom') {
            $this->assertInstanceOf(Dimension::class, $value);
            $this->assertTrue($value->equals(Dimension::fromNamedPowers([
                'length' => 1,
                'mass' => 2,
                'time' => -3,
                'currency' => -2,
                'information' => 3,
            ])));

            return;
        }

        if ($id === 'quantity-default') {
            $this->assertInstanceOf(Quantity::class, $value);
            $this->assertSame(Units::default(), $value->units());
            $this->assertSame('2/7', $value->valueToString());
            $this->assertSame('international_foot / second', $value->unitToString());
            $this->assertSame('381/4375', $value->valueIn('meter / second')->toString());

            return;
        }

        if ($id === 'quantity-named-dimension') {
            $this->assertInstanceOf(Quantity::class, $value);
            $this->assertSame('24', $value->valueToString());
            $this->assertSame('pixels', $value->unitToString());
            $this->assertSame(Dimension::IMAGE_SAMPLE, $value->dimension()->toString());

            return;
        }

        if ($id === 'point-default-affine') {
            $this->assertInstanceOf(PointQuantity::class, $value);
            $this->assertSame(Units::default(), $value->units());
            $this->assertSame('641/2', $value->valueToString());
            $this->assertSame('2885/18', $value->valueIn('celsius')->toString());

            return;
        }

        if ($id === 'prefix-descriptor-milli') {
            $this->assertInstanceOf(PrefixDescriptor::class, $value);
            $this->assertSame('milli', $value->matchedName);
            $this->assertSame('milli', $value->canonicalName);
            $this->assertSame(CatalogNameKind::Canonical, $value->matchedAs);
            $this->assertSame('1e-3', $value->definitionExpression);

            return;
        }

        if ($id === 'unit-descriptor-millifoot') {
            $this->assertInstanceOf(UnitDescriptor::class, $value);
            $this->assertSame('millifoot', $value->matchedName);
            $this->assertSame('milliinternational_foot', $value->canonicalName);
            $this->assertTrue($value->isDynamicallyPrefixed());
            $this->assertNotNull($value->prefixDecomposition);

            return;
        }

        $this->assertSame('prefix-decomposition-millifoot', $id);
        $this->assertInstanceOf(PrefixDecomposition::class, $value);
        $this->assertSame('milli', $value->prefix->canonicalName);
        $this->assertSame('foot', $value->unit->matchedName);
        $this->assertSame('international_foot', $value->unit->canonicalName);
    }

    private function assertCustomGraphSemantics(mixed $restored, Units $units): void
    {
        $this->assertIsArray($restored);
        $this->assertSame(['default', 'quantity', 'point'], array_keys($restored));
        $default = $restored['default'];
        $quantity = $restored['quantity'];
        $point = $restored['point'];
        $this->assertInstanceOf(Quantity::class, $default);
        $this->assertInstanceOf(Quantity::class, $quantity);
        $this->assertInstanceOf(PointQuantity::class, $point);
        $this->assertSame(Units::default(), $default->units());
        $this->assertSame($units, $quantity->units());
        $this->assertSame($units, $point->units());
        $this->assertSame('1', $default->valueIn('meter')->toString());
        $this->assertSame('6', $quantity->valueIn(self::customBaseUnitName())->toString());
        $this->assertSame('14', $point->valueIn(self::customBaseUnitName())->toString());
    }

    private function assertJsonShape(string $directory, string $id, mixed $value): void
    {
        $expected = self::canonicalizeJson(self::readJson($directory, 'json/' . $id . '.json'));
        $json = json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $actual = self::canonicalizeJson(json_decode($json, true, flags: JSON_THROW_ON_ERROR));

        $this->assertSame($expected, $actual);
    }

    private static function customUnits(int $scale, int $offset): Units
    {
        return new Units(
            UnitRegistryBuilder::empty()
                ->baseUnit('credit', 'currency')
                ->define(sprintf('voucher = %d * credit', $scale))
                ->define(sprintf('credit_point = credit @ %d', $offset))
                ->build(),
        );
    }

    private static function customBaseUnitName(): string
    {
        return 'credit';
    }

    private static function unserializeFixture(string $directory, string $id): mixed
    {
        return unserialize(self::serializedFixture($directory, $id));
    }

    private static function serializedFixture(string $directory, string $id): string
    {
        $encoded = file_get_contents(self::fixturePath($directory, 'serialized/' . $id . '.b64'));

        self::assertIsString($encoded);
        $serialized = base64_decode(trim($encoded), true);
        self::assertIsString($serialized);

        return $serialized;
    }

    /**
     * @return array<array-key, mixed>
     */
    private static function readJson(string $directory, string $path): array
    {
        $json = file_get_contents(self::fixturePath($directory, $path));

        self::assertIsString($json);
        $decoded = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);

        return $decoded;
    }

    /**
     * @return list<string>
     */
    private static function releaseDirectories(): array
    {
        $paths = glob(self::FIXTURE_ROOT . '/v*', GLOB_ONLYDIR);

        if ($paths === false) {
            throw new \RuntimeException('Unable to discover release persistence fixture directories.');
        }

        return array_map(basename(...), $paths);
    }

    private static function fixturePath(string $directory, string $path): string
    {
        return self::FIXTURE_ROOT . '/' . $directory . '/' . $path;
    }

    private static function canonicalizeJson(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        foreach ($value as $key => $item) {
            $value[$key] = self::canonicalizeJson($item);
        }

        if (!array_is_list($value)) {
            ksort($value);
        }

        return $value;
    }
}
