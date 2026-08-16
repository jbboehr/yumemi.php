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

use PHPUnit\Framework\TestCase;

final class DiagnosticIdentifierInventoryTest extends TestCase
{
    /** @var list<string> */
    private const PUBLIC_IDENTIFIERS = [
        'yumemi.ambiguousUnitExpression',
        'yumemi.docTagDuplicate',
        'yumemi.docTagParameter',
        'yumemi.docTagSyntax',
        'yumemi.docTagTransform',
        'yumemi.docTagType',
        'yumemi.docTagUnsupported',
        'yumemi.dynamicUnitExpression',
        'yumemi.invalidPointQuantityOperation',
        'yumemi.invalidQuantityArithmetic',
        'yumemi.invalidQuantityComparison',
        'yumemi.invalidQuantityConstruction',
        'yumemi.invalidQuantityConversion',
        'yumemi.invalidUnitAggregation',
        'yumemi.invalidUnitAngleFunction',
        'yumemi.invalidUnitCall',
        'yumemi.invalidUnitComparison',
        'yumemi.invalidUnitMathFunction',
        'yumemi.invalidUnitRange',
        'yumemi.invalidUnitRoot',
        'yumemi.invalidUnitSelection',
    ];

    /** @var list<string> */
    private const INTERNAL_KEYS = [
        'yumemi.unitRegistry',
    ];

    public function testInventoryValuesAreUniqueAndSorted(): void
    {
        $this->assertSame(self::sorted(self::PUBLIC_IDENTIFIERS), self::PUBLIC_IDENTIFIERS);
        $this->assertSame(self::sorted(self::INTERNAL_KEYS), self::INTERNAL_KEYS);
    }

    public function testPublicDocumentationMatchesTheCompatibilityInventory(): void
    {
        $reference = $this->readFile(__DIR__ . '/../../docs/pages/reference/phpstan.md');
        $compatibility = $this->readFile(__DIR__ . '/../../docs/development/compatibility.md');

        $this->assertSame(
            self::PUBLIC_IDENTIFIERS,
            $this->identifiersInSection($reference, '## Diagnostics', '## Limitations'),
            'The PHPStan reference diagnostic list must match the stable compatibility inventory.',
        );
        $this->assertSame(
            self::PUBLIC_IDENTIFIERS,
            $this->identifiersInSection($compatibility, '### Diagnostics', '## Persistent Data'),
            'The compatibility policy diagnostic list must match the stable compatibility inventory.',
        );
    }

    public function testEveryFirstPartyPhpStanKeyIsClassified(): void
    {
        $identifiers = [];
        foreach ($this->phpFilesIn(__DIR__ . '/../../src/PHPStan') as $file) {
            preg_match_all(
                '/[\'\"](yumemi\.[A-Za-z][A-Za-z0-9.]*)[\'\"]/',
                $this->readFile($file),
                $matches,
            );
            array_push($identifiers, ...$matches[1]);
        }

        $this->assertSame(
            self::sorted([...self::PUBLIC_IDENTIFIERS, ...self::INTERNAL_KEYS]),
            self::sorted($identifiers),
            'Every first-party yumemi.* PHPStan key must be classified as a public diagnostic or internal metadata.',
        );
    }

    public function testEveryPublicIdentifierHasARepresentativeLocalIgnore(): void
    {
        $identifiers = [];
        foreach ($this->phpFilesIn(__DIR__) as $file) {
            preg_match_all(
                '/@phpstan-ignore\s+(yumemi\.[A-Za-z][A-Za-z0-9.]*)/',
                $this->readFile($file),
                $matches,
            );
            array_push($identifiers, ...$matches[1]);
        }

        $this->assertSame(
            self::PUBLIC_IDENTIFIERS,
            self::sorted($identifiers),
            'Every public diagnostic must have a representative identifier-specific local-ignore fixture.',
        );
    }

    /** @return list<string> */
    private function identifiersInSection(string $document, string $start, string $end): array
    {
        $startOffset = strpos($document, $start);
        $this->assertNotFalse($startOffset, "Missing section {$start}.");
        $endOffset = strpos($document, $end, $startOffset + strlen($start));
        $this->assertNotFalse($endOffset, "Missing section {$end} after {$start}.");

        preg_match_all(
            '/`(yumemi\.[A-Za-z][A-Za-z0-9.]*)`/',
            substr($document, $startOffset, $endOffset - $startOffset),
            $matches,
        );

        return self::sorted($matches[1]);
    }

    private function readFile(string $file): string
    {
        $contents = file_get_contents($file);
        $this->assertNotFalse($contents, "Unable to read {$file}.");

        return $contents;
    }

    /** @return list<string> */
    private function phpFilesIn(string $path): array
    {
        $directory = new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS);
        $iterator = new \RecursiveIteratorIterator($directory);
        $files = [];

        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo) {
                throw new \LogicException('RecursiveDirectoryIterator must yield SplFileInfo instances.');
            }
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        sort($files);

        return $files;
    }

    /**
     * @param list<string> $values
     * @return list<string>
     */
    private static function sorted(array $values): array
    {
        $values = array_values(array_unique($values));
        sort($values);

        return $values;
    }
}
