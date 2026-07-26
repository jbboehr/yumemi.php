<?php

namespace jbboehr\Yumemi\Tests\Registry;

use jbboehr\Yumemi\Analyzer\UnitResolver;
use jbboehr\Yumemi\Expr\Compound;
use jbboehr\Yumemi\Expr\Constant;
use jbboehr\Yumemi\Expr\Unit;
use jbboehr\Yumemi\Registry\CompositeUnitRegistry;
use jbboehr\Yumemi\Registry\Udunits2UnitRegistry;
use jbboehr\Yumemi\Registry\UnitRegistry;
use jbboehr\Yumemi\Registry\UnitRegistryBuilder;
use jbboehr\Yumemi\Units;
use PHPUnit\Framework\TestCase;

final class UnitRegistryBuilderTest extends TestCase
{
    public function testEmptyBuilderStartsWithNothing(): void
    {
        $registry = UnitRegistryBuilder::empty()->build();

        $this->assertSame([], $registry->names());
        $this->assertNull($registry->lookup('meter'));
        $this->assertNull($registry->record('meter'));
    }

    public function testDefaultBuilderIncludesUdunits2(): void
    {
        $registry = UnitRegistryBuilder::default()->build();

        $this->assertInstanceOf(Udunits2UnitRegistry::class, $registry);
        $this->assertNotNull($registry->record('meter'));
    }

    public function testBuilderProducesImmutablePrebuiltRegistry(): void
    {
        $meter = new Unit('meter');
        $registry = UnitRegistryBuilder::empty()
            ->add($meter)
            ->alias('metres', 'meter')
            ->build();

        $this->assertSame($meter, $registry->lookup('meter'));
        $this->assertSame($meter, $registry->lookup('metres'));
        $this->assertNull($registry->lookup('foot'));
        $this->assertFalse(method_exists($registry, 'register'));
    }

    public function testUnitRegistryDefaultsIsBuiltinFixture(): void
    {
        $registry = UnitRegistry::defaults();

        $this->assertSame('meter', $registry->get('meter')->toString());
        $this->assertSame('kilometer', $registry->get('kilometer')->toString());
        $this->assertFalse($registry->get('kilometer')->isBase());
    }

    public function testDefineStringDefinitionOverUdunits2(): void
    {
        $registry = UnitRegistryBuilder::default()
            ->define('widget = 12 * meter')
            ->alias('widgets', 'widget')
            ->build();

        $this->assertInstanceOf(CompositeUnitRegistry::class, $registry);
        $this->assertSame([
            'type' => 'unit',
            'name' => 'widget',
            'def' => '12 * meter',
        ], $registry->record('widget'));

        $units = new Units($registry);

        $this->assertSame('12', $units->quantity(1, 'widget')->valueIn('meter')->toString());
        $this->assertSame('24', $units->quantity(2, 'widgets')->valueIn('meter')->toString());
        $this->assertSame('newton', $units->unit('newton')->toString());
    }

    public function testDefineCanReferenceEarlierCustomDefines(): void
    {
        $registry = UnitRegistryBuilder::default()
            ->define('chain = 20.1168 * meter')
            ->define('furlong = 10 * chain')
            ->build();

        $units = new Units($registry);

        $this->assertSame('25146/125', $units->quantity(1, 'furlong')->valueIn('meter')->toString());
    }

    public function testDefineWorksOnEmptyBuilderWithExplicitUnits(): void
    {
        $registry = UnitRegistryBuilder::empty()
            ->add(new Unit('meter'))
            ->define('widget = 12 * meter')
            ->build();

        $units = new Units($registry);

        $this->assertSame('12', $units->quantity(1, 'widget')->valueIn('meter')->toString());
    }

    public function testBuilderLayersPrebuiltUnitsOverUdunits2(): void
    {
        $meter = new Unit('meter');
        $widget = new Unit('widget', new Compound([
            new Constant(12),
            $meter,
        ]));

        $registry = UnitRegistryBuilder::default()
            ->add($widget)
            ->alias('widgets', 'widget')
            ->build();

        $this->assertInstanceOf(CompositeUnitRegistry::class, $registry);

        $resolver = new UnitResolver($registry);
        $resolved = $resolver->resolveOrFail('widget');
        $this->assertInstanceOf(Unit::class, $resolved);
        $this->assertSame('widget', $resolved->toString());
        $this->assertSame('12 * meter', $resolved->definition?->toString());

        $this->assertSame('widget', $resolver->resolveOrFail('widgets')->toString());
        $this->assertNotNull($resolver->resolve('newton'));
    }

    public function testCustomUnitOverridesCatalogName(): void
    {
        $customFoot = new Unit('foot', new Constant(99));

        $registry = UnitRegistryBuilder::default()
            ->add($customFoot)
            ->build();

        $resolver = new UnitResolver($registry);
        $foot = $resolver->resolveOrFail('foot');

        $this->assertInstanceOf(Unit::class, $foot);
        $this->assertSame($customFoot, $foot);
        $this->assertFalse($foot->isBase());
        $this->assertSame('99', $foot->definition?->toString());
    }

    public function testDefineOverridesCatalogName(): void
    {
        $registry = UnitRegistryBuilder::default()
            ->define('foot = 99 * meter')
            ->build();

        $units = new Units($registry);

        $this->assertSame('99', $units->quantity(1, 'foot')->valueIn('meter')->toString());
    }

    public function testUnitsCanUseBuiltRegistry(): void
    {
        $registry = UnitRegistry::defaults();
        $units = new Units($registry);

        $this->assertSame('3 * meter', $units->quantity(1, 'meter')->add($units->quantity(2, 'meter'))->toString());
    }

    public function testBuilderRejectsInvalidDefineSyntax(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('name = expression');

        UnitRegistryBuilder::empty()->define('widget 12 * meter');
    }

    public function testBuilderRejectsDuplicateDefine(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Duplicate');

        UnitRegistryBuilder::default()
            ->define('widget = 12 * meter')
            ->define('widget = 13 * meter');
    }

    public function testBuilderIsImmutableFluent(): void
    {
        $base = UnitRegistryBuilder::empty();
        $withMeter = $base->add(new Unit('meter'));

        $this->assertNull($base->build()->lookup('meter'));
        $this->assertNotNull($withMeter->build()->lookup('meter'));
    }

    public function testUnitRegistryBuilderDelegatesToEmpty(): void
    {
        $this->assertSame([], UnitRegistry::builder()->build()->names());
    }
}
