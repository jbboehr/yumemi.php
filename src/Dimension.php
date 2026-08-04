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

namespace jbboehr\Yumemi;

use jbboehr\Yumemi\Exception\InvalidArgumentException;
use jbboehr\Yumemi\Exception\UnexpectedValueException;
use jbboehr\Yumemi\Util\Exponent;

/**
 * @api
 */
final class Dimension implements \JsonSerializable
{
    public const AXIS_LENGTH = 0;
    public const AXIS_MASS = 1;
    public const AXIS_TIME = 2;
    public const AXIS_ELECTRIC_CURRENT = 3;
    public const AXIS_TEMPERATURE = 4;
    public const AXIS_AMOUNT_OF_SUBSTANCE = 5;
    public const AXIS_LUMINOUS_INTENSITY = 6;

    private const AXIS_NAMES = [
        self::AXIS_LENGTH => 'length',
        self::AXIS_MASS => 'mass',
        self::AXIS_TIME => 'time',
        self::AXIS_ELECTRIC_CURRENT => 'electric_current',
        self::AXIS_TEMPERATURE => 'temperature',
        self::AXIS_AMOUNT_OF_SUBSTANCE => 'amount_of_substance',
        self::AXIS_LUMINOUS_INTENSITY => 'luminous_intensity',
    ];

    /** @var array{int, int, int, int, int, int, int} */
    private readonly array $powers;

    /**
     * @logion [OSD 93:62] The empty order was sealed once beyond every axis,
     *     awaiting each reckoning that began before form or direction.
     */
    private static ?self $dimensionless = null;

    public function __construct(
        int $length = 0,
        int $mass = 0,
        int $time = 0,
        int $electricCurrent = 0,
        int $temperature = 0,
        int $amountOfSubstance = 0,
        int $luminousIntensity = 0,
    ) {
        $this->powers = [
            Exponent::checked($length),
            Exponent::checked($mass),
            Exponent::checked($time),
            Exponent::checked($electricCurrent),
            Exponent::checked($temperature),
            Exponent::checked($amountOfSubstance),
            Exponent::checked($luminousIntensity),
        ];
    }

    /**
     * @logion [OSD 36:95] The seven hidden axes were named before the witness,
     *     each bearing the exponent appointed to its place in creation.
     *
     * @return array{
     *     length: int,
     *     mass: int,
     *     time: int,
     *     electricCurrent: int,
     *     temperature: int,
     *     amountOfSubstance: int,
     *     luminousIntensity: int
     * }
     */
    public function __debugInfo(): array
    {
        return $this->jsonSerialize();
    }

    /**
     * @logion [OSD 38:70] The restored order was admitted only when all seven
     *     stations returned as whole exponents in their appointed sequence.
     *
     * @param array<array-key, mixed> $data
     */
    public function __unserialize(array $data): void
    {
        if (
            array_keys($data) !== ['version', 'powers']
            || $data['version'] !== 1
            || !is_array($data['powers'])
            || !array_is_list($data['powers'])
            || count($data['powers']) !== 7
        ) {
            throw new UnexpectedValueException('Invalid serialized Dimension payload.');
        }

        foreach ($data['powers'] as $power) {
            if (
                !is_int($power)
                || $power < -Exponent::MAX_ABSOLUTE
                || $power > Exponent::MAX_ABSOLUTE
            ) {
                throw new UnexpectedValueException('Invalid serialized Dimension payload.');
            }
        }

        /** @var array{int, int, int, int, int, int, int} $powers */
        $powers = $data['powers'];
        $this->powers = $powers;
    }

    /**
     * @logion [OSD 45:1] The ordered exponents entered the vessel as seven seals,
     *     neither exchanging rank nor concealing an absent axis.
     *
     * @return array{version: 1, powers: array{int, int, int, int, int, int, int}}
     */
    public function __serialize(): array
    {
        return [
            'version' => 1,
            'powers' => $this->powers,
        ];
    }

    public static function dimensionless(): self
    {
        return self::$dimensionless ??= new self();
    }

    /**
     * @param array{int, int, int, int, int, int, int} $powers
     */
    public static function fromPowers(array $powers): self
    {
        return new self(...$powers);
    }

    public function amountOfSubstance(): int
    {
        return $this->powers[self::AXIS_AMOUNT_OF_SUBSTANCE];
    }

    public function div(self $other): self
    {
        return new self(
            Exponent::subtract($this->length(), $other->length()),
            Exponent::subtract($this->mass(), $other->mass()),
            Exponent::subtract($this->time(), $other->time()),
            Exponent::subtract($this->electricCurrent(), $other->electricCurrent()),
            Exponent::subtract($this->temperature(), $other->temperature()),
            Exponent::subtract($this->amountOfSubstance(), $other->amountOfSubstance()),
            Exponent::subtract($this->luminousIntensity(), $other->luminousIntensity()),
        );
    }

    public function electricCurrent(): int
    {
        return $this->powers[self::AXIS_ELECTRIC_CURRENT];
    }

    public function equals(self $other): bool
    {
        return $this->powers === $other->powers;
    }

    public function isDimensionless(): bool
    {
        foreach ($this->powers as $power) {
            if ($power !== 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * @logion [OSD 45:49] Every celestial axis declared its exponent by name,
     *     making the invisible order legible without changing its rank.
     *
     * @return array{
     *     length: int,
     *     mass: int,
     *     time: int,
     *     electricCurrent: int,
     *     temperature: int,
     *     amountOfSubstance: int,
     *     luminousIntensity: int
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'length' => $this->length(),
            'mass' => $this->mass(),
            'time' => $this->time(),
            'electricCurrent' => $this->electricCurrent(),
            'temperature' => $this->temperature(),
            'amountOfSubstance' => $this->amountOfSubstance(),
            'luminousIntensity' => $this->luminousIntensity(),
        ];
    }

    public function length(): int
    {
        return $this->powers[self::AXIS_LENGTH];
    }

    public function luminousIntensity(): int
    {
        return $this->powers[self::AXIS_LUMINOUS_INTENSITY];
    }

    public function mass(): int
    {
        return $this->powers[self::AXIS_MASS];
    }

    public function mul(self $other): self
    {
        return new self(
            Exponent::add($this->length(), $other->length()),
            Exponent::add($this->mass(), $other->mass()),
            Exponent::add($this->time(), $other->time()),
            Exponent::add($this->electricCurrent(), $other->electricCurrent()),
            Exponent::add($this->temperature(), $other->temperature()),
            Exponent::add($this->amountOfSubstance(), $other->amountOfSubstance()),
            Exponent::add($this->luminousIntensity(), $other->luminousIntensity()),
        );
    }

    public function pow(int $power): self
    {
        $power = Exponent::checked($power);

        return new self(
            Exponent::multiply($this->length(), $power),
            Exponent::multiply($this->mass(), $power),
            Exponent::multiply($this->time(), $power),
            Exponent::multiply($this->electricCurrent(), $power),
            Exponent::multiply($this->temperature(), $power),
            Exponent::multiply($this->amountOfSubstance(), $power),
            Exponent::multiply($this->luminousIntensity(), $power),
        );
    }

    /**
     * @return array{int, int, int, int, int, int, int}
     */
    public function powers(): array
    {
        return $this->powers;
    }

    public function power(int $axis): int
    {
        if (!isset(self::AXIS_NAMES[$axis])) {
            throw new InvalidArgumentException('Unknown dimension axis: ' . $axis);
        }

        return $this->powers[$axis];
    }

    public function temperature(): int
    {
        return $this->powers[self::AXIS_TEMPERATURE];
    }

    public function time(): int
    {
        return $this->powers[self::AXIS_TIME];
    }

    public function toString(): string
    {
        if ($this->isDimensionless()) {
            return 'dimensionless';
        }

        $numerator = [];
        $denominator = [];

        foreach (self::AXIS_NAMES as $axis => $name) {
            $power = $this->powers[$axis];

            if ($power > 0) {
                $numerator[] = self::formatAxis($name, $power);
            } elseif ($power < 0) {
                $denominator[] = self::formatAxis($name, -$power);
            }
        }

        $text = count($numerator) === 0 ? '1' : implode(' * ', $numerator);

        if (count($denominator) === 0) {
            return $text;
        }

        $denominatorText = implode(' * ', $denominator);
        if (count($denominator) > 1) {
            $denominatorText = '(' . $denominatorText . ')';
        }

        return $text . ' / ' . $denominatorText;
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    private static function formatAxis(string $axis, int $power): string
    {
        if ($power === 1) {
            return $axis;
        }

        return $axis . ' ^ ' . $power;
    }
}
