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

use jbboehr\Yumemi\Catalog\CatalogNameKind;
use jbboehr\Yumemi\Catalog\UnitKind;
use jbboehr\Yumemi\Dimension;
use jbboehr\Yumemi\Exception\UnitNotFoundException;
use jbboehr\Yumemi\Registry\Udunits2UnitRegistry;
use jbboehr\Yumemi\Registry\UnitRegistryBuilder;
use jbboehr\Yumemi\Units;
use PHPUnit\Framework\TestCase;

final class BundledUnitRegistryTest extends TestCase
{
    public function testBundledPublishingUnitsHaveExactDefinitions(): void
    {
        $units = Units::default();

        $this->assertSame('1/96', $units->conversionFactor('css_pixel', 'inch')->toString());
        $this->assertSame('1/72', $units->conversionFactor('typographic_point', 'inch')->toString());
        $this->assertSame('1/1440', $units->conversionFactor('twip', 'inch')->toString());
        $this->assertSame('1/914400', $units->conversionFactor('EMU', 'inch')->toString());

        $emu = $units->describe('EMU');
        $this->assertNotNull($emu);
        $this->assertSame('css_pixel', $units->describe('css_pixels')?->canonicalName);
        $this->assertSame('typographic_point', $units->describe('typographic_points')?->canonicalName);
        $this->assertSame('twip', $units->describe('twips')?->canonicalName);
        $this->assertSame('english_metric_unit', $units->describe('english_metric_units')?->canonicalName);
        $this->assertSame('english_metric_unit', $emu->canonicalName);
        $this->assertSame(CatalogNameKind::Symbol, $emu->matchedAs);
    }

    public function testPixelIsANominalRasterDimension(): void
    {
        $units = Units::default();
        $pixel = $units->describe('pixel');
        $this->assertNotNull($pixel);

        $this->assertSame(Dimension::IMAGE_SAMPLE, $units->dimension('pixel')->toString());
        $this->assertSame(['image_sample' => 1], $units->dimension('pixel')->namedPowers());
        $this->assertSame('image_sample ^ 2', $units->dimension('pixel ^ 2')->toString());
        $this->assertSame('image_sample / length', $units->dimension('pixel / inch')->toString());
        $this->assertFalse($units->areCompatible('pixel', 'css_pixel'));
        $this->assertFalse($units->areCompatible('pixel', 'meter'));
        $this->assertSame('1000000', $units->conversionFactor('megapixel', 'pixel')->toString());

        $this->assertSame('pixel', $pixel->canonicalName);
        $this->assertSame(UnitKind::Base, $pixel->kind);
        $this->assertSame(['pixels'], $pixel->generatedPlurals);
        $this->assertSame(CatalogNameKind::GeneratedPlural, $units->describe('pixels')?->matchedAs);
    }

    public function testAmbiguousAbbreviationsRetainUdunits2Meanings(): void
    {
        $units = Units::default();

        $this->assertTrue($units->areCompatible('pt', 'US_liquid_pint'));
        $this->assertFalse($units->areCompatible('pt', 'typographic_point'));
        $this->assertSame('printers_pica', $units->unit('pica')->toString());
        $this->assertTrue($units->dimension('dpi')->isDimensionless());
        $this->assertTrue($units->dimension('ppi')->isDimensionless());

        $this->expectException(UnitNotFoundException::class);
        $units->parse('px');
    }

    public function testPureUdunits2RegistryDoesNotContainTheSupplement(): void
    {
        $registry = new Udunits2UnitRegistry();

        $this->assertNull($registry->findCatalogRecord('pixel'));
        $this->assertNull($registry->findCatalogRecord('css_pixel'));
        $this->assertNull($registry->findCatalogRecord('typographic_point'));
        $this->assertNull($registry->findCatalogRecord('twip'));
        $this->assertNull($registry->findCatalogRecord('english_metric_unit'));
    }

    public function testApplicationOverlayMayReplaceASupplementalName(): void
    {
        $units = new Units(UnitRegistryBuilder::default()
            ->define('pixel = meter')
            ->build());

        $this->assertSame('length', $units->dimension('pixel')->toString());
        $this->assertSame('1', $units->conversionFactor('pixel', 'meter')->toString());
        $this->assertSame('1', $units->conversionFactor('pixels', 'meter')->toString());
    }
}
