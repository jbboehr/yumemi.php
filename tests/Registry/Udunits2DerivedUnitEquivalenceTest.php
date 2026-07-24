<?php

namespace jbboehr\IudexMensurarumMysteriorum\Tests\Registry;

use jbboehr\IudexMensurarumMysteriorum\Units;
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
