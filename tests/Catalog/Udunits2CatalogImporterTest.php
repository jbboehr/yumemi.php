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

use jbboehr\Yumemi\Catalog\PhpCatalogExporter;
use jbboehr\Yumemi\Catalog\Udunits2CatalogImporter;
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
        $this->assertSame(['type' => 'alias', 'name' => 'm', 'def' => 'meter'], $catalog['units']['m']);
    }

    public function testImportsUnitMetadataAndPluralAlias(): void
    {
        $units = $this->import(self::SAMPLE)['units'];

        $this->assertSame('unit', $units['second']['type']);
        $this->assertSame('SI base unit of time', $units['second']['definition'] ?? null);
        $this->assertSame('duration', $units['second']['comment'] ?? null);
        $this->assertSame('seconds', $units['second']['plural'] ?? null);

        // Explicit UDUNITS2 plurals are registered as fail-closed aliases.
        $this->assertSame(['type' => 'alias', 'name' => 'seconds', 'def' => 'second'], $units['seconds']);
        $this->assertSame('second', $units['s']['def'] ?? null);
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

    public function testSkipsLogarithmicUnits(): void
    {
        $units = $this->import(self::SAMPLE)['units'];

        $this->assertArrayNotHasKey('bel', $units);
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

        $regex = $catalog['prefixRegex'] ?? null;
        $this->assertNotNull($regex);
        $this->assertSame(1, preg_match($regex, 'kilometer'));
    }

    public function testEmptyFileListIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one UDUNITS2 XML file is required.');

        (new Udunits2CatalogImporter())->importFiles([]);
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

    /**
     * @phpstan-return Udunits2Catalog
     */
    private function import(string $xml): array
    {
        $file = $this->tempFile();
        file_put_contents($file, $xml);

        return (new Udunits2CatalogImporter())->importFiles([$file]);
    }

    private function tempFile(): string
    {
        $file = tempnam(sys_get_temp_dir(), 'yumemi-udunits2-');
        $this->assertNotFalse($file);
        $this->tempFiles[] = $file;

        return $file;
    }
}
