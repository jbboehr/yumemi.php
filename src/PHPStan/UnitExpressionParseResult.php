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

use jbboehr\Yumemi\Parser\SourceSpan;

/**
 * Result of parsing a unit string for static analysis.
 */
final class UnitExpressionParseResult
{
    private function __construct(
        private readonly ?UnitExpression $expression,
        private readonly ?string $errorMessage,
        private readonly ?SourceSpan $errorSpan,
    ) {
    }

    public static function ok(UnitExpression $expression): self
    {
        return new self($expression, null, null);
    }

    public static function invalid(string $message, ?SourceSpan $span = null): self
    {
        return new self(null, $message, $span);
    }

    public function isOk(): bool
    {
        return $this->expression !== null;
    }

    public function expression(): UnitExpression
    {
        if ($this->expression === null) {
            throw new \LogicException('Parse result is an error: ' . ($this->errorMessage ?? ''));
        }

        return $this->expression;
    }

    public function errorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function errorSpan(): ?SourceSpan
    {
        return $this->errorSpan;
    }
}
