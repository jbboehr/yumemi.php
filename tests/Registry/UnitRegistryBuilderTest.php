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
    /** @var list<string> */
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            @unlink($file);
        }

        $this->tempFiles = [];
    }

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

    public function testBuilderUsesExplicitUdunits2CatalogPath(): void
    {
        $catalogFile = $this->catalogFile();

        $defaultRegistry = UnitRegistryBuilder::default($catalogFile)->build();
        $emptyBuilder = UnitRegistryBuilder::empty();
        $optInRegistry = $emptyBuilder->withUdunits2($catalogFile)->build();

        foreach ([$defaultRegistry, $optInRegistry] as $registry) {
            $this->assertSame(['type' => 'base', 'name' => 'widget'], $registry->record('widget'));
            $this->assertNull($registry->record('meter'));
        }

        $this->assertSame([], $emptyBuilder->build()->names());
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

    public function testAddAllPreservesTheOriginalBuilder(): void
    {
        $base = UnitRegistryBuilder::empty();
        $extended = $base->addAll([new Unit('meter'), new Unit('second')]);

        $this->assertSame([], $base->build()->names());
        $this->assertSame(['meter', 'second'], $extended->build()->names());
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

    public function testBuilderRejectsDuplicateAddedUnit(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Duplicate unit name');

        UnitRegistryBuilder::empty()
            ->add(new Unit('widget'))
            ->add(new Unit('widget'));
    }

    public function testBuilderRejectsAliasCollidingWithDefinition(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Duplicate unit or alias name');

        UnitRegistryBuilder::empty()
            ->define('widget = 1')
            ->alias('widget', 'other');
    }

    public function testBuilderRejectsEmptyAliasName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Alias name must not be empty');

        UnitRegistryBuilder::empty()->alias('', 'widget');
    }

    public function testBuilderRejectsEmptyAliasTarget(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Alias target must not be empty');

        UnitRegistryBuilder::empty()->alias('thing', '');
    }

    public function testBuilderIsImmutableFluent(): void
    {
        $base = UnitRegistryBuilder::empty();
        $withMeter = $base->add(new Unit('meter'));
        $withDefinition = $base->define('widget = 1');
        $withAlias = $withDefinition->alias('thing', 'widget');

        $this->assertNull($base->build()->lookup('meter'));
        $this->assertNull($base->build()->record('widget'));
        $this->assertNotNull($withMeter->build()->lookup('meter'));
        $this->assertNotNull($withDefinition->build()->record('widget'));
        $this->assertNull($withDefinition->build()->record('thing'));
        $this->assertNotNull($withAlias->build()->record('thing'));
    }

    public function testOverlayAliasCollectionContinuesPastOtherRecords(): void
    {
        $widget = new Unit('widget');
        $registry = UnitRegistryBuilder::empty()
            ->define('definition = 1')
            ->add($widget)
            ->alias('unresolved', 'missing')
            ->alias('thing', 'widget')
            ->build();

        $this->assertNull($registry->lookup('unresolved'));
        $this->assertSame($widget, $registry->lookup('thing'));
    }

    public function testDefinitionAssignmentTrimsOuterAndExpressionWhitespace(): void
    {
        $registry = UnitRegistryBuilder::empty()
            ->define(" \n widget \t = \n 12 *\n meter \n ")
            ->build();

        $this->assertSame([
            'type' => 'unit',
            'name' => 'widget',
            'def' => "12 *\n meter",
        ], $registry->record('widget'));
    }

    public function testDefinitionAssignmentRejectsLeadingGarbage(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('name = expression');

        UnitRegistryBuilder::empty()->define('garbage widget = 1');
    }

    public function testUnitRegistryBuilderDelegatesToEmpty(): void
    {
        $this->assertSame([], UnitRegistry::builder()->build()->names());
    }

    private function catalogFile(): string
    {
        $file = tempnam(sys_get_temp_dir(), 'yumemi-builder-catalog-');
        $this->assertNotFalse($file);
        $this->tempFiles[] = $file;

        $catalog = [
            'units' => [
                'widget' => ['type' => 'base', 'name' => 'widget'],
            ],
            'base' => ['widget'],
            'prefixes' => [],
        ];
        file_put_contents($file, "<?php\n\nreturn " . var_export($catalog, true) . ";\n");

        return $file;
    }
}
