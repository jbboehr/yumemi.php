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
 * - Quantity<'meter / second'>
 *
 * Unit strings are validated through {@see UnitExpressionParser}.
 */
final class UnitTypeNodeResolverExtension implements TypeNodeResolverExtension
{
    private const NAMES = [
        'unit_int' => 'int',
        'unit_float' => 'float',
        'quantity' => 'quantity',
    ];

    public function __construct(
        private readonly UnitExpressionParser $parser,
    ) {
    }

    public function resolve(TypeNode $typeNode, NameScope $nameScope): ?Type
    {
        if (!$typeNode instanceof GenericTypeNode) {
            return null;
        }

        $name = strtolower($typeNode->type->name);
        if (!isset(self::NAMES[$name])) {
            // Also accept FQCN-style names if users import aliases later.
            $short = $name;
            if (str_contains($name, '\\')) {
                $parts = explode('\\', $name);
                $short = end($parts);
            }

            if (!isset(self::NAMES[$short])) {
                return null;
            }

            $name = $short;
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

        $parsed = $this->parser->parse($unitString);
        if (!$parsed->isOk()) {
            return new ErrorType($parsed->errorMessage() ?? 'Invalid unit expression.');
        }

        $unit = $parsed->expression();

        return match (self::NAMES[$name]) {
            'int' => new UnitIntegerType($unit),
            'float' => new UnitFloatType($unit),
            'quantity' => new QuantityType($unit),
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
