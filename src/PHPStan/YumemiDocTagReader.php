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

use PHPStan\PhpDoc\ResolvedPhpDocBlock;
use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\PhpDocParser\Ast\PhpDoc\GenericTagValueNode;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;

/**
 * Reads Yumemi's vendor-prefixed PHPDoc tags (@yumemi-return / @yumemi-param / @yumemi-var).
 *
 * These are unknown to PHPStan, so they survive as {@see GenericTagValueNode} generic text and are
 * ignored by consumers without this extension (graceful degradation). We re-parse the type payload
 * through {@see TypeStringResolver} so it reaches {@see UnitTypeNodeResolverExtension} — the unit
 * grammar keeps exactly one parser and one meaning across the extension.
 *
 * Only branded unit types ({@see UnitIntegerType} / {@see UnitFloatType} / {@see QuantityType}) are
 * honoured by inference. Invalid occurrences are retained as {@see YumemiDocTag} values for the
 * declaration rules, so diagnostics can be emitted without letting bad tags poison other analysis.
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
        foreach ($this->returnTags($phpDoc) as $tag) {
            if ($tag->type !== null) {
                return $tag->type;
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

        foreach ($this->paramTags($phpDoc) as $tag) {
            if ($tag->parameterName === null || $tag->type === null || isset($result[$tag->parameterName])) {
                continue;
            }

            $result[$tag->parameterName] = $tag->type;
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
     * Every @yumemi-return occurrence, including invalid ones.
     *
     * @return list<YumemiDocTag>
     */
    public function returnTags(ResolvedPhpDocBlock $phpDoc): array
    {
        $result = [];

        foreach ($this->genericTagValues($phpDoc, self::RETURN_TAG) as $text) {
            $result[] = $this->parseTypeTag(self::RETURN_TAG, $text, null, $text);
        }

        return $result;
    }

    /**
     * Every @yumemi-param occurrence, including invalid ones.
     *
     * @return list<YumemiDocTag>
     */
    public function paramTags(ResolvedPhpDocBlock $phpDoc): array
    {
        $result = [];

        foreach ($this->genericTagValues($phpDoc, self::PARAM_TAG) as $text) {
            if (preg_match('/^\s*(?<type>.+?)\s+\$(?<name>[A-Za-z_]\w*)/s', $text, $m) !== 1) {
                $result[] = new YumemiDocTag(
                    self::PARAM_TAG,
                    $text,
                    null,
                    null,
                    YumemiDocTag::ERROR_SYNTAX,
                    'expected "<unit type> $parameter".',
                );

                continue;
            }

            $result[] = $this->parseTypeTag(self::PARAM_TAG, $text, $m['name'], $m['type']);
        }

        return $result;
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

    private function parseTypeTag(
        string $tagName,
        string $payload,
        ?string $parameterName,
        string $typeText,
    ): YumemiDocTag {
        $typeText = trim($typeText);
        if ($typeText === '') {
            return new YumemiDocTag(
                $tagName,
                $payload,
                $parameterName,
                null,
                YumemiDocTag::ERROR_SYNTAX,
                'a unit type is required.',
            );
        }

        try {
            $type = $this->typeStringResolver->resolve($typeText);
        } catch (\Throwable) {
            return new YumemiDocTag(
                $tagName,
                $payload,
                $parameterName,
                null,
                YumemiDocTag::ERROR_SYNTAX,
                'the payload is not a valid PHPDoc type.',
            );
        }

        if ($type instanceof ErrorType) {
            return new YumemiDocTag(
                $tagName,
                $payload,
                $parameterName,
                null,
                YumemiDocTag::ERROR_TYPE,
                $type->getReason() ?? 'the unit type is invalid.',
            );
        }

        if (!$this->isUnitType($type)) {
            return new YumemiDocTag(
                $tagName,
                $payload,
                $parameterName,
                null,
                YumemiDocTag::ERROR_TYPE,
                sprintf(
                    'expected unit_int<\'...\'>, unit_float<\'...\'>, or Quantity<\'...\'>; %s given.',
                    $type->describe(VerbosityLevel::typeOnly()),
                ),
            );
        }

        return new YumemiDocTag($tagName, $payload, $parameterName, $type, null, null);
    }
}
