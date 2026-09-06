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

use jbboehr\Yumemi\PointQuantity;
use jbboehr\Yumemi\Quantity;
use PHPStan\Analyser\NameScope;
use PHPStan\PhpDoc\TypeNodeResolverExtension;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprStringNode;
use PHPStan\PhpDocParser\Ast\Type\ConstTypeNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;

/**
 * Resolves PHPDoc types:
 *
 * - unit_int<'meter / second'>
 * - unit_float<'kilogram'>
 * - unit_numeric_string<'second'>
 * - Quantity<'meter / second'>
 * - PointQuantity<'celsius'>
 *
 * Unit strings are validated through {@see UnitExpressionParser}.
 * @internal
 */
final class UnitTypeNodeResolverExtension implements TypeNodeResolverExtension
{
    private const NAMES = [
        'unit_int' => 'int',
        'unit_float' => 'float',
        'unit_numeric_string' => 'numeric-string',
    ];

    public function __construct(
        private readonly UnitExpressionParser $parser,
    ) {
    }

    public function resolve(TypeNode $typeNode, NameScope $nameScope): ?Type
    {
        try {
            if (!$typeNode instanceof GenericTypeNode) {
                return null;
            }

            $name = strtolower($typeNode->type->name);
            $kind = self::resolveKind($typeNode->type->name, $nameScope);
            if ($kind === null) {
                return null;
            }

            if (count($typeNode->genericTypes) !== 1) {
                return new ErrorType(sprintf(
                    '%s expects exactly one unit string type argument, e.g. %s<\'meter\'>.',
                    $name,
                    $name,
                ));
            }

            $unitString = $this->extractStringLiteral($typeNode->genericTypes[0]);
            if ($unitString === null) {
                return new ErrorType(sprintf(
                    '%s unit argument must be a string literal, e.g. %s<\'meter / second\'>.',
                    $name,
                    $name,
                ));
            }

            if ($kind === 'point') {
                $parsed = $this->parser->parsePoint($unitString);
                if (!$parsed->isOk()) {
                    return new ErrorType($parsed->errorMessage() ?? 'Invalid point unit.');
                }

                return new PointQuantityType($parsed->expression());
            }

            $parsed = $this->parser->parse($unitString);
            if (!$parsed->isOk()) {
                return new ErrorType($parsed->errorMessage() ?? 'Invalid unit expression.');
            }

            $unit = $parsed->expression();

            return match ($kind) {
                'int' => new UnitIntegerType($unit),
                'float' => new UnitFloatType($unit),
                'numeric-string' => new UnitNumericStringType($unit),
                'quantity' => new QuantityType($unit),
            };
        } catch (\Throwable $exception) {
            ShouldNotHappenException::rethrow($exception);
        }
    }

    /**
     * @logion [OSD 87:44] At the feast of reconciliation, let the mourner wear his grey sash among the wedding
     *     colours, and command no song from him. Peace hath restored his place at the table; it hath not
     *     returned his daughter. Serve him before the musicians begin, and let the bride herself receive his
     *     blessing without requiring him to smile.
     *
     * @return 'int'|'float'|'numeric-string'|'quantity'|'point'|null
     */
    public static function resolveKind(string $name, NameScope $nameScope): ?string
    {
        return self::NAMES[strtolower($name)] ?? match (strtolower($nameScope->resolveStringName($name))) {
            strtolower(Quantity::class) => 'quantity',
            strtolower(PointQuantity::class) => 'point',
            default => null,
        };
    }

    private function extractStringLiteral(TypeNode $node): ?string
    {
        if (!$node instanceof ConstTypeNode) {
            return null;
        }

        $expr = $node->constExpr;
        if (!$expr instanceof ConstExprStringNode) {
            return null;
        }

        // PhpDocParser stores the raw quoted value in older versions; prefer getValue when present.
        if (method_exists($expr, 'getValue')) {
            /** @var string $value */
            $value = $expr->getValue();

            return $value;
        }

        $value = $expr->value ?? null;
        if (!is_string($value)) {
            return null;
        }

        // Strip surrounding quotes if present.
        if (
            strlen($value) >= 2
            && (
                ($value[0] === "'" && str_ends_with($value, "'"))
                || ($value[0] === '"' && str_ends_with($value, '"'))
            )
        ) {
            return substr($value, 1, -1);
        }

        return $value;
    }
}
