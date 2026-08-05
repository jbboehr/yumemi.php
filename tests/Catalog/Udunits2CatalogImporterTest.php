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

namespace jbboehr\Yumemi\Tests\Catalog;

use jbboehr\Yumemi\Catalog\AffineDeltaUnitSynthesizer;
use jbboehr\Yumemi\Catalog\PhpCatalogExporter;
use jbboehr\Yumemi\Catalog\Udunits2CatalogImporter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Covers the UDUNITS2 XML → catalog import pipeline behind bin/generate-udunits2-catalog, which is
 * otherwise only exercised indirectly through the shipped, pre-generated data/udunits2.php.
 *
 * @phpstan-import-type Udunits2Catalog from \jbboehr\Yumemi\Registry\Udunits2UnitRegistry
 */
final class Udunits2CatalogImporterTest extends TestCase
{
    private const SAMPLE = <<<'XML'
        <?xml version="1.0" encoding="UTF-8"?>
        <unit-system>
          <unit>
            <base/>
            <name><singular>meter</singular></name>
            <symbol>m</symbol>
            <aliases><name><singular>metre</singular></name></aliases>
          </unit>
          <unit>
            <name><singular>second</singular><plural>seconds</plural></name>
            <symbol>s</symbol>
            <definition>SI base unit of time</definition>
            <comment>duration</comment>
          </unit>
          <unit>
            <name><singular>hertz</singular></name>
            <symbol>Hz</symbol>
            <def>1/second</def>
            <aliases><singular>cps</singular></aliases>
          </unit>
          <unit>
            <name><singular>radian</singular></name>
            <dimensionless/>
            <def>1</def>
          </unit>
          <unit>
            <name><singular>are_ish</singular></name>
            <def>100 cm2</def>
          </unit>
          <unit>
            <name><singular>bel</singular></name>
            <def>lg(re 1 W)</def>
          </unit>
          <unit>
            <name><singular>arcminute</singular></name>
            <symbol>′</symbol>
          </unit>
          <unit>
            <name><singular>foot</singular><plural>feet</plural></name>
            <def>12 meter</def>
          </unit>
          <unit>
            <def>1</def>
            <aliases><name><singular>pi_constant</singular><noplural/></name></aliases>
          </unit>
          <unit>
            <def>0.01</def>
            <aliases><name><singular>percent</singular></name><noplural/></aliases>
          </unit>
          <unit>
            <name><singular>knot</singular></name>
            <aliases><symbol>kt</symbol><symbol>kts</symbol></aliases>
          </unit>
          <prefix>
            <name>kilo</name>
            <symbol>k</symbol>
            <value>1000</value>
          </prefix>
          <prefix>
            <name>half</name>
            <value>.5</value>
          </prefix>
        </unit-system>
        XML;

    /**
     * @var list<string>
     */
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            @unlink($file);
        }

        $this->tempFiles = [];
    }

    public function testImportsBaseUnitsAndAliases(): void
    {
        $catalog = $this->import(self::SAMPLE);

        $this->assertSame(['meter'], $catalog['base']);
        $this->assertSame(['type' => 'base', 'name' => 'meter'], $catalog['units']['meter']);
        // The symbol is registered as an alias pointing back at the canonical name.
        $this->assertSame(
            ['type' => 'alias', 'name' => 'm', 'def' => 'meter', 'aliasKind' => 'symbol'],
            $catalog['units']['m'],
        );
    }

    public function testImportsUnitMetadataAndPluralAlias(): void
    {
        $units = $this->import(self::SAMPLE)['units'];

        $this->assertSame('unit', $units['second']['type']);
        $this->assertSame('SI base unit of time', $units['second']['definition'] ?? null);
        $this->assertSame('duration', $units['second']['comment'] ?? null);
        $this->assertSame('seconds', $units['second']['plural'] ?? null);

        // Explicit UDUNITS2 plurals are registered as fail-closed aliases.
        $this->assertSame(
            ['type' => 'alias', 'name' => 'seconds', 'def' => 'second', 'aliasKind' => 'explicit_plural'],
            $units['seconds'],
        );
        $this->assertSame('second', $units['s']['def'] ?? null);
    }

    public function testGeneratesImplicitPluralsForCanonicalAndAliasNames(): void
    {
        $units = $this->import(self::SAMPLE)['units'];

        $this->assertSame(
            ['type' => 'alias', 'name' => 'meters', 'def' => 'meter', 'aliasKind' => 'generated_plural'],
            $units['meters'],
        );
        $this->assertSame(
            ['type' => 'alias', 'name' => 'metres', 'def' => 'meter', 'aliasKind' => 'generated_plural'],
            $units['metres'],
        );
    }

    public function testUsesExplicitPluralAndDoesNotPluralizeSymbols(): void
    {
        $units = $this->import(self::SAMPLE)['units'];

        $this->assertSame(
            ['type' => 'alias', 'name' => 'feet', 'def' => 'foot', 'aliasKind' => 'explicit_plural'],
            $units['feet'],
        );
        $this->assertArrayNotHasKey('foots', $units);
        $this->assertSame('symbol', $units['kt']['aliasKind'] ?? null);
        $this->assertSame('symbol', $units['kts']['aliasKind'] ?? null);
        $this->assertArrayNotHasKey('ktses', $units);
        $this->assertArrayNotHasKey('ms', $units);
    }

    public function testHonorsNameAndAliasGroupNoPluralMarkers(): void
    {
        $units = $this->import(self::SAMPLE)['units'];

        $this->assertArrayHasKey('pi_constant', $units);
        $this->assertArrayNotHasKey('pi_constants', $units);
        $this->assertArrayHasKey('percent', $units);
        $this->assertArrayNotHasKey('percents', $units);
    }

    public function testExactIdentifierWinsOverGeneratedPlural(): void
    {
        $xml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <unit-system>
              <unit><name><singular>widget</singular></name><def>1</def></unit>
              <unit><name><singular>widgets</singular><noplural/></name><def>2</def></unit>
            </unit-system>
            XML;

        $units = $this->import($xml)['units'];

        $this->assertSame('unit', $units['widgets']['type']);
        $this->assertSame('2', $units['widgets']['def']);
    }

    public function testAmbiguousGeneratedPluralIsOmitted(): void
    {
        $xml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <unit-system>
              <unit><name><singular>category</singular></name><def>1</def></unit>
              <unit><name><singular>categorie</singular></name><def>2</def></unit>
            </unit-system>
            XML;

        $units = $this->import($xml)['units'];

        $this->assertArrayNotHasKey('categories', $units);
    }

    public function testImportsDefinitionAndExplicitAliases(): void
    {
        $units = $this->import(self::SAMPLE)['units'];

        $this->assertSame('1/second', $units['hertz']['def'] ?? null);
        $this->assertSame('hertz', $units['Hz']['def'] ?? null);
        $this->assertSame('hertz', $units['cps']['def'] ?? null);
        $this->assertSame('dimensionless', $units['radian']['type']);
    }

    public function testNormalizesCm2DefinitionSyntax(): void
    {
        $units = $this->import(self::SAMPLE)['units'];

        $this->assertSame('100 cm ^ 2', $units['are_ish']['def'] ?? null);
    }

    public function testRetainsLogarithmicUnitsAsUnsupported(): void
    {
        $units = $this->import(self::SAMPLE)['units'];

        $this->assertSame('unit', $units['bel']['type']);
        $this->assertSame('lg(re 1 W)', $units['bel']['def']);
        $this->assertSame('logarithmic', $units['bel']['semantics'] ?? null);
    }

    public function testClassifiesAffineUnitsAndSynthesizesDifferenceUnits(): void
    {
        $xml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <unit-system>
              <unit>
                <name><singular>ordinary_widget</singular></name>
                <def>2 meter</def>
              </unit>
              <unit>
                <name><singular>absolute_widget_temperature</singular></name>
                <def>widget_temperature</def>
              </unit>
              <unit>
                <name><singular>widget_temperature</singular></name>
                <aliases><singular>widget_temp</singular></aliases>
                <def>degree_widget</def>
              </unit>
              <unit>
                <name><singular>degree_widget</singular></name>
                <symbol>°W</symbol>
                <def>kelvin @ 273.15</def>
              </unit>
              <unit>
                <name><singular>shifted_widget_temperature</singular></name>
                <def>degree_widget @ 5</def>
              </unit>
            </unit-system>
            XML;

        $units = $this->import($xml)['units'];

        $this->assertSame('kelvin @ 273.15', $units['degree_widget']['def'] ?? null);
        $this->assertSame('affine', $units['degree_widget']['semantics'] ?? null);
        $this->assertSame('affine', $units['widget_temperature']['semantics'] ?? null);
        $this->assertSame('affine', $units['absolute_widget_temperature']['semantics'] ?? null);
        $this->assertSame('affine', $units['shifted_widget_temperature']['semantics'] ?? null);
        $this->assertArrayNotHasKey('semantics', $units['widget_temp']);
        $this->assertArrayNotHasKey('semantics', $units['ordinary_widget']);

        $this->assertSame(
            ['type' => 'unit', 'name' => 'delta_degree_widget', 'def' => 'kelvin'],
            $units['delta_degree_widget'],
        );
        $this->assertSame(
            ['type' => 'alias', 'name' => 'delta_widget_temperature', 'def' => 'delta_degree_widget'],
            $units['delta_widget_temperature'],
        );
        $this->assertSame(
            [
                'type' => 'alias',
                'name' => 'delta_widget_temp',
                'def' => 'delta_widget_temperature',
                'aliasKind' => 'alias',
            ],
            $units['delta_widget_temp'],
        );
        $this->assertSame(
            ['type' => 'alias', 'name' => 'Δ°W', 'def' => 'delta_degree_widget', 'aliasKind' => 'symbol'],
            $units['Δ°W'],
        );
        $this->assertSame(
            [
                'type' => 'alias',
                'name' => 'delta_absolute_widget_temperature',
                'def' => 'delta_widget_temperature',
            ],
            $units['delta_absolute_widget_temperature'],
        );
        $this->assertSame(
            ['type' => 'unit', 'name' => 'delta_shifted_widget_temperature', 'def' => 'delta_degree_widget'],
            $units['delta_shifted_widget_temperature'],
        );
        $this->assertArrayNotHasKey('delta_ordinary_widget', $units);
    }

    public function testDifferenceSynthesisRejectsAnAffineRecordWithoutADefinition(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Affine catalog unit is missing definition: broken_scale');

        AffineDeltaUnitSynthesizer::synthesize([
            'broken_scale' => [
                'type' => 'unit',
                'name' => 'broken_scale',
                'semantics' => 'affine',
            ],
        ]);
    }

    #[DataProvider('compoundAffineDefinitionProvider')]
    public function testDifferenceSynthesisRejectsAnAffineReferenceInsideACompoundExpression(
        string $definition,
        string $rendered,
    ): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Cannot synthesize a difference unit from a compound affine expression: ' . $rendered,
        );

        AffineDeltaUnitSynthesizer::synthesize([
            'degree_widget' => [
                'type' => 'unit',
                'name' => 'degree_widget',
                'def' => 'kelvin @ 100',
                'semantics' => 'affine',
            ],
            'scaled_widget_temperature' => [
                'type' => 'unit',
                'name' => 'scaled_widget_temperature',
                'def' => $definition,
                'semantics' => 'affine',
            ],
        ]);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function compoundAffineDefinitionProvider(): iterable
    {
        yield 'affine identifier in product' => ['2 * degree_widget', '(2 * degree_widget)'];
        yield 'affine origin in product' => ['2 * degree_widget @ 5', '(2 * (degree_widget @ 5))'];
        yield 'affine identifier in quotient' => ['degree_widget / 2', '(degree_widget / 2)'];
        yield 'affine identifier in sum' => ['degree_widget + kelvin', '(degree_widget + kelvin)'];
        yield 'affine identifier in difference' => ['kelvin - degree_widget', '(kelvin - degree_widget)'];
        yield 'affine identifier in power' => ['degree_widget ^ 2', '(degree_widget ^ 2)'];
    }

    public function testDifferenceLinearizationPreservesAnOrdinaryCompoundExpression(): void
    {
        $this->assertSame(
            '(2 * meter)',
            AffineDeltaUnitSynthesizer::linearizeExpression(
                '2 * meter',
                static fn (string $name): ?array => null,
            ),
        );
    }

    public function testRegistersPrimeSymbolAndApostropheAlias(): void
    {
        $units = $this->import(self::SAMPLE)['units'];

        $this->assertSame('arcminute', $units['′']['def'] ?? null);
        $this->assertSame('arcminute', $units["'"]['def'] ?? null);
    }

    public function testImportsPrefixesAndNormalizesLeadingDot(): void
    {
        $catalog = $this->import(self::SAMPLE);

        $this->assertSame('1000', $catalog['prefixes']['kilo']);
        $this->assertSame('1000', $catalog['prefixes']['k']);
        // A leading-dot value is normalized to a leading zero.
        $this->assertSame('0.5', $catalog['prefixes']['half']);
        $this->assertArrayHasKey('prefixMetadata', $catalog);
        $this->assertSame(
            ['name' => 'kilo', 'kind' => 'canonical', 'value' => '1000'],
            $catalog['prefixMetadata']['kilo'],
        );
        $this->assertSame(
            ['name' => 'kilo', 'kind' => 'symbol', 'value' => '1000'],
            $catalog['prefixMetadata']['k'],
        );
        $this->assertSame(
            ['name' => 'half', 'kind' => 'canonical', 'value' => '0.5'],
            $catalog['prefixMetadata']['half'],
        );

        $regex = $catalog['prefixRegex'] ?? null;
        $this->assertNotNull($regex);
        $this->assertSame(1, preg_match($regex, 'kilometer'));
    }

    public function testTrimsImportedUnitAndAliasText(): void
    {
        $xml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <unit-system>
              <unit>
                <name><singular> widget </singular><plural> widgets </plural></name>
                <symbol> W </symbol>
                <aliases>
                  <singular> gadget </singular>
                  <plural> gadgets </plural>
                  <symbol> G </symbol>
                </aliases>
                <definition> documentation </definition>
                <comment> a comment </comment>
                <def> 2 meter </def>
              </unit>
            </unit-system>
            XML;

        $units = $this->import($xml)['units'];

        $this->assertSame('2 meter', $units['widget']['def'] ?? null);
        $this->assertSame('documentation', $units['widget']['definition'] ?? null);
        $this->assertSame('a comment', $units['widget']['comment'] ?? null);
        $this->assertSame('widgets', $units['widget']['plural'] ?? null);
        $this->assertSame('widget', $units['widgets']['def'] ?? null);
        $this->assertSame('widget', $units['W']['def'] ?? null);
        $this->assertSame('widget', $units['G']['def'] ?? null);
        $this->assertSame('widget', $units['gadget']['def'] ?? null);
        $this->assertSame('widget', $units['gadgets']['def'] ?? null);
    }

    public function testCombinesMultipleAliasBlocks(): void
    {
        $xml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <unit-system>
              <unit>
                <name><singular>widget</singular></name>
                <aliases><name><singular>gadget</singular></name><symbol>W</symbol></aliases>
                <aliases><name><singular>device</singular></name><symbol>Wd</symbol></aliases>
                <def>1</def>
              </unit>
            </unit-system>
            XML;

        $units = $this->import($xml)['units'];

        $this->assertSame('widget', $units['gadget']['def'] ?? null);
        $this->assertSame('widget', $units['device']['def'] ?? null);
        $this->assertSame('widget', $units['W']['def'] ?? null);
        $this->assertSame('widget', $units['Wd']['def'] ?? null);
    }

    public function testRepeatedAliasForSameTargetPreservesFirstMetadata(): void
    {
        $xml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <unit-system>
              <unit>
                <name><singular>widget</singular><plural>widgets</plural></name>
                <aliases><name><singular>widgets</singular><noplural/></name></aliases>
                <def>1</def>
              </unit>
            </unit-system>
            XML;

        $units = $this->import($xml)['units'];

        $this->assertSame(
            ['type' => 'alias', 'name' => 'widgets', 'def' => 'widget', 'aliasKind' => 'alias'],
            $units['widgets'],
        );
    }

    public function testAliasCannotCollideWithCanonicalUnit(): void
    {
        $xml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <unit-system>
              <unit><name><singular>widget</singular></name><def>1</def></unit>
              <unit><name><singular>gadget</singular></name><symbol>widget</symbol><def>2</def></unit>
            </unit-system>
            XML;

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Conflicting UDUNITS2 unit or alias name: widget');

        $this->import($xml);
    }

    public function testAliasCannotChangeTargets(): void
    {
        $xml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <unit-system>
              <unit>
                <name><singular>widget</singular></name>
                <aliases><name><singular>thing</singular></name></aliases>
                <def>1</def>
              </unit>
              <unit>
                <name><singular>gadget</singular></name>
                <aliases><name><singular>thing</singular></name></aliases>
                <def>2</def>
              </unit>
            </unit-system>
            XML;

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Conflicting UDUNITS2 unit or alias name: thing');

        $this->import($xml);
    }

    public function testImportsAcrossMultipleFilesBeforeMaterializingPlurals(): void
    {
        $first = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <unit-system>
              <unit><name><singular>widget</singular></name><def>1</def></unit>
              <unit><name><singular>widgets</singular><noplural/></name><def>2</def></unit>
            </unit-system>
            XML;
        $second = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <unit-system>
              <unit><name><singular>gadget</singular></name><def>3</def></unit>
            </unit-system>
            XML;

        $units = $this->importMany($first, $second)['units'];

        $this->assertSame('2', $units['widgets']['def'] ?? null);
        $this->assertSame(
            ['type' => 'alias', 'name' => 'gadgets', 'def' => 'gadget', 'aliasKind' => 'generated_plural'],
            $units['gadgets'],
        );
    }

    public function testImportsSymbolFallbacksAndSkipsUnidentifiedUnits(): void
    {
        $xml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <unit-system>
              <unit><def>ignored</def></unit>
              <unit><symbol>!</symbol><def>1</def></unit>
              <unit><aliases><symbol>@</symbol></aliases><def>2</def></unit>
            </unit-system>
            XML;

        $units = $this->import($xml)['units'];

        $this->assertSame(['!', '@'], array_keys($units));
        $this->assertSame(['type' => 'unit', 'name' => '!', 'def' => '1'], $units['!']);
        $this->assertSame(['type' => 'unit', 'name' => '@', 'def' => '2'], $units['@']);
    }

    public function testPrimarySymbolPrecedesAliasSymbolsForNamelessUnits(): void
    {
        $xml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <unit-system>
              <unit>
                <symbol>!</symbol>
                <aliases><symbol>@</symbol></aliases>
                <def>1</def>
              </unit>
            </unit-system>
            XML;

        $units = $this->import($xml)['units'];

        $this->assertSame(['type' => 'unit', 'name' => '!', 'def' => '1'], $units['!']);
        $this->assertSame(
            ['type' => 'alias', 'name' => '@', 'def' => '!', 'aliasKind' => 'symbol'],
            $units['@'],
        );
    }

    public function testGeneratesPluralFormsForSupportedIdentifierEndings(): void
    {
        $xml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <unit-system>
              <unit><name><singular>category</singular></name><def>1</def></unit>
              <unit><name><singular>boy</singular></name><def>1</def></unit>
              <unit><name><singular>glass</singular></name><def>1</def></unit>
              <unit><name><singular>box</singular></name><def>1</def></unit>
              <unit><name><singular>blitz</singular></name><def>1</def></unit>
              <unit><name><singular>church</singular></name><def>1</def></unit>
              <unit><name><singular>brush</singular></name><def>1</def></unit>
              <unit><name><singular>ab</singular></name><def>1</def></unit>
              <unit><name><singular>Widget</singular></name><def>1</def></unit>
              <unit><name><singular>widget-</singular></name><def>1</def></unit>
            </unit-system>
            XML;

        $units = $this->import($xml)['units'];

        foreach ([
            'categories' => 'category',
            'boys' => 'boy',
            'glasses' => 'glass',
            'boxes' => 'box',
            'blitzes' => 'blitz',
            'churches' => 'church',
            'brushes' => 'brush',
        ] as $plural => $singular) {
            $this->assertSame($singular, $units[$plural]['def'] ?? null);
        }

        $this->assertArrayNotHasKey('abs', $units);
        $this->assertArrayNotHasKey('Widgets', $units);
        $this->assertArrayNotHasKey('widget-s', $units);
    }

    public function testImportsPrefixFallbacksAndEscapesPrefixRegex(): void
    {
        $xml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <unit-system>
              <prefix><name> ignored </name></prefix>
              <prefix><value> 2 </value></prefix>
              <prefix><symbol> q </symbol><value> 3 </value><ignored/></prefix>
              <prefix><name> micro~ </name><symbol> u~ </symbol><value> .25 </value></prefix>
            </unit-system>
            XML;

        $catalog = $this->import($xml);

        $this->assertSame(['q' => '3', 'micro~' => '0.25', 'u~' => '0.25'], $catalog['prefixes']);
        $this->assertArrayHasKey('prefixMetadata', $catalog);
        $this->assertSame(
            ['name' => 'q', 'kind' => 'canonical', 'value' => '3'],
            $catalog['prefixMetadata']['q'],
        );
        $this->assertSame(
            ['name' => 'micro~', 'kind' => 'symbol', 'value' => '0.25'],
            $catalog['prefixMetadata']['u~'],
        );

        $regex = $catalog['prefixRegex'] ?? null;
        $this->assertNotNull($regex);
        $this->assertSame(1, preg_match($regex, 'u~meter', $matches));
        $this->assertSame('u~', $matches[1]);
        $this->assertSame(0, preg_match($regex, 'xu~meter'));
    }

    #[DataProvider('malformedNestedElementProvider')]
    public function testRejectsMalformedNestedElements(string $unit, string $message): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?><unit-system>' . $unit . '</unit-system>';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage($message);

        $this->import($xml);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function malformedNestedElementProvider(): iterable
    {
        yield 'missing singular name' => [
            '<unit><name><plural>widgets</plural></name></unit>',
            'non-empty singular form',
        ];
        yield 'unknown name child' => [
            '<unit><name><singular>widget</singular><bogus/></name></unit>',
            'Unhandled UDUNITS2 name tag: bogus',
        ];
        yield 'unknown alias child' => [
            '<unit><name><singular>widget</singular></name><aliases><bogus/></aliases></unit>',
            'Unhandled UDUNITS2 alias tag: bogus',
        ];
    }

    public function testEmptyFileListIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one UDUNITS2 XML file is required.');

        (new Udunits2CatalogImporter())->importFiles([]);
    }

    public function testUnreadableFileIsRejectedWithoutLeakingANativeWarning(): void
    {
        $file = $this->tempFile();
        unlink($file);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Could not read UDUNITS2 XML file: ' . $file);

        (new Udunits2CatalogImporter())->importFiles([$file]);
    }

    public function testMalformedXmlIsRejectedWithoutLeakingANativeWarning(): void
    {
        $file = $this->tempFile();
        file_put_contents($file, '<unit-system><unit></unit-system>');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Could not parse UDUNITS2 XML file: ' . $file);

        (new Udunits2CatalogImporter())->importFiles([$file]);
    }

    public function testIgnoresCommentsAroundNameElements(): void
    {
        $xml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <unit-system>
              <unit>
                <name><!-- before --><singular>widget</singular><!-- after --></name>
                <def>1</def>
              </unit>
            </unit-system>
            XML;

        $this->assertSame('1', $this->import($xml)['units']['widget']['def'] ?? null);
    }

    public function testDuplicateUnitNameIsRejected(): void
    {
        $duplicate = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <unit-system>
              <unit><base/><name><singular>meter</singular></name></unit>
              <unit><name><singular>meter</singular></name></unit>
            </unit-system>
            XML;

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Already registered UDUNITS2 unit name: meter');

        $this->import($duplicate);
    }

    public function testUnhandledUnitTagIsRejected(): void
    {
        $bogus = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <unit-system>
              <unit><name><singular>widget</singular></name><bogus/></unit>
            </unit-system>
            XML;

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unhandled UDUNITS2 unit tag: bogus');

        $this->import($bogus);
    }

    public function testExporterRoundTripsThePhpCatalog(): void
    {
        $catalog = $this->import(self::SAMPLE);

        $php = (new PhpCatalogExporter())->export($catalog, '// generated by test');
        $this->assertStringStartsWith("<?php", $php);
        $this->assertStringContainsString('// generated by test', $php);

        $file = $this->tempFile();
        file_put_contents($file, $php);

        $restored = require $file;

        $this->assertSame($catalog, $restored);
    }

    public function testExporterHasDeterministicLayoutWithAndWithoutHeader(): void
    {
        $exporter = new PhpCatalogExporter();
        $withoutHeader = <<<'PHP'
            <?php

            return [
                'answer' => 42
            ];
            PHP;
        $withHeader = <<<'PHP'
            <?php

            // generated

            return [
                'answer' => 42
            ];
            PHP;

        $this->assertSame($withoutHeader . "\n", $exporter->export(['answer' => 42]));
        $this->assertSame($withHeader . "\n", $exporter->export(['answer' => 42], '// generated'));
    }

    /**
     * @phpstan-return Udunits2Catalog
     */
    private function import(string $xml): array
    {
        return $this->importMany($xml);
    }

    /**
     * @phpstan-return Udunits2Catalog
     */
    private function importMany(string ...$documents): array
    {
        $files = [];

        foreach ($documents as $xml) {
            $files[] = $file = $this->tempFile();
            file_put_contents($file, $xml);
        }

        return (new Udunits2CatalogImporter())->importFiles($files);
    }

    private function tempFile(): string
    {
        $file = tempnam(sys_get_temp_dir(), 'yumemi-udunits2-');
        $this->assertNotFalse($file);
        $this->tempFiles[] = $file;

        return $file;
    }
}
