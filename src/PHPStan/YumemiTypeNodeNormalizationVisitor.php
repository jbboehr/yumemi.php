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

use PHPStan\PhpDocParser\Ast\AbstractNodeVisitor;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprIntegerNode;
use PHPStan\PhpDocParser\Ast\Node;
use PHPStan\PhpDocParser\Ast\Type\ConstTypeNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IntersectionTypeNode;
use PHPStan\PhpDocParser\Ast\Type\NullableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;

/**
 * Cloned-TypeNode normalization used by exact Yumemi fallback matching.
 * @internal
 */
final class YumemiTypeNodeNormalizationVisitor extends AbstractNodeVisitor
{
    public function __construct(
        private readonly bool $eraseUnits,
    ) {
    }

    public function leaveNode(Node $node): ?Node
    {
        if ($this->eraseUnits && $node instanceof GenericTypeNode) {
            $erased = $this->eraseUnit($node);
            if ($erased !== null) {
                return $erased;
            }
        }

        if ($node instanceof NullableTypeNode) {
            return $this->normalizedComposite(
                [
                    $node->type,
                    new IdentifierTypeNode('null'),
                ],
                true,
            );
        }

        if ($node instanceof UnionTypeNode) {
            return $this->normalizedComposite(array_values($node->types), true);
        }

        if ($node instanceof IntersectionTypeNode) {
            return $this->normalizedComposite(array_values($node->types), false);
        }

        return null;
    }

    private function eraseUnit(GenericTypeNode $node): ?IdentifierTypeNode
    {
        $name = strtolower(ltrim($node->type->name, '\\'));
        $parts = explode('\\', $name);
        $shortName = end($parts);

        return match ($shortName) {
            'unit_int' => new IdentifierTypeNode('int'),
            'unit_float' => new IdentifierTypeNode('float'),
            'unit_numeric_string' => new IdentifierTypeNode('numeric-string'),
            'quantity', 'pointquantity' => new IdentifierTypeNode($node->type->name),
            default => null,
        };
    }

    /**
     * @param list<TypeNode> $types
     */
    private function normalizedComposite(array $types, bool $union): TypeNode
    {
        $flattened = [];

        foreach ($types as $type) {
            if ($union && $type instanceof UnionTypeNode) {
                array_push($flattened, ...$type->types);
            } elseif (!$union && $type instanceof IntersectionTypeNode) {
                array_push($flattened, ...$type->types);
            } else {
                $flattened[] = $type;
            }
        }

        $byDescription = [];
        foreach ($flattened as $type) {
            $byDescription[(string) $type] = $type;
        }
        ksort($byDescription);

        if (!$union && isset($byDescription['int'])) {
            $hasIntegerRefinement = false;
            foreach ($byDescription as $description => $type) {
                if ($description === 'int') {
                    continue;
                }

                if (
                    ($type instanceof GenericTypeNode && strtolower($type->type->name) === 'int')
                    || ($type instanceof ConstTypeNode && $type->constExpr instanceof ConstExprIntegerNode)
                    || ($type instanceof IdentifierTypeNode && in_array(strtolower($type->name), [
                        'negative-int',
                        'non-negative-int',
                        'non-positive-int',
                        'non-zero-int',
                        'positive-int',
                    ], true))
                ) {
                    $hasIntegerRefinement = true;
                    break;
                }
            }

            if ($hasIntegerRefinement) {
                unset($byDescription['int']);
            }
        }

        $normalized = array_values($byDescription);

        if (count($normalized) === 1) {
            return $normalized[0];
        }

        return $union ? new UnionTypeNode($normalized) : new IntersectionTypeNode($normalized);
    }
}
