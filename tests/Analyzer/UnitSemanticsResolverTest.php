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

namespace jbboehr\Yumemi\Tests\Analyzer;

use jbboehr\Yumemi\Analyzer\UnitSemanticsResolver;
use jbboehr\Yumemi\Catalog\UnitSemantics;
use jbboehr\Yumemi\Registry\UnitRegistry;
use PHPUnit\Framework\TestCase;

final class UnitSemanticsResolverTest extends TestCase
{
    public function testResolvedSemanticsAreCachedByCompleteName(): void
    {
        $registry = new class () extends UnitRegistry {
            public int $recordLookups = 0;

            public function findCatalogRecord(string $name): ?array
            {
                ++$this->recordLookups;

                return parent::findCatalogRecord($name);
            }
        };
        $resolver = new UnitSemanticsResolver($registry);

        $this->assertSame(UnitSemantics::UnsupportedExpression, $resolver->resolve('missing'));
        $lookups = $registry->recordLookups;
        $this->assertGreaterThan(0, $lookups);
        $this->assertSame(UnitSemantics::UnsupportedExpression, $resolver->resolve('missing'));
        $this->assertSame($lookups, $registry->recordLookups);
    }

    public function testUnexpectedProgrammingErrorsPropagate(): void
    {
        $registry = new class () extends UnitRegistry {
            public function findCatalogRecord(string $name): ?array
            {
                throw new \LogicException('unexpected registry failure');
            }
        };

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('unexpected registry failure');

        (new UnitSemanticsResolver($registry))->resolve('meter');
    }
}
