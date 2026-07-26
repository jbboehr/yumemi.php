<?php

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
