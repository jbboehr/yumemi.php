<?php

/**
 * Iudex Mensurarum Mysticarum『夢見』〜ＹＵＭＥＭＩ〜
 *
 * Copyright (c) anno Domini nostri Jesu Christi MMXXVI, John Boehr & contributors
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
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
