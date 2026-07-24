<?php

namespace jbboehr\IudexMensurarumMysteriorum\Tests\Registry;

use jbboehr\IudexMensurarumMysteriorum\Exception\UnsupportedUnitDimensionException;
use jbboehr\IudexMensurarumMysteriorum\Expr\Unit;
use jbboehr\IudexMensurarumMysteriorum\Registry\Udunits2UnitRegistry;
use jbboehr\IudexMensurarumMysteriorum\Units;
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

    public function testLookupReturnedUnitsExposeDimensionsFromDefinitionTree(): void
    {
        $registry = new Udunits2UnitRegistry();

        // Catalog-loaded units carry definition trees, so dimension works without Units binding.
        $this->assertSame('length', $registry->get('foot')->dimension()->toString());
        $this->assertSame('length * mass / time ^ 2', $registry->get('newton')->dimension()->toString());
    }

    public function testUnitsFacadeUnitsExposeDimensions(): void
    {
        $units = Units::default();
        $foot = $units->unit('foot');
        $newton = $units->unit('newton');

        $this->assertInstanceOf(Unit::class, $foot);
        $this->assertInstanceOf(Unit::class, $newton);
        $this->assertSame('length', $foot->dimension()->toString());
        $this->assertSame('length * mass / time ^ 2', $newton->dimension()->toString());
    }

    public function testLookupReturnsNullForMissingUnits(): void
    {
        $registry = new Udunits2UnitRegistry();

        $this->assertNull($registry->lookup('supercalifragilisticexpialidocious'));
    }

    public function testBareUnitDimensionRequiresUnitsContextOrDefinition(): void
    {
        $this->expectException(UnsupportedUnitDimensionException::class);
        $this->expectExceptionMessage('Units::unit()');

        (new Unit('foot'))->dimension();
    }

    public function testBareUnitDimensionFallsBackToBoundUnitsContext(): void
    {
        $units = Units::default();

        $this->assertSame('length', (new Unit('foot'))->withUnits($units)->dimension()->toString());
    }
}
