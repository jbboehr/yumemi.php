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

use jbboehr\Yumemi\Units;

final class IncompatibleQuantityContextException extends \RuntimeException
{
    public readonly ?int $leftContextId;
    public readonly ?int $rightContextId;

    public function __construct(
        string $message,
        ?int $leftContextId = null,
        ?int $rightContextId = null,
    ) {
        parent::__construct($message);
        $this->leftContextId = $leftContextId;
        $this->rightContextId = $rightContextId;
    }

    public static function create(?Units $left = null, ?Units $right = null): self
    {
        $leftId = $left !== null ? spl_object_id($left) : null;
        $rightId = $right !== null ? spl_object_id($right) : null;

        $message = 'Quantities must use the same Units context (object identity). '
            . 'Units::default() is shared; for isolation construct new Units($registry).';

        if ($leftId !== null && $rightId !== null) {
            $message .= sprintf(' Got contexts #%d and #%d.', $leftId, $rightId);
        }

        return new self($message, $leftId, $rightId);
    }
}
