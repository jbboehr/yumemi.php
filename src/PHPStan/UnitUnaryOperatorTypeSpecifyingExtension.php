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

namespace jbboehr\Yumemi\PHPStan;

use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;
use PHPStan\Type\UnaryOperatorTypeSpecifyingExtension;

/**
 * Infers types for unary + / - on unit_int and unit_float.
 *
 * Both keep the same unit; unary - only flips the numeric magnitude (statically).
 */
final class UnitUnaryOperatorTypeSpecifyingExtension implements UnaryOperatorTypeSpecifyingExtension
{
    private const SUPPORTED = ['+', '-'];

    public function isOperatorSupported(string $operatorSigil, Type $operand): bool
    {
        if (!in_array($operatorSigil, self::SUPPORTED, true)) {
            return false;
        }

        return $operand instanceof UnitIntegerType || $operand instanceof UnitFloatType;
    }

    public function specifyType(string $operatorSigil, Type $operand): Type
    {
        if (!$operand instanceof UnitIntegerType && !$operand instanceof UnitFloatType) {
            return new ErrorType('Unary unit operator requires a unit_int or unit_float operand.');
        }

        // Unary + / - preserve unit identity and magnitude kind.
        return $operand;
    }
}
