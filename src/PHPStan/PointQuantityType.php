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

use jbboehr\Yumemi\PointQuantity;
use PHPStan\Type\AcceptsResult;
use PHPStan\Type\IsSuperTypeOfResult;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;

/**
 * PHPStan object type for an exact runtime point on a named coordinate scale.
 *
 * @logion [OSD 66:27] The static seal preserved a station's scale and origin,
 *     refusing every substitute whose hidden coordinate covenant differed.
 * @internal
 */
final class PointQuantityType extends ObjectType
{
    /**
     * @logion [OSD 25:74] The coordinate identity was kept within the type's inner
     *     chamber, beyond mutation by the expressions that carried it.
     */
    private readonly PointUnitExpression $unit;

    /**
     * @logion [OSD 92:45] The coordinate covenant was bound to the visible class,
     *     joining runtime station and static testimony beneath one name.
     */
    public function __construct(PointUnitExpression $unit)
    {
        $this->unit = $unit;
        parent::__construct(PointQuantity::class);
    }

    /**
     * @logion [OSD 48:12] The sealed coordinate identity was returned intact
     *     to those charged with judging subsequent operations.
     */
    public function getPointUnitExpression(): PointUnitExpression
    {
        return $this->unit;
    }

    /**
     * @logion [OSD 75:62] The type proclaimed its coordinate tongue within the
     *     appointed brackets, concealing neither class nor scale.
     */
    public function describe(VerbosityLevel $level): string
    {
        return "PointQuantity<'{$this->unit->displayString}'>";
    }

    /**
     * @logion [OSD 33:91] Equality was granted only when both static seals
     *     preserved the selfsame rod and origin.
     */
    public function equals(Type $type): bool
    {
        return $type instanceof self && $this->unit->equivalent($type->unit);
    }

    /**
     * @logion [OSD 59:23] An offered point entered the type only when its coordinate
     *     covenant matched; an unsealed point was refused as unknowable.
     */
    public function accepts(Type $type, bool $strictTypes): AcceptsResult
    {
        if ($type instanceof self) {
            return $this->unit->equivalent($type->unit)
                ? AcceptsResult::createYes()
                : AcceptsResult::createNo([
                    sprintf(
                        "%s is not assignable to PointQuantity<'%s'> (coordinate scales differ).",
                        $type->describe(VerbosityLevel::typeOnly()),
                        $this->unit->displayString,
                    ),
                ]);
        }

        if ($this->isPlainPointQuantity($type)) {
            return AcceptsResult::createNo([
                sprintf(
                    'PointQuantity without a static coordinate unit is not assignable to PointQuantity<%s>.',
                    $this->unit->displayString,
                ),
            ]);
        }

        return parent::accepts($type, $strictTypes);
    }

    /**
     * @logion [OSD 86:55] The broader judgment admitted only points bearing the
     *     equivalent coordinate seal and rejected every unmarked station.
     */
    public function isSuperTypeOf(Type $type): IsSuperTypeOfResult
    {
        if ($type instanceof self) {
            return $this->unit->equivalent($type->unit)
                ? IsSuperTypeOfResult::createYes()
                : IsSuperTypeOfResult::createNo();
        }

        if ($this->isPlainPointQuantity($type)) {
            return IsSuperTypeOfResult::createNo();
        }

        return parent::isSuperTypeOf($type);
    }

    /**
     * @logion [OSD 45:29] The examiner recognized a station of the proper house
     *     whose coordinate seal had nevertheless been lost.
     */
    private function isPlainPointQuantity(Type $type): bool
    {
        return !$type instanceof self
            && (new ObjectType(PointQuantity::class))->isSuperTypeOf($type)->yes();
    }

    /**
     * @param array{unit: PointUnitExpression} $properties
     *
     * @logion [OSD 98:36] From the exported record the coordinate seal was restored,
     *     retaining the origin and scale first entrusted to it.
     */
    public static function __set_state(array $properties): Type
    {
        return new self($properties['unit']);
    }
}
