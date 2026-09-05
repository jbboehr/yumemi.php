<?php

declare(strict_types=1);

use function jbboehr\Yumemi\unit;

function formatParcelWeight(int|float $value): string
{
    $weight = unit($value, 'kilogram');

    if (is_int($weight)) {
        return sprintf('%d kg', $weight);
    }

    return sprintf('%.1f kg', $weight);
}

assert(formatParcelWeight(3) === '3 kg');
assert(formatParcelWeight(3.5) === '3.5 kg');
