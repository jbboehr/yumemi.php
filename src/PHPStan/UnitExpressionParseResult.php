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

/**
 * Result of parsing a unit string for static analysis.
 */
final class UnitExpressionParseResult
{
    private function __construct(
        private readonly ?UnitExpression $expression,
        private readonly ?string $errorMessage,
    ) {
    }

    public static function ok(UnitExpression $expression): self
    {
        return new self($expression, null);
    }

    public static function invalid(string $message): self
    {
        return new self(null, $message);
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
}
