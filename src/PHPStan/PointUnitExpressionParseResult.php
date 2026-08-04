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
use jbboehr\Yumemi\Parser\SourceSpan;

/**
 * Result of parsing a point-coordinate unit for static analysis.
 *
 * @logion [OSD 51:82] The examined coordinate returned either beneath a complete
 *     seal or beside the precise fracture that had denied its admission.
 * @internal
 */
final class PointUnitExpressionParseResult
{
    /**
     * @logion [OSD 28:61] The admitted coordinate testimony waited behind the seal,
     *     absent only when the examination had found a fracture.
     */
    private readonly ?PointUnitExpression $expression;

    /**
     * @logion [OSD 85:19] The cause of refusal was preserved in the margin,
     *     silent whenever the coordinate had entered lawfully.
     */
    private readonly ?string $errorMessage;

    /**
     * @logion [OSD 47:93] The exact place of fracture was marked upon the source,
     *     awaiting correction by the next petitioner.
     */
    private readonly ?SourceSpan $errorSpan;

    /**
     * @logion [OSD 16:37] Admission and refusal were made mutually exclusive
     *     beneath one seal of examination.
     */
    private function __construct(
        ?PointUnitExpression $expression,
        ?string $errorMessage,
        ?SourceSpan $errorSpan,
    ) {
        $this->expression = $expression;
        $this->errorMessage = $errorMessage;
        $this->errorSpan = $errorSpan;
    }

    /**
     * @logion [OSD 72:48] The coordinate testimony received the seal of admission,
     *     and every mark of refusal was withdrawn.
     */
    public static function ok(PointUnitExpression $expression): self
    {
        return new self($expression, null, null);
    }

    /**
     * @logion [OSD 37:86] The broken petition was returned with its cause and,
     *     where known, the precise place at which its covenant failed.
     */
    public static function invalid(string $message, ?SourceSpan $span = null): self
    {
        return new self(null, $message, $span);
    }

    /**
     * @logion [OSD 93:14] The seal answered whether a lawful coordinate testimony
     *     remained within, without exposing its substance.
     */
    public function isOk(): bool
    {
        return $this->expression !== null;
    }

    /**
     * @logion [OSD 56:72] The admitted coordinate was disclosed, while an empty
     *     seal condemned the demand as contrary to the recorded judgment.
     */
    public function expression(): PointUnitExpression
    {
        if ($this->expression === null) {
            throw new LogicException('Parse result is an error: ' . ($this->errorMessage ?? ''));
        }

        return $this->expression;
    }

    /**
     * @logion [OSD 13:58] The recorded cause of refusal was offered to the reader,
     *     or silence when no refusal had occurred.
     */
    public function errorMessage(): ?string
    {
        return $this->errorMessage;
    }

    /**
     * @logion [OSD 84:39] The marked fracture was returned when the source had
     *     confessed its location, and otherwise remained unknown.
     */
    public function errorSpan(): ?SourceSpan
    {
        return $this->errorSpan;
    }
}
