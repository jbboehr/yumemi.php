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

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\Type;

/**
 * Brands a function's return with the unit declared in a @yumemi-return tag.
 *
 * This is the extension-optional annotation path: a function keeps a plain native return type in its
 * signature (so consumers without this extension are unaffected) and adds, e.g.:
 *
 *     @yumemi-return unit_int<'foot'>
 *
 * When the extension is loaded, call sites see the branded unit type instead of the bare native one.
 * Applies to every function via {@see isFunctionSupported()}; the object-method analogue is limited
 * by PHPStan's per-class dynamic-return hook and is handled separately.
 */
final class YumemiReturnTagFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{
    public function __construct(
        private readonly FileTypeMapper $fileTypeMapper,
        private readonly YumemiDocTagReader $reader,
    ) {
    }

    public function isFunctionSupported(FunctionReflection $functionReflection): bool
    {
        return $this->brandedReturnType($functionReflection) !== null;
    }

    public function getTypeFromFunctionCall(
        FunctionReflection $functionReflection,
        FuncCall $functionCall,
        Scope $scope,
    ): ?Type {
        return $this->brandedReturnType($functionReflection);
    }

    private function brandedReturnType(FunctionReflection $functionReflection): ?Type
    {
        $docComment = $functionReflection->getDocComment();
        if ($docComment === null) {
            return null;
        }

        $phpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
            $functionReflection->getFileName(),
            null,
            null,
            $functionReflection->getName(),
            $docComment,
        );

        return $this->reader->returnType($phpDoc);
    }
}
