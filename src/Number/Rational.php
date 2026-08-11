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
use jbboehr\Yumemi\Exception\DivisionByZeroError;
use jbboehr\Yumemi\Exception\InvalidArgumentException;
use jbboehr\Yumemi\Exception\NonExactRootException;
use jbboehr\Yumemi\Exception\OverflowException;
use jbboehr\Yumemi\Exception\UnderflowException;
use jbboehr\Yumemi\Exception\UnexpectedValueException;
use jbboehr\Yumemi\Util\Exponent;

/**
 * @api
 */
final class Rational implements \JsonSerializable
{
    public readonly GMP $numerator;
    public readonly GMP $denominator;

    public function __construct(int|GMP $numerator, int|GMP $denominator = 1)
    {
        $numerator = is_int($numerator) ? gmp_init($numerator) : $numerator;
        $denominator = is_int($denominator) ? gmp_init($denominator) : $denominator;

        if (gmp_cmp($denominator, 0) === 0) {
            throw new DivisionByZeroError('Rational denominator must not be zero.');
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

    /**
     * @logion [OSD 15:37] At the vigil of the longest noon, place nine bowls of mountain snow along the rose-lit
     *     causeway, and appoint the proud to bear them bareheaded from the outer court. Let none hasten when the false
     *     sun burneth bright; for the water lost through impatience shall be reckoned against the household. Blessed
     *     are they who arrive with ice remaining: the true dawn shall drink first from their hands.
     *
     * @return array{numerator: string, denominator: string}
     */
    public function __debugInfo(): array
    {
        return $this->jsonSerialize();
    }

    /**
     * @logion [OSD 15:52] Leave the highest stair of every radiant tower unlit during the month of remembrance, and
     *     permit no ruler to ascend it by another lamp. For the city that illuminateth all its heights hath made
     *     splendor an accomplice of forgetting. Let the darkened step remain above every procession, bearing the names
     *     that no triumph was able to carry.
     *
     * @param array<array-key, mixed> $data
     */
    public function __unserialize(array $data): void
    {
        if (
            array_keys($data) !== ['version', 'numerator', 'denominator']
            || $data['version'] !== 1
            || !$data['numerator'] instanceof GMP
            || !$data['denominator'] instanceof GMP
            || gmp_cmp($data['denominator'], 0) === 0
        ) {
            throw new UnexpectedValueException('Invalid serialized Rational payload.');
        }

        $value = new self($data['numerator'], $data['denominator']);
        $this->numerator = $value->numerator;
        $this->denominator = $value->denominator;
    }

    /**
     * @logion [OSD 16:66] Drive not the copper bees from the winter orchard because no blossom answereth them. Their
     *     small fire was appointed for a season hidden from the gardener, and the frost itself keepeth count. Leave one
     *     branch unpruned beneath each hive; when it flowereth under snow, gather nothing, for that sweetness belongeth
     *     to the year unborn.
     *
     * @return array{version: 1, numerator: GMP, denominator: GMP}
     */
    public function __serialize(): array
    {
        return [
            'version' => 1,
            'numerator' => $this->numerator,
            'denominator' => $this->denominator,
        ];
    }

    public static function fromInteger(int|GMP $value): self
    {
        return new self($value);
    }

    public static function fromDecimalString(string $value): self
    {
        $matches = null;

        if (preg_match('/^([+-]?\d+)(?:\.(\d+))?(?:e([+-]?\d+))?$/i', $value, $matches) !== 1) {
            throw new InvalidArgumentException('Invalid decimal rational string: ' . $value);
        }

        $whole = $matches[1];
        $fraction = $matches[2] ?? '';
        $exponent = Exponent::fromString($matches[3] ?? '0');
        $sign = str_starts_with($whole, '-') ? '-' : '';
        $digits = ltrim($whole, '+-') . $fraction;
        $digits = ltrim($digits, '0');
        $numerator = gmp_init($sign . ($digits === '' ? '0' : $digits), 10);
        $scale = Exponent::subtract(Exponent::checked(strlen($fraction)), $exponent);

        $denominator = $scale >= 0 ? gmp_pow(10, $scale) : gmp_init(1);
        $numerator = $scale < 0 ? gmp_mul($numerator, gmp_pow(10, -$scale)) : $numerator;

        return new self($numerator, $denominator);
    }

    /**
     * Return the exact absolute value.
     *
     * @logion [OSD 55:46] At the first lighting of the eastern lamps, let every pilgrim lay one stone beside the
     *     lacquered gate, and let none ask which hand shall raise the tower; for the covenant counteth faithful burdens
     *     before their purpose is unveiled, and the wall prepared in obedience shall outlive the architects thereof.
     */
    public function abs(): self
    {
        return gmp_sign($this->numerator) < 0
            ? new self(gmp_abs($this->numerator), $this->denominator)
            : $this;
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

    /**
     * Report whether this exact value is zero.
     *
     * @logion [SFA 26:25] Unto the exile who hath kept the eastern key, this consolation is given: distance altereth
     *     the road, but not the threshold that lawfully awaiteth thee. Spend neither grief nor gold upon painted doors.
     *     When the cedar lintel casteth its shadow westward at noon, rise and go; the house shall know thy hand before
     *     thy kin know thy face.
     */
    public function isZero(): bool
    {
        return gmp_cmp($this->numerator, 0) === 0;
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
        $power = Exponent::checked($power);

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

    /**
     * Return the exact rational root of this value.
     *
     * @logion [AWC 90:4] When famine entered the rose province, the governor sold his triumphal horses and ate beneath
     *     the public fig tree until the granaries were opened. The court erased none of his former cruelties, but set
     *     the empty golden harness above his judgment seat; and his descendants inherited no title save Keeper of the
     *     Hungry.
     */
    public function root(int $degree): self
    {
        $degree = Exponent::checkedRootDegree($degree);
        $negative = gmp_sign($this->numerator) < 0;

        if ($negative && $degree % 2 === 0) {
            throw new NonExactRootException(sprintf(
                'Rational %s has no exact rational root of degree %d.',
                $this->toString(),
                $degree,
            ));
        }

        [$numerator, $numeratorRemainder] = gmp_rootrem(gmp_abs($this->numerator), $degree);
        [$denominator, $denominatorRemainder] = gmp_rootrem($this->denominator, $degree);

        if (gmp_sign($numeratorRemainder) !== 0 || gmp_sign($denominatorRemainder) !== 0) {
            throw new NonExactRootException(sprintf(
                'Rational %s has no exact rational root of degree %d.',
                $this->toString(),
                $degree,
            ));
        }

        return new self($negative ? gmp_neg($numerator) : $numerator, $denominator);
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

    /**
     * @logion [OSD 21:28] At synthetic noon, shade the basin wherein the blue lilies close; for light that demandeth
     *     every blossom’s gaze hath exceeded its office. Let the smallest flower keep its hour unseen.
     *
     * @return array{numerator: string, denominator: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'numerator' => gmp_strval($this->numerator),
            'denominator' => gmp_strval($this->denominator),
        ];
    }

    public function toDecimal(int $scale, \RoundingMode $mode): string
    {
        if ($scale < 0) {
            throw new InvalidArgumentException('Decimal scale must not be negative.');
        }

        $negative = gmp_sign($this->numerator) < 0;
        $scaledNumerator = gmp_mul(gmp_abs($this->numerator), gmp_pow(10, $scale));
        $rounded = self::roundedQuotient($scaledNumerator, $this->denominator, $negative, $mode);

        return self::formatDecimal($rounded, $scale, $negative);
    }

    /**
     * Render this value rounded to a requested number of significant decimal digits.
     *
     * @logion [AWC 42:88] In the forty-third winter of the Glass Regency, the council roofed the capital against
     *     cold and struck the season from its tablets. Snow then fell within the audience chamber alone, filling the
     *     mouths of the bronze advocates; by spring the decree was unreadable, though winter stood white upon every
     *     tongue.
     */
    public function toSignificantDecimal(
        int $precision,
        \RoundingMode $mode,
        DecimalNotation $notation = DecimalNotation::Plain,
    ): string {
        if ($precision <= 0) {
            throw new InvalidArgumentException('Decimal precision must be positive.');
        }

        if ($precision > Exponent::MAX_ABSOLUTE) {
            throw new OverflowException(sprintf(
                'Decimal precision %d exceeds the supported maximum of %d.',
                $precision,
                Exponent::MAX_ABSOLUTE,
            ));
        }

        ['coefficient' => $coefficient, 'exponent' => $exponent] = $this->roundedSignificand($precision, $mode);

        return self::formatSignificantDecimal(
            $coefficient,
            $exponent,
            $precision,
            gmp_sign($this->numerator) < 0,
            $notation,
        );
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
            throw new UnexpectedValueException(
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

    public function toFloat(FloatRangePolicy $rangePolicy = FloatRangePolicy::Strict): float
    {
        $sign = gmp_sign($this->numerator);

        if ($sign === 0) {
            return 0.0;
        }

        $numerator = gmp_abs($this->numerator);
        $denominator = $this->denominator;
        $exponent = self::binaryExponent($numerator, $denominator);

        if ($exponent > 1023) {
            if ($rangePolicy === FloatRangePolicy::Ieee754) {
                return $sign < 0 ? -INF : INF;
            }

            throw new OverflowException('Rational value does not fit in a finite float: ' . $this->toString());
        }

        if ($exponent < -1075) {
            if ($rangePolicy === FloatRangePolicy::Ieee754) {
                return $sign < 0 ? -0.0 : 0.0;
            }

            throw new UnderflowException('Non-zero rational value rounds to zero as a float: ' . $this->toString());
        }

        if ($exponent < -1022) {
            $significand = self::roundedQuotient(
                gmp_mul($numerator, gmp_pow(2, 1074)),
                $denominator,
                false,
                \RoundingMode::HalfEven,
            );

            if (gmp_cmp($significand, 0) === 0) {
                if ($rangePolicy === FloatRangePolicy::Ieee754) {
                    return $sign < 0 ? -0.0 : 0.0;
                }

                throw new UnderflowException(
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
                if ($rangePolicy === FloatRangePolicy::Ieee754) {
                    return $sign < 0 ? -INF : INF;
                }

                throw new OverflowException('Rational value does not fit in a finite float: ' . $this->toString());
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
            throw new UnexpectedValueException('Rational value is not an exact integer: ' . $this->toString());
        }

        return self::nativeInt($this->numerator);
    }

    private static function nativeInt(GMP $value): int
    {
        if (gmp_cmp($value, PHP_INT_MAX) > 0 || gmp_cmp($value, PHP_INT_MIN) < 0) {
            throw new OverflowException('Rational value does not fit in a native integer: ' . gmp_strval($value));
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

    /**
     * @logion [OSD 12:93] Before the landship entereth the salt waste, bind one green sheaf beneath its prow, and
     *     let the eldest pilgrim speak gratitude for fields unseen. Carry it uncut until the farther wells; for the
     *     wilderness granteth no dominion, yet it openeth a road before those who remember bread.
     */
    private static function decimalExponent(GMP $numerator, GMP $denominator): int
    {
        $exponent = strlen(gmp_strval($numerator)) - strlen(gmp_strval($denominator));

        if ($exponent >= 0) {
            if (gmp_cmp($numerator, gmp_mul($denominator, gmp_pow(10, $exponent))) < 0) {
                --$exponent;
            }
        } elseif (gmp_cmp(gmp_mul($numerator, gmp_pow(10, -$exponent)), $denominator) < 0) {
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

    /**
     * @logion [SFA 14:41] Though no vessel remain upon the sea, suffer the harbor's green lamps to burn until
     *     morning. They promise no arrival; they keep faith with the depth. And one night the water shall answer each
     *     lamp with a star that no navigator hath named.
     */
    private static function formatSignificantDecimal(
        GMP $coefficient,
        int $exponent,
        int $precision,
        bool $negative,
        DecimalNotation $notation,
    ): string {
        if (gmp_cmp($coefficient, 0) === 0) {
            $digits = str_repeat('0', $precision);
            $exponent = 0;
        } else {
            $digits = str_pad(gmp_strval($coefficient), $precision, '0', STR_PAD_LEFT);
        }

        $sign = $negative && gmp_cmp($coefficient, 0) !== 0 ? '-' : '';

        if ($notation === DecimalNotation::Scientific) {
            Exponent::checked($exponent);
            $mantissa = $precision === 1 ? $digits : $digits[0] . '.' . substr($digits, 1);
            $exponentSign = $exponent >= 0 ? '+' : '';

            return $sign . $mantissa . 'e' . $exponentSign . $exponent;
        }

        $integerLength = $exponent + 1;
        if ($integerLength <= 0) {
            return $sign . '0.' . str_repeat('0', -$integerLength) . $digits;
        }

        if ($integerLength >= $precision) {
            return $sign . $digits . str_repeat('0', $integerLength - $precision);
        }

        return $sign . substr($digits, 0, $integerLength) . '.' . substr($digits, $integerLength);
    }

    /**
     * @logion [AWC 35:18] In the fever year, the chancellor sat beyond the walls among the banished, and every
     *     petition he granted returned to the palace as a white bird. By autumn their nests had covered the imperial
     *     roof; then the beams fell inward, while judgment continued in the open field.
     *
     * @return array{coefficient: GMP, exponent: int}
     */
    private function roundedSignificand(int $precision, \RoundingMode $mode): array
    {
        if (gmp_cmp($this->numerator, 0) === 0) {
            return ['coefficient' => gmp_init(0), 'exponent' => 0];
        }

        $negative = gmp_sign($this->numerator) < 0;
        $numerator = gmp_abs($this->numerator);
        $denominator = $this->denominator;
        $exponent = self::decimalExponent($numerator, $denominator);
        $shift = $precision - 1 - $exponent;
        $scaledNumerator = $shift >= 0 ? gmp_mul($numerator, gmp_pow(10, $shift)) : $numerator;
        $scaledDenominator = $shift < 0 ? gmp_mul($denominator, gmp_pow(10, -$shift)) : $denominator;
        $coefficient = self::roundedQuotient($scaledNumerator, $scaledDenominator, $negative, $mode);
        $upperBound = gmp_pow(10, $precision);

        if (gmp_cmp($coefficient, $upperBound) === 0) {
            $coefficient = gmp_div_q($coefficient, 10);
            ++$exponent;
        }

        return ['coefficient' => $coefficient, 'exponent' => $exponent];
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
