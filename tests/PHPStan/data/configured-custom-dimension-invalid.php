<?php

declare(strict_types=1);

namespace jbboehr\Yumemi\Tests\PHPStan\Data\ConfiguredCustomDimensionInvalid;

use function jbboehr\Yumemi\unit;

/** @param unit_float<'USD'> $amount */
function acceptDollars(float $amount): void
{
}

$euros = unit(1, 'EUR');

acceptDollars($euros);

function invalidCurrencySum(): mixed
{
    return unit(1, 'USD') + unit(1, 'EUR');
}
