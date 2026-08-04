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

namespace jbboehr\Yumemi\PHPStan;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PHPStan\Parser\Parser;

/**
 * Applies Yumemi tag promotion after PHPStan chooses its rich, simple, or stub parser.
 * @internal
 */
final class YumemiTagPromotingParser implements Parser
{
    private readonly NodeTraverser $traverser;

    public function __construct(
        private readonly Parser $wrappedParser,
        YumemiDocTagPromoter $promoter,
    ) {
        $this->traverser = new NodeTraverser($promoter);
    }

    /** @return array<Node\Stmt> */
    public function parseFile(string $file): array
    {
        return $this->promote($this->wrappedParser->parseFile($file));
    }

    /** @return array<Node\Stmt> */
    public function parseString(string $sourceCode): array
    {
        return $this->promote($this->wrappedParser->parseString($sourceCode));
    }

    /**
     * @param array<Node\Stmt> $nodes
     *
     * @return array<Node\Stmt>
     */
    private function promote(array $nodes): array
    {
        try {
            /** @var array<Node\Stmt> */
            return $this->traverser->traverse($nodes);
        } catch (\Throwable $exception) {
            ShouldNotHappenException::rethrow($exception);
        }
    }
}
