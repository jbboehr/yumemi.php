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
use jbboehr\Yumemi\Catalog\UnitDescriptor;
use jbboehr\Yumemi\Catalog\UnitSemantics;
use jbboehr\Yumemi\Dimension;
use jbboehr\Yumemi\Exception\UnitNotFoundException;
use jbboehr\Yumemi\Exception\UnresolvableUnitDimensionException;
use jbboehr\Yumemi\Exception\UnsupportedSyntaxException;
use jbboehr\Yumemi\Exception\UnsupportedUnitAlgebraException;
use jbboehr\Yumemi\Exception\UnsupportedUnitConversionException;
use jbboehr\Yumemi\Expr\Product;
use jbboehr\Yumemi\Expr\Constant;
use jbboehr\Yumemi\Expr\Unit;
use jbboehr\Yumemi\Parser\ExpressionLimitExceededException;
use jbboehr\Yumemi\Parser\ParseException;
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
        $this->assertNull($registry->findPrebuiltUnit('meter'));
        $this->assertNull($registry->findCatalogRecord('meter'));
    }

    public function testDefaultBuilderIncludesBundledCatalog(): void
    {
        $registry = UnitRegistryBuilder::default()->build();

        $this->assertInstanceOf(CompositeUnitRegistry::class, $registry);
        $this->assertNotNull($registry->findCatalogRecord('meter'));
        $this->assertNotNull($registry->findCatalogRecord('pixel'));
    }

    public function testBuilderDeclaresPrimitiveDimensionAndDerivedUnits(): void
    {
        $registry = UnitRegistryBuilder::default()
            ->baseUnit('USD', Dimension::CURRENCY)
            ->define('EUR = 100 / 107 * USD')
            ->alias('dollars', 'USD')
            ->build();
        $units = new Units($registry);

        $this->assertSame(Dimension::CURRENCY, $registry->findPrimitiveDimension('USD'));
        $this->assertNull($registry->findPrimitiveDimension('EUR'));
        $this->assertSame([
            'type' => 'base',
            'name' => 'USD',
            'dimension' => Dimension::CURRENCY,
        ], $registry->findCatalogRecord('USD'));
        $this->assertSame('currency', $units->dimension('USD')->toString());
        $this->assertSame('currency', $units->unit('USD')->dimension()->toString());
        $this->assertSame('currency / time', $units->dimension('EUR / second')->toString());
        $this->assertSame('100', $units->convert(107, 'EUR', 'USD')->toString());
        $this->assertSame('107', $units->convert(100, 'dollars', 'EUR')->toString());

        $sum = $units->quantity(100, 'USD')->add($units->quantity(107, 'EUR'));
        $this->assertSame('200', $sum->valueToString());
        $this->assertSame('USD', $sum->unitToString());
    }

    public function testBuilderRejectsInvalidPrimitiveDimensionDeclarations(): void
    {
        try {
            UnitRegistryBuilder::empty()->baseUnit('USD / second', Dimension::CURRENCY);
            self::fail('Expected invalid base-unit name failure.');
        } catch (\InvalidArgumentException $exception) {
            $this->assertStringContainsString('one unit identifier', $exception->getMessage());
        }

        try {
            UnitRegistryBuilder::empty()->baseUnit('USD', 'Not Valid');
            self::fail('Expected invalid primitive-dimension name failure.');
        } catch (\InvalidArgumentException $exception) {
            $this->assertStringContainsString('lower_snake_case', $exception->getMessage());
        }

        $builder = UnitRegistryBuilder::empty()->baseUnit('USD', Dimension::CURRENCY);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Duplicate unit');
        $builder->define('USD = 1');
    }

    public function testBuilderReportsMalformedBaseUnitExpressionsAsInvalidNames(): void
    {
        try {
            UnitRegistryBuilder::empty()->baseUnit('USD /', Dimension::CURRENCY);
            self::fail('Expected malformed base-unit name failure.');
        } catch (\InvalidArgumentException $exception) {
            $this->assertStringContainsString('one unit identifier', $exception->getMessage());
            $this->assertInstanceOf(ParseException::class, $exception->getPrevious());
        }
    }

    public function testBuilderRejectsMultipleBaseUnitsForOnePrimitiveDimension(): void
    {
        $builder = UnitRegistryBuilder::empty()->baseUnit('USD', Dimension::CURRENCY);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('already has base unit "USD"');
        $builder->baseUnit('EUR', Dimension::CURRENCY);
    }

    public function testBuilderRejectsFixedSiAxesAsPrimitiveDimensions(): void
    {
        foreach (
            [
                'length',
                'mass',
                'time',
                'electric_current',
                'temperature',
                'amount_of_substance',
                'luminous_intensity',
            ] as $dimension
        ) {
            try {
                UnitRegistryBuilder::default()->baseUnit('custom_' . $dimension, $dimension);
                self::fail('Expected fixed SI dimension rejection for ' . $dimension . '.');
            } catch (\InvalidArgumentException $exception) {
                $this->assertSame(
                    sprintf(
                        'Primitive dimension "%s" is one of the seven fixed SI dimensions; define the unit relative to its SI base instead.',
                        $dimension,
                    ),
                    $exception->getMessage(),
                );
            }
        }
    }

    public function testBuilderCannotCreateAnIdentityConversionForASecondLengthBase(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Primitive dimension "length" is one of the seven fixed SI dimensions');

        UnitRegistryBuilder::default()->baseUnit('rod', 'length');
    }

    public function testBuilderUsesExplicitUdunits2CatalogPath(): void
    {
        $catalogFile = $this->catalogFile();

        $defaultRegistry = UnitRegistryBuilder::default($catalogFile)->build();
        $emptyBuilder = UnitRegistryBuilder::empty();
        $this->assertSame($emptyBuilder, $emptyBuilder->includeUdunits2($catalogFile));
        $optInRegistry = $emptyBuilder->build();

        foreach ([$defaultRegistry, $optInRegistry] as $registry) {
            $this->assertSame(['type' => 'base', 'name' => 'widget'], $registry->findCatalogRecord('widget'));
            $this->assertNull($registry->findCatalogRecord('meter'));
        }

        $this->assertNotNull($defaultRegistry->findCatalogRecord('pixel'));
        $this->assertNull($optInRegistry->findCatalogRecord('pixel'));

        $this->assertSame(['widget'], $emptyBuilder->build()->names());
    }

    public function testBuilderProducesImmutablePrebuiltRegistry(): void
    {
        $meter = new Unit('meter');
        $registry = UnitRegistryBuilder::empty()
            ->add($meter)
            ->alias('metres', 'meter')
            ->build();

        $this->assertSame($meter, $registry->findPrebuiltUnit('meter'));
        $this->assertSame($meter, $registry->findPrebuiltUnit('metres'));
        $this->assertNull($registry->findPrebuiltUnit('foot'));
        $this->assertFalse(method_exists($registry, 'register'));
    }

    public function testAddAllMutatesAndReturnsTheBuilder(): void
    {
        $builder = UnitRegistryBuilder::empty();
        $returned = $builder->addAll([new Unit('meter'), new Unit('second')]);

        $this->assertSame($builder, $returned);
        $this->assertSame(['meter', 'second'], $builder->build()->names());
    }

    public function testUnitRegistryDefaultsIsBuiltinFixture(): void
    {
        $registry = UnitRegistry::defaults();

        $this->assertSame('meter', $registry->findPrebuiltUnit('meter')?->toString());
        $this->assertSame('kilometer', $registry->findPrebuiltUnit('kilometer')?->toString());
        $this->assertFalse($registry->findPrebuiltUnit('kilometer')->isBase());
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
        ], $registry->findCatalogRecord('widget'));

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

    public function testDefineClassifiesAffineAndLogarithmicDefinitions(): void
    {
        $registry = UnitRegistryBuilder::default()
            ->define('degree_widget = kelvin @ 100')
            ->define('bel_widget = lg(re 1)')
            ->build();

        $this->assertSame('affine', $registry->findCatalogRecord('degree_widget')['semantics'] ?? null);
        $this->assertSame('logarithmic', $registry->findCatalogRecord('bel_widget')['semantics'] ?? null);
        $this->assertSame(UnitSemantics::Affine, $registry->describe('degree_widget')?->semantics);
        $this->assertSame(UnitSemantics::Logarithmic, $registry->describe('bel_widget')?->semantics);
    }

    public function testDefineInheritsSemanticsThroughExactNameChains(): void
    {
        $registry = UnitRegistryBuilder::default()
            ->define('degree_widget = kelvin @ 100')
            ->define('widget_temperature = degree_widget')
            ->define('absolute_widget_temperature = widget_temperature')
            ->alias('degW', 'degree_widget')
            ->define('aliased_widget_temperature = degW')
            ->define('bel_widget = lg(re 1)')
            ->define('widget_level = bel_widget')
            ->define('celsius_synonym = celsius')
            ->define('bel_synonym = B')
            ->build();

        foreach (
            [
                'widget_temperature',
                'absolute_widget_temperature',
                'aliased_widget_temperature',
                'celsius_synonym',
            ] as $name
        ) {
            $this->assertSame('affine', $registry->findCatalogRecord($name)['semantics'] ?? null, $name);
            $this->assertSame(UnitSemantics::Affine, $registry->describe($name)?->semantics, $name);
        }

        foreach (['widget_level', 'bel_synonym'] as $name) {
            $this->assertSame('logarithmic', $registry->findCatalogRecord($name)['semantics'] ?? null, $name);
            $this->assertSame(UnitSemantics::Logarithmic, $registry->describe($name)?->semantics, $name);
        }

        $prefixed = $registry->describe('kilowidget_temperature');
        $this->assertNotNull($prefixed);
        $this->assertSame(UnitSemantics::UnsupportedExpression, $prefixed->semantics);
        $this->assertFalse($prefixed->supportsMultiplicativeAlgebra());
        $this->assertFalse($prefixed->supportsConversion());
        $this->assertSame(UnitSemantics::Affine, $prefixed->prefixDecomposition?->unit->semantics);
    }

    public function testDescriptionsReflectCompleteExpressionRuntimeCapabilities(): void
    {
        $registry = UnitRegistryBuilder::default()
            ->define('widget_speed = meter / second')
            ->alias('widget_velocity', 'widget_speed')
            ->define('degree_widget = kelvin @ 100')
            ->define('bel_widget = lg(re 1)')
            ->define('scaled_celsius = 2 * celsius')
            ->define('scaled_bel = 2 * B')
            ->define('unsupported_sum = meter + second')
            ->define('missing_dependency = definitely_missing_unit')
            ->define('malformed_definition = meter * / second')
            ->define('cycle_left = cycle_right')
            ->define('cycle_right = cycle_left')
            ->build();
        $units = new Units($registry);

        $cases = [
            'widget_speed' => UnitSemantics::Multiplicative,
            'widget_velocity' => UnitSemantics::Multiplicative,
            'degree_widget' => UnitSemantics::Affine,
            'bel_widget' => UnitSemantics::Logarithmic,
            'scaled_celsius' => UnitSemantics::UnsupportedExpression,
            'scaled_bel' => UnitSemantics::UnsupportedExpression,
            'unsupported_sum' => UnitSemantics::UnsupportedExpression,
            'missing_dependency' => UnitSemantics::UnsupportedExpression,
            'malformed_definition' => UnitSemantics::UnsupportedExpression,
            'cycle_left' => UnitSemantics::UnsupportedExpression,
        ];

        foreach ($cases as $name => $semantics) {
            $descriptor = $registry->describe($name);

            $this->assertNotNull($descriptor, $name);
            $this->assertSame($semantics, $descriptor->semantics, $name);
            $this->assertSame(
                $this->supportsAlgebra($units, $name),
                $descriptor->supportsMultiplicativeAlgebra(),
                $name,
            );
            $this->assertSame($this->supportsConversion($units, $name), $descriptor->supportsConversion(), $name);
            $this->assertSame($semantics, $registry->describe($name)?->semantics, $name);
        }

        $this->assertArrayNotHasKey('semantics', $registry->findCatalogRecord('scaled_celsius') ?? []);
        $this->assertArrayNotHasKey('semantics', $registry->findCatalogRecord('scaled_bel') ?? []);
    }

    public function testPrebuiltOverlayPreventsInheritedBaseCatalogReason(): void
    {
        $registry = UnitRegistryBuilder::default()
            ->add(new Unit('celsius'))
            ->define('custom_temperature = celsius')
            ->build();

        $this->assertArrayNotHasKey('semantics', $registry->findCatalogRecord('custom_temperature') ?? []);
        $descriptor = $registry->describe('custom_temperature');
        $this->assertNotNull($descriptor);
        $this->assertTrue($descriptor->supportsMultiplicativeAlgebra());
        $this->assertFalse($descriptor->supportsConversion());

        $units = new Units($registry);
        $customTemperature = $units->unit('custom_temperature');
        $this->assertInstanceOf(Unit::class, $customTemperature);
        $this->assertSame('celsius', $customTemperature->definition?->toString());
        $this->assertFalse($this->supportsConversion($units, 'custom_temperature'));

        $restoredDescriptor = unserialize(serialize($descriptor));
        $this->assertInstanceOf(UnitDescriptor::class, $restoredDescriptor);
        $this->assertFalse($restoredDescriptor->supportsConversion());

        $this->expectException(UnresolvableUnitDimensionException::class);
        $units->conversionFactor('custom_temperature', 'custom_temperature');
    }

    public function testAffineDescriptionRequiresResolvableConversionDimensions(): void
    {
        $registry = UnitRegistryBuilder::empty()
            ->add(new Unit('widget'))
            ->define('degree_widget = widget @ 100')
            ->build();

        $descriptor = $registry->describe('degree_widget');

        $this->assertNotNull($descriptor);
        $this->assertSame('affine', $registry->findCatalogRecord('degree_widget')['semantics'] ?? null);
        $this->assertSame(UnitSemantics::UnsupportedExpression, $descriptor->semantics);
        $this->assertFalse($descriptor->supportsMultiplicativeAlgebra());
        $this->assertFalse($descriptor->supportsConversion());
    }

    public function testBuildSynthesizesDifferenceUnitsForCustomAffineDefinitionsAndAliases(): void
    {
        $registry = UnitRegistryBuilder::default()
            ->define('degree_widget = kelvin @ 100')
            ->define('shifted_widget_temperature = degree_widget @ 5')
            ->define('widget_temperature = degree_widget')
            ->alias('widget_temp', 'widget_temperature')
            ->build();
        $units = new Units($registry);

        $this->assertSame('kelvin', $registry->findCatalogRecord('delta_degree_widget')['def'] ?? null);
        $this->assertSame(
            'delta_degree_widget',
            $registry->findCatalogRecord('delta_widget_temperature')['def'] ?? null,
        );
        $this->assertSame(
            'delta_widget_temperature',
            $registry->findCatalogRecord('delta_widget_temp')['def'] ?? null,
        );
        $this->assertSame(
            'delta_degree_widget',
            $registry->findCatalogRecord('delta_shifted_widget_temperature')['def'] ?? null,
        );
        $this->assertSame(
            '1',
            $units->conversionFactor('delta_widget_temperature', 'kelvin')->toString(),
        );
        $this->assertSame(
            '105',
            $units->point(0, self::shiftedWidgetTemperatureUnit())->valueIn('kelvin')->toString(),
        );
        $this->assertSame(
            '1',
            $units->conversionFactor('delta_shifted_widget_temperature', 'kelvin')->toString(),
        );
        $this->assertSame(
            UnitSemantics::Multiplicative,
            $registry->describe('delta_widget_temperature')?->semantics,
        );
    }

    public function testCustomAffineOverrideAlsoOverridesGeneratedBaseDifferenceUnit(): void
    {
        $registry = UnitRegistryBuilder::default()
            ->define('double_kelvin = 2 * kelvin')
            ->define('celsius = double_kelvin @ 100')
            ->build();
        $units = new Units($registry);

        $this->assertSame(
            '2',
            $units->conversionFactor('delta_celsius', 'kelvin')->toString(),
        );
    }

    public function testExplicitDifferenceNameCannotConflictWithSynthesis(): void
    {
        $builder = UnitRegistryBuilder::default()
            ->define('degree_widget = kelvin @ 100')
            ->define('delta_degree_widget = kelvin');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('conflicts with its synthesized difference unit');

        $builder->build();
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
        $widget = new Unit('widget', new Product([
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

    public function testBuilderIsMutableFluent(): void
    {
        $builder = UnitRegistryBuilder::empty();

        $this->assertSame($builder, $builder->add(new Unit('meter')));
        $this->assertSame($builder, $builder->baseUnit('USD', Dimension::CURRENCY));
        $this->assertSame($builder, $builder->define('widget = 1'));
        $this->assertSame($builder, $builder->alias('thing', 'widget'));

        $registry = $builder->build();
        $this->assertNotNull($registry->findPrebuiltUnit('meter'));
        $this->assertSame(Dimension::CURRENCY, $registry->findPrimitiveDimension('USD'));
        $this->assertNotNull($registry->findCatalogRecord('widget'));
        $this->assertNotNull($registry->findCatalogRecord('thing'));
    }

    public function testBuiltRegistriesAreSnapshotsOfMutableBuilderState(): void
    {
        $builder = UnitRegistryBuilder::empty()->add(new Unit('meter'));
        $first = $builder->build();

        $builder->add(new Unit('second'))->define('widget = meter');
        $second = $builder->build();

        $builder->baseUnit('USD', Dimension::CURRENCY);
        $third = $builder->build();

        $this->assertSame(['meter'], $first->names());
        $this->assertNull($first->findCatalogRecord('widget'));
        $this->assertSame(['meter', 'second', 'widget'], $second->names());
        $this->assertNotNull($second->findCatalogRecord('widget'));
        $this->assertNull($second->findPrimitiveDimension('USD'));
        $this->assertSame(Dimension::CURRENCY, $third->findPrimitiveDimension('USD'));
    }

    public function testAddAllIsTransactional(): void
    {
        $builder = UnitRegistryBuilder::empty()->add(new Unit('meter'));

        try {
            $builder->addAll([new Unit('second'), new Unit('meter')]);
            self::fail('Expected duplicate unit failure.');
        } catch (\InvalidArgumentException $exception) {
            $this->assertStringContainsString('Duplicate unit name', $exception->getMessage());
        }

        $this->assertSame(['meter'], $builder->build()->names());
    }

    public function testAddAllRejectsDuplicatesWithinOneBatchTransactionally(): void
    {
        $builder = UnitRegistryBuilder::empty();

        try {
            $builder->addAll([new Unit('meter'), new Unit('meter')]);
            self::fail('Expected duplicate unit failure.');
        } catch (\InvalidArgumentException $exception) {
            $this->assertStringContainsString('Duplicate unit name', $exception->getMessage());
        }

        $this->assertSame([], $builder->build()->names());
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

        $this->assertNull($registry->findPrebuiltUnit('unresolved'));
        $this->assertSame($widget, $registry->findPrebuiltUnit('thing'));
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
        ], $registry->findCatalogRecord('widget'));
    }

    public function testDefinitionAssignmentRejectsLeadingGarbage(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('name = expression');

        UnitRegistryBuilder::empty()->define('garbage widget = 1');
    }

    public function testDefinitionAssignmentRejectsEmptyInput(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unit definition must not be empty.');

        UnitRegistryBuilder::empty()->define(" \n\t ");
    }

    public function testCustomDefinitionResolutionUsesTheSharedExpressionLimits(): void
    {
        $units = new Units(UnitRegistryBuilder::empty()
            ->define('widget = ' . str_repeat('a', 1025))
            ->build());

        try {
            $units->parse('2 * widget');
            self::fail('Expected the custom definition to exceed the shared expression limits.');
        } catch (ExpressionLimitExceededException $exception) {
            $this->assertSame('token-bytes', $exception->limit);
            $this->assertSame(1024, $exception->maximum);
            $this->assertSame(1025, $exception->observed);
            $callerSpan = $exception->getSpan();
            $this->assertNotNull($callerSpan);
            $this->assertSame(4, $callerSpan->start);
            $this->assertSame(10, $callerSpan->end);

            $definitionFailure = $exception->getPrevious();
            $this->assertInstanceOf(ExpressionLimitExceededException::class, $definitionFailure);
            $definitionSpan = $definitionFailure->getSpan();
            $this->assertNotNull($definitionSpan);
            $this->assertSame(0, $definitionSpan->start);
            $this->assertSame(1025, $definitionSpan->end);
        }
    }

    public function testUnitRegistryBuilderDelegatesToEmpty(): void
    {
        $this->assertSame([], UnitRegistry::builder()->build()->names());
    }

    private static function shiftedWidgetTemperatureUnit(): string
    {
        return 'shifted_widget_temperature';
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

    private function supportsAlgebra(Units $units, string $name): bool
    {
        try {
            $units->unit($name);

            return true;
        } catch (
            UnitNotFoundException
            | UnsupportedSyntaxException
            | UnsupportedUnitAlgebraException
            | ParseException
            | \UnexpectedValueException
        ) {
            return false;
        }
    }

    private function supportsConversion(Units $units, string $name): bool
    {
        try {
            $units->dimension($name);

            return true;
        } catch (
            UnitNotFoundException
            | UnresolvableUnitDimensionException
            | UnsupportedSyntaxException
            | UnsupportedUnitConversionException
            | ParseException
            | \UnexpectedValueException
        ) {
            return false;
        }
    }
}
