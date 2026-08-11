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

declare(strict_types=1);

namespace jbboehr\Yumemi\Tests\Documentation;

use PHPUnit\Framework\TestCase;

final class LogionPlateTest extends TestCase
{
    private const BOOK_NAMES = [
        'AWC' => 'Acts of the Western Court',
        'OSD' => 'Ordinances of the Synthetic Dawn',
        'RAS' => 'Revelation of the Artificial Sun',
        'SFA' => 'Scholia of the Fifth Archive',
    ];

    public function testEveryPublicChapterHasOneCanonicalPlateOrTheBannerException(): void
    {
        $projectRoot = dirname(__DIR__, 2);
        $documentationRoot = $projectRoot . '/docs/pages';
        $chapterPaths = self::summaryChapterPaths($documentationRoot . '/SUMMARY.md');

        self::assertSame(
            self::publicMarkdownPaths($documentationRoot),
            $chapterPaths,
            'SUMMARY.md must account for every public Markdown page.',
        );

        $canonicalLogia = self::canonicalLogia([$projectRoot . '/src', $projectRoot . '/bin']);
        $seenCitations = [];
        $pageCitations = [];
        $deliveryImages = [];
        $archivalImages = [];

        foreach ($chapterPaths as $chapterPath) {
            $pagePath = $documentationRoot . '/' . $chapterPath;
            $page = file_get_contents($pagePath);
            self::assertNotFalse($page);

            $plateCount = preg_match_all(
                '/<figure class="logion" data-logion="(?<citation>[^"]+)">(?<plate>.*?)<\/figure>/s',
                $page,
                $plateMatches,
                PREG_SET_ORDER,
            );
            self::assertNotFalse($plateCount);

            if ('README.md' === $chapterPath) {
                self::assertSame(0, $plateCount, 'The Introduction uses its existing banner instead of a logion plate.');

                continue;
            }

            self::assertSame(1, $plateCount, $chapterPath . ' must contain exactly one logion plate.');
            self::assertMatchesRegularExpression(
                '/\A# [^\r\n]+\R\R<figure class="logion"/',
                $page,
                $chapterPath . ' must place its logion plate directly below the page title.',
            );

            $plate = $plateMatches[0];
            $citation = $plate['citation'];
            self::assertArrayNotHasKey($citation, $seenCitations, $citation . ' is used by more than one public page.');
            $seenCitations[$citation] = $chapterPath;
            $pageCitations[$chapterPath] = $citation;

            self::assertArrayHasKey($citation, $canonicalLogia, $citation . ' is not assigned in src/ or bin/.');
            self::assertSame(
                $canonicalLogia[$citation],
                self::plateQuotation($plate['plate']),
                $chapterPath . ' must reproduce the canonical logion text exactly.',
            );

            self::assertMatchesRegularExpression('/^(?<book>[A-Z]{3}) (?<verse>\d+:\d+)$/', $citation);
            [$book, $verse] = explode(' ', $citation, 2);
            self::assertArrayHasKey($book, self::BOOK_NAMES);
            self::assertMatchesRegularExpression(
                '/<p class="logion-citation">\s*—\s*<cite>'
                    . preg_quote(self::BOOK_NAMES[$book] . ' ' . $verse, '/')
                    . '<\/cite>\s*<\/p>/',
                $plate['plate'],
                $chapterPath . ' must show the complete citation in its citation paragraph.',
            );

            $imageCount = preg_match_all(
                '/<img\b(?<attributes>[^>]*)>/',
                $plate['plate'],
                $imageMatches,
                PREG_SET_ORDER,
            );
            self::assertSame(1, $imageCount, $chapterPath . ' must contain one fully described delivery image.');

            $imageAttributes = self::htmlAttributes($imageMatches[0]['attributes']);
            self::assertSame('960', $imageAttributes['width'] ?? null, $chapterPath . ' has an unexpected image width.');
            self::assertSame(
                '540',
                $imageAttributes['height'] ?? null,
                $chapterPath . ' has an unexpected image height.',
            );
            self::assertSame(
                'eager',
                $imageAttributes['loading'] ?? null,
                $chapterPath . ' must eagerly load its first-viewport plate.',
            );
            self::assertSame(
                'high',
                $imageAttributes['fetchpriority'] ?? null,
                $chapterPath . ' must give its first-viewport plate high fetch priority.',
            );

            $imageSource = $imageAttributes['src'] ?? null;
            $imageAlt = $imageAttributes['alt'] ?? null;
            self::assertIsString($imageSource, $chapterPath . ' must provide an image source.');
            self::assertIsString($imageAlt, $chapterPath . ' must provide image alternative text.');
            self::assertNotSame('', trim($imageAlt), $chapterPath . ' must provide meaningful alt text.');
            self::assertStringNotContainsString('-hq', $imageSource, 'Public pages must not embed archival images.');

            $imageStem = str_replace([' ', ':'], ['-', '_'], $citation);
            self::assertSame($imageStem . '.webp', basename($imageSource));

            $deliveryPath = dirname($pagePath) . '/' . $imageSource;
            $archivalPath = $projectRoot . '/docs/development/images/logia/' . $imageStem . '-hq.webp';
            self::assertImageDimensions($deliveryPath, 960, 540);
            self::assertImageDimensions($archivalPath, 3840, 2160);

            $deliveryImages[] = basename($deliveryPath);
            $archivalImages[] = basename($archivalPath);
        }

        sort($deliveryImages);
        sort($archivalImages);
        ksort($pageCitations);

        self::assertSame(self::imageBasenames($documentationRoot . '/images/logia'), $deliveryImages);
        self::assertSame(self::imageBasenames($projectRoot . '/docs/development/images/logia'), $archivalImages);
        self::assertSame(
            self::ledgerPlateAssignments($projectRoot . '/docs/development/logion-plates.md'),
            $pageCitations,
            'The human-readable plate ledger must match the public documentation.',
        );
    }

    /** @return list<string> */
    private static function summaryChapterPaths(string $summaryPath): array
    {
        $summary = file_get_contents($summaryPath);
        self::assertNotFalse($summary);
        $matchCount = preg_match_all('/\[[^]]+]\((?<path>[^)]+\.md)\)/', $summary, $matches);
        self::assertNotFalse($matchCount);
        $paths = $matches['path'];
        sort($paths);

        return $paths;
    }

    /** @return list<string> */
    private static function publicMarkdownPaths(string $documentationRoot): array
    {
        $paths = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($documentationRoot, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo || !$file->isFile() || 'md' !== $file->getExtension()) {
                continue;
            }

            $relativePath = substr($file->getPathname(), strlen($documentationRoot) + 1);
            if ('SUMMARY.md' !== $relativePath) {
                $paths[] = str_replace('\\', '/', $relativePath);
            }
        }

        sort($paths);

        return $paths;
    }

    /**
     * @param list<string> $sourceRoots
     *
     * @return array<string, string>
     */
    private static function canonicalLogia(array $sourceRoots): array
    {
        $logia = [];

        foreach ($sourceRoots as $sourceRoot) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($sourceRoot, \FilesystemIterator::SKIP_DOTS),
            );

            foreach ($iterator as $file) {
                if (!$file instanceof \SplFileInfo || !$file->isFile() || 'php' !== $file->getExtension()) {
                    continue;
                }

                $source = file_get_contents($file->getPathname());
                self::assertNotFalse($source);
                $matchCount = preg_match_all(
                    '/@logion \[(?<citation>[A-Z]{3} \d+:\d+)] (?<text>[^\r\n]*(?:\R\s*\*[ \t]{5}[^\r\n]*)*)/',
                    $source,
                    $matches,
                    PREG_SET_ORDER,
                );
                self::assertNotFalse($matchCount);

                foreach ($matches as $match) {
                    self::assertArrayNotHasKey($match['citation'], $logia);
                    $logia[$match['citation']] = self::normalizeText(
                        preg_replace('/\R\s*\*[ \t]+/', ' ', $match['text']) ?? $match['text'],
                    );
                }
            }
        }

        return $logia;
    }

    private static function plateQuotation(string $plate): string
    {
        $matchCount = preg_match('/<blockquote>\s*<p>(?<quote>.*?)<\/p>\s*<\/blockquote>/s', $plate, $matches);
        self::assertSame(1, $matchCount, 'Every plate must contain exactly one paragraph quotation.');

        return self::normalizeText(strip_tags($matches['quote']));
    }

    private static function normalizeText(string $text): string
    {
        $normalized = preg_replace('/\s+/u', ' ', html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        self::assertNotNull($normalized);

        return trim($normalized);
    }

    /** @return array<string, string> */
    private static function htmlAttributes(string $source): array
    {
        $matchCount = preg_match_all(
            '/(?<name>[a-z][a-z0-9-]*)="(?<value>[^"]*)"/i',
            $source,
            $matches,
            PREG_SET_ORDER,
        );
        self::assertNotFalse($matchCount);

        $attributes = [];
        foreach ($matches as $match) {
            $name = strtolower($match['name']);
            self::assertArrayNotHasKey($name, $attributes, 'HTML attributes must not be repeated.');
            $attributes[$name] = html_entity_decode($match['value'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        return $attributes;
    }

    /** @return array<string, string> */
    private static function ledgerPlateAssignments(string $ledgerPath): array
    {
        $ledger = file_get_contents($ledgerPath);
        self::assertNotFalse($ledger);
        $matchCount = preg_match_all(
            '/^\| `(?<page>[^`]+)`\h+\|\h+(?<citation>[A-Z]{3} \d+:\d+)\h+\|/m',
            $ledger,
            $matches,
            PREG_SET_ORDER,
        );
        self::assertNotFalse($matchCount);

        $assignments = [];
        foreach ($matches as $match) {
            self::assertArrayNotHasKey($match['page'], $assignments);
            $assignments[$match['page']] = $match['citation'];
        }

        ksort($assignments);

        return $assignments;
    }

    private static function assertImageDimensions(string $path, int $width, int $height): void
    {
        self::assertFileExists($path);
        $dimensions = getimagesize($path);
        self::assertNotFalse($dimensions);
        self::assertSame($width, $dimensions[0], $path . ' has an unexpected width.');
        self::assertSame($height, $dimensions[1], $path . ' has an unexpected height.');
    }

    /** @return list<string> */
    private static function imageBasenames(string $directory): array
    {
        $paths = glob($directory . '/*.webp');
        self::assertNotFalse($paths);
        $basenames = array_map(basename(...), $paths);
        sort($basenames);

        return $basenames;
    }
}
