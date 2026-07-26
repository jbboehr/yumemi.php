<?php

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
