# Getting Started

Yumemi requires PHP 8.2 or later and the GMP extension.

## Installation

Until the first tagged release, install the development branch with Composer:

```shell
composer require jbboehr/yumemi:dev-master
```

When [`phpstan/extension-installer`](https://github.com/phpstan/extension-installer) is installed, Yumemi's primary
PHPStan extension is registered automatically. Otherwise, include it explicitly:

```neon
includes:
    - vendor/jbboehr/yumemi/extension.neon
```

Most applications should use Yumemi's PHPDoc types directly. Libraries that cannot require Yumemi from every consumer
can instead use the deliberately opt-in
[`@yumemi-*` annotation integration](reference/phpstan.md#extension-optional-annotations).

## Verify Static Analysis

Use `unit()` to brand an ordinary native value at a system boundary. PHPStan then carries the unit through arithmetic
and rejects a deliberately incorrect result:

```php
<?php

require 'vendor/autoload.php';

use function jbboehr\Yumemi\unit;

/** @param unit_float<'meter / second'> $speed */
function saveJourneySpeed(float $speed): void {}

$distance = unit(100.0, 'meter');
$duration = unit(10.0, 'second');
$speed = $distance / $duration;

saveJourneySpeed($speed);
assert($speed === 10.0);

//! expects unit_float<'meter / second'>, unit_float<'meter * second'> given
saveJourneySpeed($distance * $duration);
```

Run the PHPStan command used by your project, or the default executable directly:

```shell
vendor/bin/phpstan analyse
```

PHPStan should accept `$speed` and report the expected unit mismatch for the final call. The `//!` line records the
diagnostic expected by Yumemi's documentation tests; it is an ordinary comment, not a required annotation. Remove the
incorrect call once the extension is working.

The runtime values remain ordinary floats. The additional unit information exists only in PHPStan's type system.

## Runtime Conversion

Use `Units` and `Quantity` when the application must perform a conversion or retain exact rational values:

```php
<?php

require 'vendor/autoload.php';

use jbboehr\Yumemi\Units;

$length = Units::default()->quantity(1, 'mile')->to('kilometer');

assert($length->exactDecimalValueIn('kilometer') === '1.609344');
assert($length->unitToString() === 'kilometer');
```

Continue with [Core Concepts](core-concepts.md), then use the [PHPStan](reference/phpstan.md),
[unit syntax](reference/unit-syntax.md), and [runtime API](reference/runtime.md) references as needed.
