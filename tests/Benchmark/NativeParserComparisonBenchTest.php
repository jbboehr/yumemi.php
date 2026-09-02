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

namespace jbboehr\Yumemi\Tests\Benchmark;

use jbboehr\Yumemi\Benchmarks\NativeParserComparisonBench;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class NativeParserComparisonBenchTest extends TestCase
{
    public function testParseAndResolveSubjectsUseFreshCatalogResolution(): void
    {
        eval(<<<'PHP'
            namespace jbboehr\Yumemi\Parser;

            final class NativeParser
            {
                public const ABI_VERSION = 1;

                public static function supports(int $abiVersion): bool
                {
                    return $abiVersion === 1;
                }

                /** @return array<string, int|string> */
                public static function parse(string $input): array
                {
                    return [
                        'kind' => 'identifier',
                        'start' => 0,
                        'end' => strlen($input),
                        'text' => $input,
                    ];
                }
            }

            final class NativeParseException extends \RuntimeException {}
            final class NativeLimitException extends \LengthException {}
            PHP);

        require_once dirname(__DIR__, 2) . '/benchmarks/NativeParserComparison.php';

        $benchmark = new NativeParserComparisonBench();
        $params = ['input' => 'meter'];
        $benchmark->setUp($params);

        $resolved = [
            $benchmark->benchPhpParseAndResolve($params),
            $benchmark->benchPhpParseAndResolve($params),
            $benchmark->benchNativeParseAndResolve($params),
            $benchmark->benchNativeParseAndResolve($params),
        ];

        self::assertCount(
            count($resolved),
            array_unique(array_map(spl_object_id(...), $resolved)),
            'Every measured invocation must resolve through a fresh UnitResolver catalog cache.',
        );
    }
}
