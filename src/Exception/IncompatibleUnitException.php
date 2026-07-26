<?php

/**
 * Iudex Mensurarum Mysticarum『夢見』〜ＹＵＭＥＭＩ〜
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

namespace jbboehr\Yumemi\Exception;

use jbboehr\Yumemi\Dimension;
use jbboehr\Yumemi\Expr;
use jbboehr\Yumemi\Formatter\ExprFormatter;

final class IncompatibleUnitException extends \RuntimeException
{
    public readonly Expr $from;
    public readonly Expr $to;
    public readonly ?Dimension $fromDimension;
    public readonly ?Dimension $toDimension;

    public function __construct(
        string $message,
        Expr $from,
        Expr $to,
        ?Dimension $fromDimension = null,
        ?Dimension $toDimension = null,
    ) {
        parent::__construct($message);
        $this->from = $from;
        $this->to = $to;
        $this->fromDimension = $fromDimension;
        $this->toDimension = $toDimension;
    }

    public static function create(
        Expr $from,
        Expr $to,
        ?Dimension $fromDimension = null,
        ?Dimension $toDimension = null,
    ): self {
        $message = sprintf(
            'Incompatible unit expressions: %s and %s.',
            ExprFormatter::format($from),
            ExprFormatter::format($to),
        );

        if ($fromDimension !== null && $toDimension !== null) {
            if ($fromDimension->equals($toDimension)) {
                $message .= sprintf(
                    ' Both have dimension %s; convert explicitly before adding or subtracting.',
                    $fromDimension->toString(),
                );
            } else {
                $message .= sprintf(
                    ' Dimensions: %s vs %s.',
                    $fromDimension->toString(),
                    $toDimension->toString(),
                );
            }
        }

        return new self($message, $from, $to, $fromDimension, $toDimension);
    }
}
