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

namespace jbboehr\Yumemi\Tests\PHPStan;

use jbboehr\Yumemi\PHPStan\ConfiguredUnitRegistryProvider;
use jbboehr\Yumemi\PHPStan\UnitExpressionParser;
use jbboehr\Yumemi\PHPStan\UnitRegistryFactory;
use jbboehr\Yumemi\Registry\Udunits2UnitRegistry;
use jbboehr\Yumemi\Registry\UnitRegistry;
use jbboehr\Yumemi\Registry\UnitRegistryBuilder;
use jbboehr\Yumemi\Units;
use PHPUnit\Framework\TestCase;

final class ConfiguredUnitRegistryProviderTest extends TestCase
{
    protected function setUp(): void
    {
        CountingUnitRegistryFactory::$calls = 0;
    }

    public function testNullFactoryUsesUdunits2(): void
    {
        $registry = (new ConfiguredUnitRegistryProvider(null))->getRegistry();

        $this->assertInstanceOf(Udunits2UnitRegistry::class, $registry);
        $this->assertNotNull($registry->findCatalogRecord('meter'));
    }

    public function testConfiguredFactoryIsInvokedOnce(): void
    {
        $provider = new ConfiguredUnitRegistryProvider(CountingUnitRegistryFactory::class);

        $first = $provider->getRegistry();
        $second = $provider->getRegistry();

        $this->assertSame($first, $second);
        $this->assertSame(1, CountingUnitRegistryFactory::$calls);
    }

    public function testFactoryControlsTheCompleteRegistry(): void
    {
        $registry = (new ConfiguredUnitRegistryProvider(IsolatedUnitRegistryFactory::class))->getRegistry();
        $parser = new UnitExpressionParser(new Units($registry));

        $this->assertTrue($parser->parse('widget')->isOk());
        $this->assertFalse($parser->parse('meter')->isOk());
    }

    public function testFactoryCanOverrideUdunits2Name(): void
    {
        $registry = (new ConfiguredUnitRegistryProvider(OverridingUnitRegistryFactory::class))->getRegistry();
        $result = (new UnitExpressionParser(new Units($registry)))->parse('foot');

        $this->assertTrue($result->isOk());
        $this->assertSame('99 * meter', $result->expression()->normalizedExpr->toString());
    }

    public function testMissingFactoryClassIsRejected(): void
    {
        $provider = new ConfiguredUnitRegistryProvider(__NAMESPACE__ . '\\MissingUnitRegistryFactory');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must name an autoloadable class');

        $provider->getRegistry();
    }

    public function testFactoryMustImplementContract(): void
    {
        $provider = new ConfiguredUnitRegistryProvider(InvalidUnitRegistryFactory::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must name a class implementing');

        $provider->getRegistry();
    }

    public function testFactoryFailureHasConfigurationContext(): void
    {
        $provider = new ConfiguredUnitRegistryProvider(ThrowingUnitRegistryFactory::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to create the Yumemi unit registry');
        $this->expectExceptionMessage('fixture registry failure');

        $provider->getRegistry();
    }
}

final class CountingUnitRegistryFactory implements UnitRegistryFactory
{
    public static int $calls = 0;

    public static function create(): UnitRegistry
    {
        ++self::$calls;

        return UnitRegistryBuilder::default()
            ->define('widget = 12 * meter')
            ->build();
    }
}

final class IsolatedUnitRegistryFactory implements UnitRegistryFactory
{
    public static function create(): UnitRegistry
    {
        return UnitRegistryBuilder::empty()
            ->define('widget = 1')
            ->build();
    }
}

final class OverridingUnitRegistryFactory implements UnitRegistryFactory
{
    public static function create(): UnitRegistry
    {
        return UnitRegistryBuilder::default()
            ->define('foot = 99 * meter')
            ->build();
    }
}

final class InvalidUnitRegistryFactory
{
}

final class ThrowingUnitRegistryFactory implements UnitRegistryFactory
{
    public static function create(): UnitRegistry
    {
        throw new \RuntimeException('fixture registry failure');
    }
}
