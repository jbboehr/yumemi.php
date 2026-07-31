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

namespace jbboehr\Yumemi\PHPStan;

use jbboehr\Yumemi\Exception\InvalidArgumentException;
use jbboehr\Yumemi\Exception\RuntimeException;
use jbboehr\Yumemi\Registry\Udunits2UnitRegistry;
use jbboehr\Yumemi\Registry\UnitRegistry;

/**
 * @internal PHPStan-container adapter for the public factory contract.
 */
final class ConfiguredUnitRegistryProvider
{
    private ?UnitRegistry $registry = null;

    public function __construct(
        private readonly ?string $factoryClass,
    ) {
    }

    public function getRegistry(): UnitRegistry
    {
        if ($this->registry !== null) {
            return $this->registry;
        }

        if ($this->factoryClass === null) {
            return $this->registry = new Udunits2UnitRegistry();
        }

        if (!class_exists($this->factoryClass)) {
            throw new InvalidArgumentException(sprintf(
                'parameters.yumemi.registryFactory must name an autoloadable class; %s was not found.',
                $this->factoryClass,
            ));
        }

        if (!is_subclass_of($this->factoryClass, UnitRegistryFactory::class)) {
            throw new InvalidArgumentException(sprintf(
                'parameters.yumemi.registryFactory must name a class implementing %s; %s does not.',
                UnitRegistryFactory::class,
                $this->factoryClass,
            ));
        }

        try {
            return $this->registry = $this->factoryClass::create();
        } catch (\Throwable $exception) {
            throw new RuntimeException(sprintf(
                'Failed to create the Yumemi unit registry with %s: %s',
                $this->factoryClass,
                $exception->getMessage(),
            ), 0, $exception);
        }
    }
}
