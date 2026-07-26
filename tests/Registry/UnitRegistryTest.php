<?php

/**
 * Iudex Mensurarum Mysticarum『夢見』〜ＹＵＭＥＭＩ〜
 *
 * Copyright (c) anno Domini nostri Jesu Christi MMXXVI, John Boehr & contributors
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace jbboehr\Yumemi\Tests\Registry;

use jbboehr\Yumemi\Exception\UnitNotFoundException;
use jbboehr\Yumemi\Registry\UnitRegistry;
use PHPUnit\Framework\TestCase;

final class UnitRegistryTest extends TestCase
{
    public function testDefaultsContainBaseAndDerivedUnits(): void
    {
        $registry = UnitRegistry::defaults();

        $this->assertSame('meter', $registry->get('meter')->toString());
        $this->assertSame('kilometer', $registry->get('kilometer')->toString());
        $this->assertFalse($registry->get('kilometer')->isBase());
    }

    public function testMissingUnitFails(): void
    {
        $registry = UnitRegistry::defaults();

        $this->expectException(UnitNotFoundException::class);
        $registry->get('league');
    }
}
