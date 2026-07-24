<?php

namespace jbboehr\IudexMensurarumMysteriorum\Tests\Registry;

use jbboehr\IudexMensurarumMysteriorum\Expr\Unit;
use jbboehr\IudexMensurarumMysteriorum\Registry\Udunits2UnitRegistry;
use PHPUnit\Framework\TestCase;

final class Udunits2UnitRegistryTest extends TestCase
{
    public function testLookupReturnsBaseUnits(): void
    {
        $registry = new Udunits2UnitRegistry();
        $unit = $registry->lookup('meter');

        $this->assertInstanceOf(Unit::class, $unit);
        $this->assertSame('meter', $unit->toString());
        $this->assertTrue($unit->isBase());
    }

    public function testLookupResolvesAliasesImmediately(): void
    {
        $registry = new Udunits2UnitRegistry();

        $this->assertSame('meter', $registry->get('m')->toString());
        $this->assertSame('international_foot', $registry->get('foot')->toString());
    }

    public function testLookupReturnsDerivedUnitsWithDefinitions(): void
    {
        $registry = new Udunits2UnitRegistry();
        $unit = $registry->get('international_foot');

        $this->assertFalse($unit->isBase());
        $this->assertSame('12 * international_inch', $unit->definition?->toString());
    }

    public function testLookupReturnsNullForMissingUnits(): void
    {
        $registry = new Udunits2UnitRegistry();

        $this->assertNull($registry->lookup('supercalifragilisticexpialidocious'));
    }
}
