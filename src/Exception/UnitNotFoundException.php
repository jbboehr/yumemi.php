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

namespace jbboehr\Yumemi\Exception;

final class UnitNotFoundException extends \RuntimeException
{
    public readonly string $unitName;

    /** @var list<string> */
    public readonly array $suggestions;

    /**
     * @param list<string> $suggestions
     */
    public function __construct(string $message, string $unitName, array $suggestions = [])
    {
        parent::__construct($message);
        $this->unitName = $unitName;
        $this->suggestions = $suggestions;
    }

    /**
     * @param list<string> $suggestions
     */
    public static function create(string $name, array $suggestions = []): self
    {
        $message = sprintf('Unit not found: %s.', $name);

        if ($suggestions !== []) {
            $message .= ' Did you mean: ' . implode(', ', $suggestions) . '?';
        }

        return new self($message, $name, $suggestions);
    }
}
