<?php

namespace jbboehr\IudexMensurarumMysteriorum\Catalog;

use Brick\VarExporter\VarExporter;

final class PhpCatalogExporter
{
    /**
     * @param array<string, mixed> $catalog
     */
    public function export(array $catalog, string $header = ''): string
    {
        if ($header !== '') {
            $header = "\n" . $header . "\n";
        }

        return "<?php\n" . $header . "\nreturn " . VarExporter::export($catalog) . ";\n";
    }
}
