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

use PhpParser\Node;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * In-process replacement for the old `shell_exec`-based PHPStan integration tests.
 *
 * `RuleTestCase` analyses through a single rule; this base composes the whole Yumemi extension rule set
 * (everything tagged `phpstan.rules.rule` in the configured container) with any named core PHPStan rules
 * a subclass needs, so `analyse()` runs a faithful multi-rule analysis in the current process instead of
 * booting a fresh `phpstan` subprocess per test. Subclasses declare their config via
 * {@see getAdditionalConfigFiles()} and the extra core rules via {@see coreRuleClasses()}.
 *
 * @extends RuleTestCase<Rule<Node>>
 */
abstract class InProcessAnalysisTestCase extends RuleTestCase
{
    protected function getRule(): Rule
    {
        // The configured container registers the full level-max rule set (Yumemi's rules plus every core
        // PHPStan rule); composing them all reproduces a real CLI analysis in-process.
        /** @var list<Rule<Node>> $rules */
        $rules = array_values(self::getContainer()->getServicesByTag('phpstan.rules.rule'));

        return new CompositeRule($rules);
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../../extension.neon',
        ];
    }
}
