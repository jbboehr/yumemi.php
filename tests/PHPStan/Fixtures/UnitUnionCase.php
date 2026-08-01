<?php

declare(strict_types=1);

namespace jbboehr\Yumemi\Tests\PHPStan\Fixtures;

use function jbboehr\Yumemi\unit;

final class UnitUnionCase
{
    /** @param \DateTimeInterface|\DateInterval|unit_int<'second'>|null $ttl */
    public function acceptTtl(\DateTimeInterface|\DateInterval|int|null $ttl): void
    {
    }
}

$case = new UnitUnionCase();
$case->acceptTtl(30);
$case->acceptTtl(unit(2, 'minute'));
