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

use PHPStan\PhpDoc\ResolvedPhpDocBlock;
use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\PhpDocParser\Ast\PhpDoc\GenericTagValueNode;
use PHPStan\Type\Type;

/**
 * Reads Yumemi's vendor-prefixed PHPDoc tags (@yumemi-return / @yumemi-param / @yumemi-var).
 *
 * These are unknown to PHPStan, so they survive as {@see GenericTagValueNode} generic text and are
 * ignored by consumers without this extension (graceful degradation). We re-parse the type payload
 * through {@see TypeStringResolver} so it reaches {@see UnitTypeNodeResolverExtension} — the unit
 * grammar keeps exactly one parser and one meaning across the extension.
 *
 * Only branded unit types ({@see UnitIntegerType} / {@see UnitFloatType} / {@see QuantityType}) are
 * honoured; anything else (a plain native type, or an {@see \PHPStan\Type\ErrorType} from an invalid
 * unit string) is treated as absent, so the tag can never poison unrelated analysis.
 */
final class YumemiDocTagReader
{
    public const RETURN_TAG = '@yumemi-return';
    public const PARAM_TAG = '@yumemi-param';
    public const VAR_TAG = '@yumemi-var';

    public function __construct(
        private readonly TypeStringResolver $typeStringResolver,
    ) {
    }

    /**
     * The unit type declared by @yumemi-return, or null when the tag is absent or not a unit type.
     *
     * The first honourable occurrence wins.
     */
    public function returnType(ResolvedPhpDocBlock $phpDoc): ?Type
    {
        foreach ($this->genericTagValues($phpDoc, self::RETURN_TAG) as $text) {
            $type = $this->resolveUnitType($text);
            if ($type !== null) {
                return $type;
            }
        }

        return null;
    }

    /**
     * The unit types declared by @yumemi-param tags, keyed by parameter name (without the `$`).
     *
     * Payload shape is `<type> $name` (e.g. `unit_int<'foot'> $length`); trailing prose is ignored.
     * Only branded unit payloads are kept; the first honourable occurrence of a name wins.
     *
     * @return array<string, Type>
     */
    public function paramTypes(ResolvedPhpDocBlock $phpDoc): array
    {
        $result = [];

        foreach ($this->genericTagValues($phpDoc, self::PARAM_TAG) as $text) {
            if (preg_match('/^\s*(?<type>.+?)\s*\$(?<name>\w+)/s', $text, $m) !== 1) {
                continue;
            }

            $name = $m['name'];
            if (isset($result[$name])) {
                continue;
            }

            $type = $this->resolveUnitType($m['type']);
            if ($type !== null) {
                $result[$name] = $type;
            }
        }

        return $result;
    }

    /**
     * Whether a type is one of our branded unit types (the only types the tags honour).
     */
    public function isUnitType(Type $type): bool
    {
        return $type instanceof UnitIntegerType
            || $type instanceof UnitFloatType
            || $type instanceof QuantityType;
    }

    /**
     * Raw text payloads of every occurrence of $tagName in the block.
     *
     * @return iterable<string>
     */
    private function genericTagValues(ResolvedPhpDocBlock $phpDoc, string $tagName): iterable
    {
        foreach ($phpDoc->getPhpDocNodes() as $node) {
            foreach ($node->getTagsByName($tagName) as $tag) {
                $value = $tag->value;
                if ($value instanceof GenericTagValueNode) {
                    yield $value->value;
                }
            }
        }
    }

    /**
     * Resolve a type payload, returning it only if it is one of our branded unit types.
     */
    private function resolveUnitType(string $text): ?Type
    {
        $text = trim($text);
        if ($text === '') {
            return null;
        }

        try {
            $type = $this->typeStringResolver->resolve($text);
        } catch (\Throwable) {
            // Unparseable payload (e.g. trailing prose): treat the tag as absent.
            return null;
        }

        return $this->isUnitType($type) ? $type : null;
    }
}
