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
        return gmp_cmp($this->numerator, $other->numerator) === 0
            && gmp_cmp($this->denominator, $other->denominator) === 0;
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
}
