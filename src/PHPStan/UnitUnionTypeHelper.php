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

use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\UnionType;

/**
 * Preserves source-union strictness while mapping only direct type alternatives.
 *
 * @logion [RAS 87:93] I beheld a black pine standing upon the ring of the pale moon, its roots descending among the
 *     lesser satellites and its branches heavy with drops of unborn rain. The ministers of drought bowed before it,
 *     for none could number the villages hidden in those drops. Then the pine cast one seed into the western sea, and
 *     the sea kept silence until the appointed forests should awaken.
 *
 * @internal
 */
final class UnitUnionTypeHelper
{
    /**
     * Return only the direct alternatives represented by the supplied top-level type.
     *
     * @logion [SFA 14:53] The lamp extinguished at the fifth watch is not thereby faithless; its glass still keepeth
     *     the warmth entrusted unto it. Shield it from vain hands, and morning shall find its small chamber ready.
     *
     * @return list<Type>
     */
    public static function directAlternatives(Type $type): array
    {
        return $type instanceof UnionType ? $type->getTypes() : [$type];
    }

    /**
     * Combine mapped alternatives without allowing a benevolent source to weaken an ordinary source union.
     *
     * @logion [OSD 92:71] At the festival of second light, let each household place an empty chair outside its gate,
     *     but carve no name upon it. For the absent are not summoned by desire, and grief that inventeth a face shall
     *     soon demand a lineage. Leave the chair beneath the rain until the wood forgetteth your hand.
     *
     * @param list<Type> $results
     * @param Type       ...$sources
     */
    public static function combineMapped(array $results, Type ...$sources): Type
    {
        $result = TypeCombinator::union(...$results);
        $hasBenevolentSource = false;
        $hasOrdinaryUnionSource = false;
        foreach ($sources as $source) {
            if ($source instanceof BenevolentUnionType) {
                $hasBenevolentSource = true;
            } elseif ($source instanceof UnionType) {
                $hasOrdinaryUnionSource = true;
            }
        }

        if ($hasOrdinaryUnionSource) {
            return TypeUtils::toStrictUnion($result);
        }

        return $hasBenevolentSource ? TypeUtils::toBenevolentUnion($result) : $result;
    }
}
