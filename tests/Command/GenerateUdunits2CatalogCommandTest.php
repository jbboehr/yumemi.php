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

namespace jbboehr\Yumemi\Tests\Command;

use jbboehr\Yumemi\Command\GenerateUdunits2CatalogCommand;
use PHPUnit\Framework\TestCase;

final class GenerateUdunits2CatalogCommandTest extends TestCase
{
    private const SAMPLE_XML = <<<'XML'
        <?xml version="1.0" encoding="UTF-8"?>
        <unit-system>
          <unit><base/><name><singular>meter</singular></name><symbol>m</symbol></unit>
          <prefix><name>kilo</name><symbol>k</symbol><value>1000</value></prefix>
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

    public function testGeneratesCatalogFromXml(): void
    {
        $xml = $this->tempFile();
        file_put_contents($xml, self::SAMPLE_XML);
        $output = $this->tempFile();

        $status = (new GenerateUdunits2CatalogCommand())->run(['bin/generate-udunits2-catalog', $output, $xml]);

        $this->assertSame(0, $status);

        $contents = file_get_contents($output);
        $this->assertIsString($contents);
        $this->assertStringStartsWith("<?php", $contents);
        $this->assertStringContainsString('UDUNITS-2 package', $contents);

        $catalog = require $output;
        $this->assertIsArray($catalog);
        $this->assertArrayHasKey('units', $catalog);
        $units = $catalog['units'];
        $this->assertIsArray($units);
        $this->assertArrayHasKey('meter', $units);
        $this->assertSame(['type' => 'alias', 'name' => 'meters', 'def' => 'meter'], $units['meters']);
        $this->assertArrayNotHasKey('ms', $units);
    }

    public function testMissingArgumentsWritesUsageAndReturnsExitCode(): void
    {
        $stderr = fopen('php://memory', 'r+');
        $this->assertNotFalse($stderr);

        $status = (new GenerateUdunits2CatalogCommand(errorStream: $stderr))->run(['bin/generate-udunits2-catalog']);

        $this->assertSame(1, $status);
        rewind($stderr);
        $this->assertStringContainsString('Usage: bin/generate-udunits2-catalog', (string) stream_get_contents($stderr));
        fclose($stderr);
    }

    private function tempFile(): string
    {
        $file = tempnam(sys_get_temp_dir(), 'yumemi-catalog-cmd-');
        $this->assertNotFalse($file);
        $this->tempFiles[] = $file;

        return $file;
    }
}
