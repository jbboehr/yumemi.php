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
use PHPStan\Node\InFunctionNode;
use PHPStan\Rules\Rule;
use PHPStan\Type\FileTypeMapper;

/**
 * Validates locally declared @yumemi-param and @yumemi-return tags on named functions.
 *
 * @implements Rule<InFunctionNode>
 */
final class YumemiFunctionDocTagRule implements Rule
{
    public function __construct(
        private readonly FileTypeMapper $fileTypeMapper,
        private readonly YumemiDocTagValidator $validator,
    ) {
    }

    public function getNodeType(): string
    {
        return InFunctionNode::class;
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

        $reflection = $node->getFunctionReflection();
        $phpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
            $scope->getFile(),
            null,
            null,
            $reflection->getName(),
            $docComment->getText(),
        );

        return $this->validator->validate(
            $phpDoc,
            $reflection->getParameters(),
            $reflection->getNativeReturnType(),
            true,
            $originalNode->getStartLine(),
        );
    }

    private function hasRelevantTag(string $docComment): bool
    {
        return str_contains($docComment, YumemiDocTagReader::PARAM_TAG)
            || str_contains($docComment, YumemiDocTagReader::RETURN_TAG);
    }
}
