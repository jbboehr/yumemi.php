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

namespace jbboehr\Yumemi\Tests\Registry;

use jbboehr\Yumemi\Units;
use PHPUnit\Framework\TestCase;

final class Udunits2DerivedUnitEquivalenceTest extends TestCase
{
    public function testNamedDerivedUnitsAreEquivalentToTheirDefiningExpressions(): void
    {
        $units = Units::default();

        foreach (self::namedDerivedUnits() as [$name, $definition]) {
            $this->assertTrue($units->compatible($name, $definition), $name);
            $this->assertSame('1', $units->conversionFactor($name, $definition)->toString(), $name);
            $this->assertSame('1', $units->conversionFactor($definition, $name)->toString(), $name);
        }
    }

    /**
     * @return list<array{string, string}>
     */
    private static function namedDerivedUnits(): array
    {
        return [
            ['radian', '1'],
            ['steradian', 'radian^2'],
            ['hertz', '1 / second'],
            ['newton', 'kilogram * meter / second^2'],
            ['pascal', 'newton / meter^2'],
            ['joule', 'newton * meter'],
            ['watt', 'joule / second'],
            ['coulomb', 'ampere * second'],
            ['volt', 'watt / ampere'],
            ['farad', 'coulomb / volt'],
            ['ohm', 'volt / ampere'],
            ['siemens', 'ampere / volt'],
            ['weber', 'volt * second'],
            ['tesla', 'weber / meter^2'],
            ['henry', 'weber / ampere'],
            ['lumen', 'candela * steradian'],
            ['lux', 'lumen / meter^2'],
            ['katal', 'mole / second'],
            ['becquerel', '1 / second'],
            ['gray', 'joule / kilogram'],
            ['sievert', 'joule / kilogram'],
        ];
    }
}
