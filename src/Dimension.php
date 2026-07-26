<?php

namespace jbboehr\Yumemi;

final class Dimension
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
            $length,
            $mass,
            $time,
            $electricCurrent,
            $temperature,
            $amountOfSubstance,
            $luminousIntensity,
        ];
    }

    public static function dimensionless(): self
    {
        return new self();
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
            $this->length() - $other->length(),
            $this->mass() - $other->mass(),
            $this->time() - $other->time(),
            $this->electricCurrent() - $other->electricCurrent(),
            $this->temperature() - $other->temperature(),
            $this->amountOfSubstance() - $other->amountOfSubstance(),
            $this->luminousIntensity() - $other->luminousIntensity(),
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
            $this->length() + $other->length(),
            $this->mass() + $other->mass(),
            $this->time() + $other->time(),
            $this->electricCurrent() + $other->electricCurrent(),
            $this->temperature() + $other->temperature(),
            $this->amountOfSubstance() + $other->amountOfSubstance(),
            $this->luminousIntensity() + $other->luminousIntensity(),
        );
    }

    public function pow(int $power): self
    {
        return new self(
            $this->length() * $power,
            $this->mass() * $power,
            $this->time() * $power,
            $this->electricCurrent() * $power,
            $this->temperature() * $power,
            $this->amountOfSubstance() * $power,
            $this->luminousIntensity() * $power,
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
        if (!array_key_exists($axis, self::AXIS_NAMES)) {
            throw new \InvalidArgumentException('Unknown dimension axis: ' . $axis);
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
