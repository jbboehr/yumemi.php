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

namespace jbboehr\Yumemi\Number;

use GMP;

final class Rational
{
    public readonly GMP $numerator;
    public readonly GMP $denominator;

    public function __construct(int|GMP $numerator, int|GMP $denominator = 1)
    {
        $numerator = is_int($numerator) ? gmp_init($numerator) : $numerator;
        $denominator = is_int($denominator) ? gmp_init($denominator) : $denominator;

        if (gmp_cmp($denominator, 0) === 0) {
            throw new \DivisionByZeroError('Rational denominator must not be zero.');
        }

        if (gmp_sign($denominator) < 0) {
            $numerator = gmp_neg($numerator);
            $denominator = gmp_neg($denominator);
        }

        if (gmp_cmp($denominator, 1) === 0) {
            $this->numerator = $numerator;
            $this->denominator = $denominator;

            return;
        }

        $gcd = gmp_gcd(gmp_abs($numerator), $denominator);

        $this->numerator = gmp_div_q($numerator, $gcd);
        $this->denominator = gmp_div_q($denominator, $gcd);
    }

    public static function fromInteger(int|GMP $value): self
    {
        return new self($value);
    }

    public static function fromDecimalString(string $value): self
    {
        $matches = null;

        if (preg_match('/^([+-]?\d+)(?:\.(\d+))?(?:e([+-]?\d+))?$/i', $value, $matches) !== 1) {
            throw new \InvalidArgumentException('Invalid decimal rational string: ' . $value);
        }

        $whole = $matches[1];
        $fraction = $matches[2] ?? '';
        $exponent = (int) ($matches[3] ?? 0);
        $sign = str_starts_with($whole, '-') ? '-' : '';
        $digits = ltrim($whole, '+-') . $fraction;
        $digits = ltrim($digits, '0');
        $numerator = gmp_init($sign . ($digits === '' ? '0' : $digits), 10);
        $denominator = gmp_pow(10, strlen($fraction));

        if ($exponent >= 0) {
            $numerator = gmp_mul($numerator, gmp_pow(10, $exponent));
        } else {
            $denominator = gmp_mul($denominator, gmp_pow(10, -$exponent));
        }

        return new self($numerator, $denominator);
    }

    public function add(self $other): self
    {
        return new self(
            gmp_add(
                gmp_mul($this->numerator, $other->denominator),
                gmp_mul($other->numerator, $this->denominator),
            ),
            gmp_mul($this->denominator, $other->denominator),
        );
    }

    /**
     * Compare this value with another rational.
     *
     * @return -1|0|1 Negative when this value is smaller, positive when it is greater.
     */
    public function compareTo(self $other): int
    {
        return gmp_cmp(
            gmp_mul($this->numerator, $other->denominator),
            gmp_mul($other->numerator, $this->denominator),
        ) <=> 0;
    }

    public function div(self $other): self
    {
        return new self(
            gmp_mul($this->numerator, $other->denominator),
            gmp_mul($this->denominator, $other->numerator),
        );
    }

    public function isOne(): bool
    {
        return gmp_cmp($this->numerator, 1) === 0 && gmp_cmp($this->denominator, 1) === 0;
    }

    public function equals(self $other): bool
    {
        return $this->compareTo($other) === 0;
    }

    public function mul(self $other): self
    {
        return new self(
            gmp_mul($this->numerator, $other->numerator),
            gmp_mul($this->denominator, $other->denominator),
        );
    }

    public function pow(int $power): self
    {
        if ($power === 0) {
            return new self(1);
        }

        if ($power < 0) {
            return new self(
                gmp_pow($this->denominator, -$power),
                gmp_pow($this->numerator, -$power),
            );
        }

        return new self(
            gmp_pow($this->numerator, $power),
            gmp_pow($this->denominator, $power),
        );
    }

    public function sub(self $other): self
    {
        return new self(
            gmp_sub(
                gmp_mul($this->numerator, $other->denominator),
                gmp_mul($other->numerator, $this->denominator),
            ),
            gmp_mul($this->denominator, $other->denominator),
        );
    }

    public function toString(): string
    {
        if (gmp_cmp($this->denominator, 1) === 0) {
            return gmp_strval($this->numerator);
        }

        return gmp_strval($this->numerator) . '/' . gmp_strval($this->denominator);
    }

    public function toDecimal(int $scale, \RoundingMode $mode): string
    {
        if ($scale < 0) {
            throw new \InvalidArgumentException('Decimal scale must not be negative.');
        }

        $negative = gmp_sign($this->numerator) < 0;
        $scaledNumerator = gmp_mul(gmp_abs($this->numerator), gmp_pow(10, $scale));
        $rounded = self::roundedQuotient($scaledNumerator, $this->denominator, $negative, $mode);

        return self::formatDecimal($rounded, $scale, $negative);
    }

    public function toDecimalExact(): string
    {
        if (gmp_cmp($this->numerator, 0) === 0) {
            return '0';
        }

        $denominator = $this->denominator;
        $twos = 0;
        $fives = 0;

        while (gmp_cmp(gmp_mod($denominator, 2), 0) === 0) {
            $denominator = gmp_div_q($denominator, 2);
            ++$twos;
        }

        while (gmp_cmp(gmp_mod($denominator, 5), 0) === 0) {
            $denominator = gmp_div_q($denominator, 5);
            ++$fives;
        }

        if (gmp_cmp($denominator, 1) !== 0) {
            throw new \UnexpectedValueException(
                'Rational value does not have a terminating decimal representation: ' . $this->toString(),
            );
        }

        $scale = max($twos, $fives);
        $magnitude = gmp_abs($this->numerator);

        if ($twos < $scale) {
            $magnitude = gmp_mul($magnitude, gmp_pow(2, $scale - $twos));
        }

        if ($fives < $scale) {
            $magnitude = gmp_mul($magnitude, gmp_pow(5, $scale - $fives));
        }

        return self::formatDecimal($magnitude, $scale, gmp_sign($this->numerator) < 0);
    }

    public function toFloat(): float
    {
        $sign = gmp_sign($this->numerator);

        if ($sign === 0) {
            return 0.0;
        }

        $numerator = gmp_abs($this->numerator);
        $denominator = $this->denominator;
        $exponent = self::binaryExponent($numerator, $denominator);

        if ($exponent > 1023) {
            throw new \OverflowException('Rational value does not fit in a finite float: ' . $this->toString());
        }

        if ($exponent < -1075) {
            throw new \UnderflowException('Non-zero rational value rounds to zero as a float: ' . $this->toString());
        }

        if ($exponent < -1022) {
            $significand = self::roundedQuotient(
                gmp_mul($numerator, gmp_pow(2, 1074)),
                $denominator,
                false,
                \RoundingMode::HalfEven,
            );

            if (gmp_cmp($significand, 0) === 0) {
                throw new \UnderflowException(
                    'Non-zero rational value rounds to zero as a float: ' . $this->toString(),
                );
            }

            $value = (float) gmp_strval($significand) * (2.0 ** -1074);

            return $sign < 0 ? -$value : $value;
        }

        $shift = 52 - $exponent;
        $scaledNumerator = $shift >= 0 ? gmp_mul($numerator, gmp_pow(2, $shift)) : $numerator;
        $scaledDenominator = $shift < 0 ? gmp_mul($denominator, gmp_pow(2, -$shift)) : $denominator;
        $significand = self::roundedQuotient(
            $scaledNumerator,
            $scaledDenominator,
            false,
            \RoundingMode::HalfEven,
        );

        if (gmp_cmp($significand, gmp_pow(2, 53)) === 0) {
            $significand = gmp_div_q($significand, 2);
            ++$exponent;

            if ($exponent > 1023) {
                throw new \OverflowException('Rational value does not fit in a finite float: ' . $this->toString());
            }
        }

        $value = (float) gmp_strval($significand) * (2.0 ** ($exponent - 52));

        return $sign < 0 ? -$value : $value;
    }

    public function toInt(): int
    {
        return self::nativeInt(gmp_div_q($this->numerator, $this->denominator, GMP_ROUND_ZERO));
    }

    public function toIntExact(): int
    {
        if (gmp_cmp($this->denominator, 1) !== 0) {
            throw new \UnexpectedValueException('Rational value is not an exact integer: ' . $this->toString());
        }

        return self::nativeInt($this->numerator);
    }

    private static function nativeInt(GMP $value): int
    {
        if (gmp_cmp($value, PHP_INT_MAX) > 0 || gmp_cmp($value, PHP_INT_MIN) < 0) {
            throw new \OverflowException('Rational value does not fit in a native integer: ' . gmp_strval($value));
        }

        return gmp_intval($value);
    }

    private static function binaryExponent(GMP $numerator, GMP $denominator): int
    {
        $exponent = strlen(gmp_strval($numerator, 2)) - strlen(gmp_strval($denominator, 2));

        if ($exponent >= 0) {
            if (gmp_cmp($numerator, gmp_mul($denominator, gmp_pow(2, $exponent))) < 0) {
                --$exponent;
            }
        } elseif (gmp_cmp(gmp_mul($numerator, gmp_pow(2, -$exponent)), $denominator) < 0) {
            --$exponent;
        }

        return $exponent;
    }

    private static function formatDecimal(GMP $magnitude, int $scale, bool $negative): string
    {
        $digits = gmp_strval($magnitude);
        $sign = $negative && gmp_cmp($magnitude, 0) !== 0 ? '-' : '';

        if ($scale === 0) {
            return $sign . $digits;
        }

        $digits = str_pad($digits, $scale + 1, '0', STR_PAD_LEFT);
        $integerLength = strlen($digits) - $scale;

        return $sign . substr($digits, 0, $integerLength) . '.' . substr($digits, $integerLength);
    }

    private static function roundedQuotient(
        GMP $numerator,
        GMP $denominator,
        bool $negative,
        \RoundingMode $mode,
    ): GMP {
        $quotient = gmp_div_q($numerator, $denominator, GMP_ROUND_ZERO);
        $remainder = gmp_mod($numerator, $denominator);

        if (gmp_cmp($remainder, 0) === 0) {
            return $quotient;
        }

        $halfComparison = gmp_cmp(gmp_mul($remainder, 2), $denominator);
        $increment = match ($mode) {
            \RoundingMode::TowardsZero => false,
            \RoundingMode::AwayFromZero => true,
            \RoundingMode::NegativeInfinity => $negative,
            \RoundingMode::PositiveInfinity => !$negative,
            \RoundingMode::HalfAwayFromZero => $halfComparison >= 0,
            \RoundingMode::HalfTowardsZero => $halfComparison > 0,
            \RoundingMode::HalfEven => $halfComparison > 0
                || ($halfComparison === 0 && gmp_testbit($quotient, 0)),
            \RoundingMode::HalfOdd => $halfComparison > 0
                || ($halfComparison === 0 && !gmp_testbit($quotient, 0)),
        };

        return $increment ? gmp_add($quotient, 1) : $quotient;
    }
}
