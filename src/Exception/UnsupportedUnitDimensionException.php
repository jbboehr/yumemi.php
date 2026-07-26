<?php

/**
 * Iudex Mensurarum Mysticarum『夢見』〜ＹＵＭＥＭＩ〜
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

namespace jbboehr\Yumemi\Exception;

final class UnsupportedUnitDimensionException extends \RuntimeException
{
    public static function create(string $unitName): self
    {
        return new self('Cannot resolve dimension for unit: ' . $unitName);
    }

    public static function missingContext(string $unitName): self
    {
        return new self(sprintf(
            'Cannot resolve dimension for unit "%s": incomplete definition and no Units context. '
            . 'Obtain units via Units::unit() (or Units::parse / quantity APIs), '
            . 'not by constructing Unit directly.',
            $unitName,
        ));
    }
}
