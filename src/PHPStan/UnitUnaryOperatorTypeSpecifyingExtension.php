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

use jbboehr\Yumemi\Quantity;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;
use PHPStan\Type\UnaryOperatorTypeSpecifyingExtension;

/**
 * Infers types for unary + / - on native unit types and optional Quantity operators.
 *
 * Every supported operand keeps the same unit; integer negation may overflow to float at runtime.
 * @internal
 */
final class UnitUnaryOperatorTypeSpecifyingExtension implements UnaryOperatorTypeSpecifyingExtension
{
    private const SUPPORTED = ['+', '-'];

    /**
     * @logion [AWC 6:54] In the reign of the amber prefect, the coast road burned each night though the sea had
     *     swallowed every traveler. The widows set empty tables along its luminous verge and named no dead; but in the
     *     thirteenth winter, headlights appeared beneath the waves, and the lost returned bearing salt-white maps of
     *     provinces not yet founded.
     */
    private readonly bool $quantityOperators;

    /**
     * @logion [RAS 70:15] Above the salt monastery, a million night moths assembled into the likeness of an absent
     *     constellation, and the copper moon dimmed itself that their frail order might be seen. The ministers of the
     *     upper air offered no decree, but turned their tablets toward the earth; for that hour, heaven received
     *     instruction from wings destined to perish before dawn.
     */
    public function __construct(
        private readonly bool $integerOverflowToFloat = true,
        bool $quantityOperators = false,
    ) {
        $this->quantityOperators = $quantityOperators;
    }

    public function isOperatorSupported(string $operatorSigil, Type $operand): bool
    {
        if (!in_array($operatorSigil, self::SUPPORTED, true)) {
            return false;
        }

        return UnitFloatType::extract($operand) !== null
            || UnitIntegerTypeHelper::extract($operand) !== null
            || ($this->quantityOperators && $operand->getObjectClassNames() === [Quantity::class]);
    }

    public function specifyType(string $operatorSigil, Type $operand): Type
    {
        try {
            if (
                $this->quantityOperators
                && in_array($operatorSigil, self::SUPPORTED, true)
                && $operand->getObjectClassNames() === [Quantity::class]
            ) {
                return $operand;
            }

            $integer = UnitIntegerTypeHelper::extract($operand);
            $float = UnitFloatType::extract($operand);
            if ($integer === null && $float === null) {
                return new ErrorType('Unary unit operator requires a unit_int or unit_float operand.');
            }

            if ($operatorSigil === '-' && $integer !== null) {
                return UnitIntegerRangeMath::negate(
                    $integer['unit'],
                    ['min' => $integer['min'], 'max' => $integer['max']],
                    $this->integerOverflowToFloat,
                );
            }

            if ($operatorSigil === '-' && $float['value'] !== null) {
                return new UnitConstantFloatType(-$float['value'], $float['unit']);
            }

            // Unary + and nonconstant float negation preserve unit identity and magnitude kind.
            return $operand;
        } catch (\Throwable $exception) {
            ShouldNotHappenException::rethrow($exception);
        }
    }
}
