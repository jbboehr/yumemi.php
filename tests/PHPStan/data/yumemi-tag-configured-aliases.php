<?php

namespace {
    /** @template T */
    class Quantity
    {
    }
}

namespace YumemiConfiguredAliases {
    use jbboehr\Yumemi\Quantity;

    use function PHPStan\Testing\assertType;

    /** @yumemi-param MeterStock $stock */
    function inspectStock(\Quantity $stock): void
    {
        assertType("Quantity<unit_float<'meter'>>", $stock);
    }

    /** @yumemi-param MeasuredDistance $distance */
    function inspectDistance(Quantity $distance): void
    {
        assertType("Quantity<'meter'>", $distance);
    }

    /** @yumemi-param MeterPacket $packet */
    function inspectPacket(array $packet): void
    {
        assertType("array{distance: unit_float<'meter'>}", $packet);
    }

    /** @yumemi-param list<MeterValue> $distances */
    function inspectDistances(array $distances): void
    {
        assertType("list<unit_float<'meter'>>", $distances);
    }

    /**
     * A shape key matching an alias is a field name, not a unit-bearing value.
     *
     * @param array{MeterValue: float} $packet
     * @yumemi-param array{MeterValue: float} $packet
     */
    function inspectPlainPacket(array $packet): void
    {
    }

    /**
     * @param object{MeterValue: float} $packet
     * @yumemi-param object{MeterValue: float} $packet
     */
    function inspectPlainObject(object $packet): void
    {
    }
}
