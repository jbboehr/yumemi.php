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

The optional `@yumemi-param`, `@yumemi-return`, and `@yumemi-var` integration replaces internal PHPStan parser services
and is therefore deliberately opt-in:

```neon
includes:
    - vendor/jbboehr/yumemi/extension.neon
    - vendor/jbboehr/yumemi/yumemi-tags.neon
```

## Static Analysis

Use `unit()` to brand an ordinary native value at a system boundary. PHPStan then carries the unit through arithmetic:

```php
<?php

require 'vendor/autoload.php';

use function jbboehr\Yumemi\unit;

/** @param unit_float<'meter / second'> $speed */
function recordGettingStartedSpeed(float $speed): void {}

$distance = unit(100.0, 'meter');
$duration = unit(10.0, 'second');
$speed = $distance / $duration;

recordGettingStartedSpeed($speed);
assert($speed === 10.0);
```

The runtime values remain ordinary floats. The additional unit information exists in PHPStan's type system.

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

Continue with the [PHPStan](reference/phpstan.md), [unit syntax](reference/unit-syntax.md), and
[runtime API](reference/runtime.md) references.
