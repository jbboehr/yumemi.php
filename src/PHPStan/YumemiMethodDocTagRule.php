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

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Rules\Rule;
use PHPStan\Type\FileTypeMapper;

/**
 * Validates locally declared @yumemi-param tags and rejects method-level @yumemi-return tags.
 *
 * @implements Rule<InClassMethodNode>
 */
final class YumemiMethodDocTagRule implements Rule
{
    public function __construct(
        private readonly FileTypeMapper $fileTypeMapper,
        private readonly YumemiDocTagValidator $validator,
    ) {
    }

    public function getNodeType(): string
    {
        return InClassMethodNode::class;
    }

    /**
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $originalNode = $node->getOriginalNode();
        $docComment = $originalNode->getDocComment();
        if ($docComment === null || !$this->hasRelevantTag($docComment->getText())) {
            return [];
        }

        $reflection = $node->getMethodReflection();
        $classReflection = $node->getClassReflection();
        $traitReflection = $scope->isInTrait() ? $scope->getTraitReflection() : null;
        $phpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
            $scope->getFile(),
            $classReflection->getName(),
            $traitReflection?->getName(),
            $reflection->getName(),
            $docComment->getText(),
        );

        return $this->validator->validate(
            $phpDoc,
            $reflection->getParameters(),
            $reflection->getNativeReturnType(),
            false,
            $originalNode->getStartLine(),
        );
    }

    private function hasRelevantTag(string $docComment): bool
    {
        return str_contains($docComment, YumemiDocTagReader::PARAM_TAG)
            || str_contains($docComment, YumemiDocTagReader::RETURN_TAG);
    }
}
