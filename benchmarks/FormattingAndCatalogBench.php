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

namespace jbboehr\Yumemi\Benchmarks;

use jbboehr\Yumemi\Catalog\UnitDescriptor;
use jbboehr\Yumemi\Dimension;
use jbboehr\Yumemi\Expr;
use jbboehr\Yumemi\Formatter\DivisionStyle;
use jbboehr\Yumemi\Formatter\ExprFormatter;
use jbboehr\Yumemi\Formatter\FormatOptions;
use jbboehr\Yumemi\Formatter\Typography;
use jbboehr\Yumemi\Formatter\UnitNameStyle;
use jbboehr\Yumemi\Registry\UnitRegistry;
use jbboehr\Yumemi\Registry\UnitRegistryBuilder;
use jbboehr\Yumemi\Registry\Udunits2UnitRegistry;
use jbboehr\Yumemi\Units;
use PhpBench\Attributes as Bench;

#[Bench\Iterations(5)]
#[Bench\Warmup(2)]
#[Bench\Groups(['runtime', 'formatting', 'catalog'])]
final class FormattingAndCatalogBench
{
    private Units $units;
    private Udunits2UnitRegistry $registry;
    private Expr $expr;
    private ExprFormatter $warmFormatter;
    private FormatOptions $symbolOptions;

    /** @param array{unit?: string} $params */
    public function setUp(array $params = []): void
    {
        $this->registry = new Udunits2UnitRegistry();
        $this->units = new Units($this->registry);
        $this->expr = isset($params['unit'])
            ? new Expr\Unit($params['unit'])
            : $this->units->parse('kilometer / second^2');
        $this->symbolOptions = FormatOptions::create()
            ->withUnitNameStyle(UnitNameStyle::Symbol)
            ->withTypography(Typography::Unicode)
            ->withDivisionStyle(DivisionStyle::NegativePowers);
        $this->warmFormatter = $this->units->formatter($this->symbolOptions);
        $this->warmFormatter->format($this->expr);
    }

    #[Bench\BeforeMethods('setUp')]
    #[Bench\Revs(500)]
    public function benchPreservedFormatting(): string
    {
        return $this->units->format($this->expr);
    }

    #[Bench\BeforeMethods('setUp')]
    #[Bench\Revs(10)]
    #[Bench\ParamProviders('provideSymbolNames')]
    public function benchColdSymbolFormatter(): string
    {
        return $this->units->formatter($this->symbolOptions)->format($this->expr);
    }

    #[Bench\BeforeMethods('setUp')]
    #[Bench\Revs(500)]
    #[Bench\ParamProviders('provideSymbolNames')]
    public function benchWarmSymbolFormatter(): string
    {
        return $this->warmFormatter->format($this->expr);
    }

    /** @return iterable<string, array{unit?: string}> */
    public function provideSymbolNames(): iterable
    {
        yield 'resolved expression' => [];
        yield 'dynamic prefix' => ['unit' => 'kilometer'];
        yield 'symbol collision' => ['unit' => 'milliinch'];
        yield 'punctuation symbol' => ['unit' => 'millipercent'];
    }

    #[Bench\BeforeMethods('setUp')]
    #[Bench\Revs(10)]
    public function benchDescribeCanonicalUnit(): ?UnitDescriptor
    {
        return $this->units->describe('meter');
    }

    #[Bench\BeforeMethods('setUp')]
    #[Bench\Revs(10)]
    public function benchDescribePrefixedUnit(): ?UnitDescriptor
    {
        return $this->units->describe('kilometer');
    }

    #[Bench\BeforeMethods('setUp')]
    #[Bench\Revs(1)]
    public function benchDescribeWholeCatalog(): int
    {
        $described = 0;

        foreach ($this->registry->names() as $name) {
            $described += $this->units->describe($name) === null ? 0 : 1;
        }

        return $described;
    }

    #[Bench\Revs(1)]
    public function benchConstructBundledRegistry(): UnitRegistry
    {
        return UnitRegistry::bundled();
    }

    #[Bench\Revs(1)]
    public function benchConstructDefaultCompositeRegistry(): UnitRegistry
    {
        return UnitRegistryBuilder::default()->build();
    }

    #[Bench\Revs(1)]
    public function benchConstructCustomRegistryOverlay(): UnitRegistry
    {
        return UnitRegistryBuilder::default()
            ->baseUnit('benchmark_credit', Dimension::CURRENCY)
            ->define('benchmark_token = 100 / 107 * benchmark_credit')
            ->alias('benchmark_tokens', 'benchmark_token')
            ->build();
    }
}
