<?php

use function jbboehr\Yumemi\unit;

/**
 * @param int $length fallback description
 * @yumemi-param unit_int<'meter'> $length
 */
function acceptsMeters(int $length): void
{
}

acceptsMeters(unit(1, 'meter'));
acceptsMeters(1);
acceptsMeters(unit(1, 'foot'));

$consumer = new class {
    /**
     * @param int $length
     * @yumemi-param unit_int<'meter'> $length
     */
    public function accept(int $length): void
    {
    }
};

$consumer->accept(unit(1, 'meter'));
$consumer->accept(1);
