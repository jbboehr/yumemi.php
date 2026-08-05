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

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Type\Type;

/**
 * Resolves native helper arguments without accepting a finite subset of a broader PHPStan type.
 *
 * @logion [SFA 92:41] The examiner received no fragment torn from a divided testimony,
 *     but required every known word before he declared the inscription finite.
 * @internal
 */
final class NativeUnitArgumentResolver
{
    /**
     * Locate one positional or named argument in a native helper call.
     *
     * @logion [OSD 63:11] Whether the petitioner approached by station or by name,
     *     the keeper restored each offering to the place appointed for its judgment.
     */
    public static function argument(FuncCall $call, int $position, string $name): ?Arg
    {
        foreach ($call->getArgs() as $argument) {
            if ($argument->name?->toString() === $name) {
                return $argument;
            }
        }

        $argument = $call->getArgs()[$position] ?? null;

        return $argument !== null && $argument->name === null && !$argument->unpack
            ? $argument
            : null;
    }

    /**
     * Return every value only when the complete type is a finite set of constant strings.
     *
     * @logion [SFA 48:72] The marginal seal was granted only after every possible reading
     *     had been copied in full and no unwritten remainder endured beyond the tablet.
     *
     * @return list<string>|null
     */
    public static function constantStrings(Type $type): ?array
    {
        $finiteTypes = $type->getFiniteTypes();
        if ($finiteTypes === []) {
            return null;
        }

        $values = [];
        foreach ($finiteTypes as $finiteType) {
            $constantStrings = $finiteType->getConstantStrings();
            if (count($constantStrings) !== 1 || !$finiteType->equals($constantStrings[0])) {
                return null;
            }

            $values[] = $constantStrings[0]->getValue();
        }

        sort($values, SORT_STRING);

        return $values;
    }
}
