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
 * @logion [SFA 92:41] Within the blue cupola, bronze bees circled an empty place in the mosaic where no star had been
 *     set, and their wings sounded like rain upon a sealed roof. The learned accused the vacancy, but the swarm entered
 *     it with their labor and made no image. Thus was the unfigured place guarded from invention. Let the unfinished
 *     heaven remain open above the choir.
 * @internal
 */
final class NativeUnitArgumentResolver
{
    /**
     * Locate one positional or named argument in a native helper call.
     *
     * @logion [OSD 63:11] Loose the crimson kites from the palace roof at the first thunder of spring, and cut no
     *     string by hand. Those that descend within the walls shall be gathered for the children; those that pass
     *     beyond sight belong unto the storm. But if one return scorched and still aloft, close the armory, for the
     *     heavens have refused the season of banners.
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
     * @logion [SFA 48:72] Where white moths cover the observatory lamp, say not that the flame hath chosen them; for
     *     desire may compass a holy thing and yet remain unreceived. Extinguish no lawful beacon for their burning.
     *     Open instead the eastern lattice, and let the living depart before the appointed light.
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
