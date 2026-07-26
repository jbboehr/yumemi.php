<?php

/**
 * Iudex Mensurarum Mysticarum『夢見』〜ＹＵＭＥＭＩ〜
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

namespace jbboehr\Yumemi\Command;

use jbboehr\Yumemi\Catalog\PhpCatalogExporter;
use jbboehr\Yumemi\Catalog\Udunits2CatalogImporter;

final class GenerateUdunits2CatalogCommand
{
    private const HEADER = <<<'HEADER'
/**
 * Copyright 2008, 2009 University Corporation for Atmospheric Research
 *
 * This file is part of the UDUNITS-2 package. See the file COPYRIGHT in the top-level source-directory of the
 * package for copying and redistribution conditions.
 */
HEADER;

    public function __construct(
        private readonly Udunits2CatalogImporter $importer = new Udunits2CatalogImporter(),
        private readonly PhpCatalogExporter $exporter = new PhpCatalogExporter(),
    ) {
    }

    /**
     * @param list<string> $argv
     */
    public function run(array $argv): int
    {
        array_shift($argv);

        if (count($argv) < 2) {
            fwrite(STDERR, "Usage: bin/generate-udunits2-catalog <output-file> <udunits2-xml>...\n");
            return 1;
        }

        $outputFile = array_shift($argv);

        $catalog = $this->importer->importFiles($argv);
        $bytes = file_put_contents($outputFile, $this->exporter->export($catalog, self::HEADER));

        if ($bytes === false) {
            throw new \RuntimeException('Could not write generated catalog: ' . $outputFile);
        }

        return 0;
    }
}
