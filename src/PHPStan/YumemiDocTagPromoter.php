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

use jbboehr\Yumemi\Exception\LogicException;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Expr\Variable;
use PhpParser\NodeVisitorAbstract;
use PHPStan\Node\VirtualNode;
use PHPStan\PhpDoc\PhpDocStringResolver;
use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\PhpDocParser\Ast\PhpDoc\GenericTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ReturnTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\VarTagValueNode;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;

/**
 * Promotes valid extension-optional @yumemi-* tags into PHPStan's native PHPDoc surface.
 * @internal
 */
final class YumemiDocTagPromoter extends NodeVisitorAbstract
{
    public const DIAGNOSTICS_ATTRIBUTE = 'yumemiDocTagPromotionDiagnostics';

    private const PARAM = 'param';
    private const RETURN = 'return';
    private const VAR = 'var';

    private const CUSTOM_TAGS = [
        '@yumemi-param' => self::PARAM,
        '@yumemi-return' => self::RETURN,
        '@yumemi-var' => self::VAR,
    ];

    private const PROMOTED_TAGS = [
        self::PARAM => '@phpstan-param',
        self::RETURN => '@phpstan-return',
        self::VAR => '@phpstan-var',
    ];

    public function __construct(
        private readonly PhpDocStringResolver $phpDocStringResolver,
        private readonly TypeStringResolver $typeStringResolver,
        private readonly YumemiTypeNodeNormalizer $normalizer,
    ) {
    }

    public function enterNode(Node $node): ?Node
    {
        if ($node instanceof VirtualNode) {
            return null;
        }

        $docComment = $node->getDocComment();
        if ($docComment === null || !str_contains($docComment->getText(), '@yumemi-')) {
            return null;
        }

        $phpDoc = $this->resolvePhpDoc($docComment->getText());
        $diagnostics = [];
        $candidates = $this->candidates($phpDoc, $node, $diagnostics);
        $duplicateKeys = $this->duplicateKeys($candidates);
        $changed = false;

        foreach ($candidates as $candidate) {
            $groupKey = $candidate->kind . "\0" . $candidate->key;
            if (isset($duplicateKeys[$groupKey])) {
                continue;
            }

            $fallback = $this->fallback($phpDoc, $candidate, $diagnostics);
            if ($fallback === false) {
                continue;
            }

            if ($fallback === null) {
                $phpDoc->children[$candidate->childIndex] = $candidate->promotedTag;
                $changed = true;

                continue;
            }

            if (!$this->matchesFallback($candidate, $fallback, $diagnostics)) {
                continue;
            }

            $this->replaceFallbackType($fallback, $candidate->type);
            $changed = true;
        }

        foreach ($duplicateKeys as $groupKey => $_) {
            [$kind, $key] = explode("\0", $groupKey, 2);
            $diagnostics[] = $this->diagnostic(
                sprintf(
                    'PHPDoc tag @yumemi-%s%s is duplicated.',
                    $kind,
                    $key === '' ? '' : sprintf(' for %s', $key),
                ),
                'yumemi.docTagDuplicate',
                $node,
            );
        }

        if ($diagnostics !== []) {
            $node->setAttribute(self::DIAGNOSTICS_ATTRIBUTE, $diagnostics);
        }

        if (!$changed) {
            return null;
        }

        $node->setDocComment(new Doc(
            (string) $phpDoc,
            $docComment->getStartLine(),
            $docComment->getStartFilePos(),
            $docComment->getStartTokenPos(),
            $docComment->getEndLine(),
            $docComment->getEndFilePos(),
            $docComment->getEndTokenPos(),
        ));

        return $node;
    }

    /**
     * @param list<array{message: string, identifier: string, line: int}> $diagnostics
     *
     * @return list<YumemiDocTagCandidate>
     */
    private function candidates(PhpDocNode $phpDoc, Node $node, array &$diagnostics): array
    {
        $result = [];

        foreach ($phpDoc->children as $index => $child) {
            if (!$child instanceof PhpDocTagNode || !isset(self::CUSTOM_TAGS[$child->name])) {
                continue;
            }

            $kind = self::CUSTOM_TAGS[$child->name];
            if (!$child->value instanceof GenericTagValueNode) {
                $diagnostics[] = $this->syntaxDiagnostic($child->name, $node);

                continue;
            }

            $promoted = $this->parsePromotedTag($kind, $child->value->value);
            $candidate = $this->candidate($index, $kind, $child, $promoted, $node, $diagnostics);
            if ($candidate !== null) {
                $result[] = $candidate;
            }
        }

        return $result;
    }

    private function parsePromotedTag(string $kind, string $payload): ?PhpDocTagNode
    {
        $phpDoc = $this->resolvePhpDoc(sprintf(
            "/**\n * %s %s\n */",
            self::PROMOTED_TAGS[$kind],
            $payload,
        ));
        $tags = $phpDoc->getTagsByName(self::PROMOTED_TAGS[$kind]);

        return $tags === [] ? null : array_values($tags)[0];
    }

    /**
     * @param self::PARAM|self::RETURN|self::VAR $kind
     * @param list<array{message: string, identifier: string, line: int}> $diagnostics
     */
    private function candidate(
        int $index,
        string $kind,
        PhpDocTagNode $original,
        ?PhpDocTagNode $promoted,
        Node $node,
        array &$diagnostics,
    ): ?YumemiDocTagCandidate {
        $value = $promoted?->value;
        if ($promoted === null) {
            $diagnostics[] = $this->syntaxDiagnostic($original->name, $node);

            return null;
        }

        if ($kind === self::PARAM && !$value instanceof ParamTagValueNode) {
            $diagnostics[] = $this->syntaxDiagnostic($original->name, $node);

            return null;
        }
        if ($kind === self::RETURN && !$value instanceof ReturnTagValueNode) {
            $diagnostics[] = $this->syntaxDiagnostic($original->name, $node);

            return null;
        }
        if ($kind === self::VAR && !$value instanceof VarTagValueNode) {
            $diagnostics[] = $this->syntaxDiagnostic($original->name, $node);

            return null;
        }

        if ($kind === self::PARAM && !$node instanceof FunctionLike) {
            $diagnostics[] = $this->diagnostic(
                'PHPDoc tag @yumemi-param is only supported on function-like declarations.',
                'yumemi.docTagUnsupported',
                $node,
            );

            return null;
        }
        if ($kind === self::RETURN && !$node instanceof FunctionLike) {
            $diagnostics[] = $this->diagnostic(
                'PHPDoc tag @yumemi-return is only supported on function-like declarations.',
                'yumemi.docTagUnsupported',
                $node,
            );

            return null;
        }

        if ($value instanceof ParamTagValueNode) {
            $key = $value->parameterName;
        } elseif ($value instanceof VarTagValueNode) {
            $key = $value->variableName;
        } else {
            $key = '';
        }

        if ($value instanceof ParamTagValueNode && !$this->hasParameter($node, $key)) {
            $diagnostics[] = $this->diagnostic(
                sprintf('PHPDoc tag @yumemi-param references unknown parameter %s.', $key),
                'yumemi.docTagParameter',
                $node,
            );

            return null;
        }

        if (!$value instanceof ParamTagValueNode && !$value instanceof ReturnTagValueNode && !$value instanceof VarTagValueNode) {
            throw new LogicException('The promoted Yumemi tag has an unexpected value node.');
        }

        $type = $value->type;
        $typeError = $this->unitTypeError($type);
        if ($typeError !== null) {
            $diagnostics[] = $this->diagnostic(
                sprintf('PHPDoc tag %s%s has invalid type: %s', $original->name, $key === '' ? '' : " for {$key}", $typeError),
                'yumemi.docTagType',
                $node,
            );

            return null;
        }

        return new YumemiDocTagCandidate($index, $kind, $key, $original, $promoted, $type, $node->getStartLine());
    }

    private function hasParameter(Node $node, string $parameterName): bool
    {
        if (!$node instanceof FunctionLike || !str_starts_with($parameterName, '$')) {
            return false;
        }

        $name = substr($parameterName, 1);
        foreach ($node->getParams() as $parameter) {
            if ($parameter->var instanceof Variable && $parameter->var->name === $name) {
                return true;
            }
        }

        return false;
    }

    private function unitTypeError(\PHPStan\PhpDocParser\Ast\Type\TypeNode $typeNode): ?string
    {
        try {
            $type = $this->typeStringResolver->resolve((string) $typeNode);
        } catch (\Throwable) {
            return 'the payload is not a valid PHPDoc type.';
        }

        $hasUnit = false;
        $error = null;
        TypeTraverser::map($type, static function (Type $inner, callable $traverse) use (&$hasUnit, &$error): Type {
            if ($inner instanceof ErrorType) {
                $error ??= $inner->getReason() ?? 'the unit type is invalid.';

                return $inner;
            }
            if (
                $inner instanceof UnitIntegerType
                || $inner instanceof UnitFloatType
                || $inner instanceof UnitNumericStringType
                || $inner instanceof QuantityType
                || $inner instanceof PointQuantityType
            ) {
                $hasUnit = true;
            }

            return $traverse($inner);
        });

        if ($error !== null) {
            return $error;
        }

        return $hasUnit
            ? null
            : "expected a type containing unit_int<'...'>, unit_float<'...'>, unit_numeric_string<'...'>, "
                . "Quantity<'...'>, or PointQuantity<'...'>.";
    }

    /**
     * @param list<YumemiDocTagCandidate> $candidates
     *
     * @return array<string, true>
     */
    private function duplicateKeys(array $candidates): array
    {
        $seen = [];
        $duplicates = [];

        foreach ($candidates as $candidate) {
            $key = $candidate->kind . "\0" . $candidate->key;
            if (isset($seen[$key])) {
                $duplicates[$key] = true;
            }
            $seen[$key] = true;
        }

        return $duplicates;
    }

    /**
     * False means an ambiguous fallback was diagnosed.
     *
     * @param list<array{message: string, identifier: string, line: int}> $diagnostics
     */
    private function fallback(
        PhpDocNode $phpDoc,
        YumemiDocTagCandidate $candidate,
        array &$diagnostics,
    ): PhpDocTagNode|false|null {
        if ($candidate->kind === self::PARAM) {
            $names = ['@phpstan-param', '@param'];
        } elseif ($candidate->kind === self::RETURN) {
            $names = ['@phpstan-return', '@return'];
        } else {
            $names = ['@phpstan-var', '@var'];
        }

        foreach ($names as $name) {
            $matching = [];
            foreach ($phpDoc->getTagsByName($name) as $tag) {
                if ($this->fallbackMatchesKey($tag, $candidate)) {
                    $matching[] = $tag;
                }
            }
            if ($matching === []) {
                continue;
            }

            if ($candidate->kind === self::VAR && $candidate->key === '' && count($matching) !== 1) {
                $diagnostics[] = $this->diagnostic(
                    'PHPDoc tag @yumemi-var without a variable name has an ambiguous fallback.',
                    'yumemi.docTagParameter',
                    $candidate->line,
                );

                return false;
            }

            return $matching[array_key_last($matching)];
        }

        return null;
    }

    private function fallbackMatchesKey(PhpDocTagNode $tag, YumemiDocTagCandidate $candidate): bool
    {
        return match (true) {
            $candidate->kind === self::PARAM && $tag->value instanceof ParamTagValueNode => $tag->value->parameterName === $candidate->key,
            $candidate->kind === self::RETURN && $tag->value instanceof ReturnTagValueNode => true,
            $candidate->kind === self::VAR && $tag->value instanceof VarTagValueNode => $tag->value->variableName === $candidate->key,
            default => false,
        };
    }

    /**
     * @param list<array{message: string, identifier: string, line: int}> $diagnostics
     */
    private function matchesFallback(
        YumemiDocTagCandidate $candidate,
        PhpDocTagNode $fallback,
        array &$diagnostics,
    ): bool {
        if ($candidate->kind === self::PARAM) {
            $promoted = $candidate->promotedTag->value;
            $fallbackValue = $fallback->value;
            if (
                !$promoted instanceof ParamTagValueNode
                || !$fallbackValue instanceof ParamTagValueNode
                || $promoted->isReference !== $fallbackValue->isReference
                || $promoted->isVariadic !== $fallbackValue->isVariadic
            ) {
                $fallbackType = $this->tagType($fallback);
                $diagnostics[] = $this->transformDiagnostic(
                    $candidate,
                    $fallbackType === null ? (string) $fallback->value : (string) $fallbackType,
                    'reference/variadic markers differ',
                );

                return false;
            }
        }

        $fallbackType = $this->tagType($fallback);
        if ($fallbackType === null) {
            throw new LogicException('The fallback Yumemi tag has an unexpected value node.');
        }
        $expected = $this->normalizer->describe($fallbackType, false);
        if ($expected === $this->normalizer->describe($candidate->type, false)) {
            return true;
        }

        $erased = $this->normalizer->describe($candidate->type, true);
        if ($expected === $erased) {
            return true;
        }

        $diagnostics[] = $this->transformDiagnostic($candidate, $expected, sprintf('its erased type is %s', $erased));

        return false;
    }

    /**
     * @return array{message: string, identifier: string, line: int}
     */
    private function transformDiagnostic(
        YumemiDocTagCandidate $candidate,
        string $fallback,
        string $reason,
    ): array {
        return [
            'message' => sprintf(
                'PHPDoc tag @yumemi-%s%s must be an exact unit transform of %s; %s.',
                $candidate->kind,
                $candidate->key === '' ? '' : sprintf(' for %s', $candidate->key),
                $fallback,
                $reason,
            ),
            'identifier' => 'yumemi.docTagTransform',
            'line' => $candidate->line,
        ];
    }

    /**
     * @return array{message: string, identifier: string, line: int}
     */
    private function syntaxDiagnostic(string $tagName, Node $node): array
    {
        return $this->diagnostic(
            sprintf('PHPDoc tag %s has invalid syntax.', $tagName),
            'yumemi.docTagSyntax',
            $node,
        );
    }

    /**
     * @return array{message: string, identifier: string, line: int}
     */
    private function diagnostic(string $message, string $identifier, Node|int $node): array
    {
        return [
            'message' => $message,
            'identifier' => $identifier,
            'line' => $node instanceof Node ? $node->getStartLine() : $node,
        ];
    }

    private function tagType(PhpDocTagNode $tag): ?\PHPStan\PhpDocParser\Ast\Type\TypeNode
    {
        $value = $tag->value;

        return $value instanceof ParamTagValueNode || $value instanceof ReturnTagValueNode || $value instanceof VarTagValueNode
            ? $value->type
            : null;
    }

    private function replaceFallbackType(
        PhpDocTagNode $tag,
        \PHPStan\PhpDocParser\Ast\Type\TypeNode $type,
    ): void {
        $value = $tag->value;
        if (!$value instanceof ParamTagValueNode && !$value instanceof ReturnTagValueNode && !$value instanceof VarTagValueNode) {
            throw new LogicException('The fallback Yumemi tag has an unexpected value node.');
        }

        $value->type = $type;
    }

    /**
     * PhpDocStringResolver is deliberately used at the parser boundary so promotion has the same
     * grammar and configuration as PHPStan. The indirection keeps that internal coupling confined
     * to one compatibility seam.
     */
    private function resolvePhpDoc(string $text): PhpDocNode
    {
        return call_user_func([$this->phpDocStringResolver, 'resolve'], $text);
    }
}
