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

/**
 * Runs a `TypeInferenceTestCase` fixture from the test body so its analysis registers code coverage.
 *
 * `gatherAssertTypes()` runs the analyser — and with it the Yumemi PHPStan extensions — in-process, so it
 * is what actually exercises the extension classes. The idiomatic `#[DataProvider]` that calls it runs
 * during data collection, which PHPUnit excludes from code coverage; worse, that collection-phase run
 * warms PHPStan's process-global parser and PHPDoc caches, so a later in-body call reuses them and the
 * parse-/PHPDoc-time extensions (tag promotion, type-node resolution) never re-execute. Calling
 * `gatherAssertTypes()` from the test body as the *first* analysis of the fixture keeps the caches cold
 * and attributes the whole run — dynamic return types, type-node resolution, tag promotion — to a test,
 * so all of it is recorded. Every assertType in the fixture is still validated via `assertFileAsserts()`.
 *
 * Mixed into `TypeInferenceTestCase` subclasses, where `gatherAssertTypes()` and `assertFileAsserts()`
 * are available. The subclass must not also expose the fixture through a `#[DataProvider]`, or the
 * provider's collection-phase run would warm the caches first.
 */
trait AssertsFixtureUnderCoverage
{
    private function assertFixtureUnderCoverage(string $file): void
    {
        $asserts = self::gatherAssertTypes($file);
        $this->assertNotEmpty($asserts);

        foreach ($asserts as $assert) {
            $this->assertFileAsserts(...$assert);
        }
    }
}
