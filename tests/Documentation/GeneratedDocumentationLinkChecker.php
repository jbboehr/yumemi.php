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

namespace jbboehr\Yumemi\Tests\Documentation;

final class GeneratedDocumentationLinkChecker
{
    /** @var array<string, array<string, true>> */
    private array $fragmentCache = [];

    /**
     * @return list<string>
     */
    public function check(string $root): array
    {
        $this->fragmentCache = [];
        $root = realpath($root);
        if ($root === false || !is_dir($root)) {
            throw new \InvalidArgumentException('Generated documentation directory does not exist.');
        }

        $files = $this->htmlFiles($root);
        if ($files === []) {
            throw new \RuntimeException('Generated documentation directory contains no HTML files.');
        }

        $errors = [];

        foreach ($files as $file) {
            $document = $this->loadDocument($file);
            $xpath = new \DOMXPath($document);
            $nodes = $xpath->query('//a[@href] | //img[@src] | //script[@src] | //link[@href]');
            if ($nodes === false) {
                throw new \RuntimeException('Unable to query generated documentation file ' . $file);
            }

            foreach ($nodes as $node) {
                if (!$node instanceof \DOMElement) {
                    continue;
                }

                $attribute = $node->hasAttribute('href') ? 'href' : 'src';
                $url = trim($node->getAttribute($attribute));
                $error = $this->checkUrl($root, $file, $url);

                if ($error !== null) {
                    $errors[] = sprintf(
                        '%s: %s="%s" %s',
                        $this->relativePath($root, $file),
                        $attribute,
                        $url,
                        $error,
                    );
                }
            }
        }

        sort($errors, SORT_STRING);

        return $errors;
    }

    /**
     * @return list<string>
     */
    private function htmlFiles(string $root): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if ($file instanceof \SplFileInfo && $file->isFile() && $file->getExtension() === 'html') {
                $files[] = $file->getPathname();
            }
        }

        sort($files, SORT_STRING);

        return $files;
    }

    private function checkUrl(string $root, string $source, string $url): ?string
    {
        if ($url === '' || str_starts_with($url, '//')) {
            return null;
        }

        $parts = parse_url($url);
        if ($parts === false) {
            return 'is not a valid URL';
        }

        if (isset($parts['scheme'])) {
            return null;
        }

        $path = rawurldecode($parts['path'] ?? '');
        $fragment = isset($parts['fragment']) ? rawurldecode($parts['fragment']) : null;
        $target = $path === ''
            ? $source
            : (str_starts_with($path, '/') ? $root . $path : dirname($source) . '/' . $path);

        if (is_dir($target)) {
            $target = rtrim($target, '/') . '/index.html';
        }

        if (!is_file($target)) {
            return 'does not exist';
        }

        $target = realpath($target);
        if ($target === false || ($target !== $root && !str_starts_with($target, $root . DIRECTORY_SEPARATOR))) {
            return 'resolves outside the generated documentation';
        }

        if ($fragment !== null && $fragment !== '' && pathinfo($target, PATHINFO_EXTENSION) === 'html') {
            if (!isset($this->fragments($target)[$fragment])) {
                return sprintf('references missing fragment #%s', $fragment);
            }
        }

        return null;
    }

    /**
     * @return array<string, true>
     */
    private function fragments(string $file): array
    {
        if (isset($this->fragmentCache[$file])) {
            return $this->fragmentCache[$file];
        }

        $document = $this->loadDocument($file);
        $fragments = [];

        foreach ($document->getElementsByTagName('*') as $element) {
            if ($element->hasAttribute('id')) {
                $fragments[$element->getAttribute('id')] = true;
            }

            if ($element->tagName === 'a' && $element->hasAttribute('name')) {
                $fragments[$element->getAttribute('name')] = true;
            }
        }

        return $this->fragmentCache[$file] = $fragments;
    }

    private function loadDocument(string $file): \DOMDocument
    {
        $document = new \DOMDocument();

        if ($file === '' || @$document->loadHTMLFile($file, LIBXML_NONET) === false) {
            throw new \RuntimeException('Unable to parse generated documentation file ' . $file);
        }

        return $document;
    }

    private function relativePath(string $root, string $file): string
    {
        return str_replace(DIRECTORY_SEPARATOR, '/', substr($file, strlen($root) + 1));
    }
}
