# Getting Started

Yumemi requires PHP 8.2 or later and the GMP extension, which provides the arbitrary-precision integers used for exact
rational arithmetic and conversion.

## Installation

Yumemi and PHPStan have different Composer roles. The examples in this guide call Yumemi at runtime, so install the
development branch as a normal application dependency until the first tagged release:

```shell
composer require jbboehr/yumemi:dev-master
```

Applications using the PHPStan extension must install PHPStan as a development dependency; Yumemi does not install it
automatically in consuming projects:

```shell
composer require --dev phpstan/phpstan:^2.1
```

For automatic extension registration, also install
[`phpstan/extension-installer`](https://github.com/phpstan/extension-installer) as a development dependency:

```shell
composer require --dev phpstan/extension-installer
```

Without the extension installer, include Yumemi explicitly from `phpstan.neon`:

```neon
includes:
    - vendor/jbboehr/yumemi/extension.neon
```

Keep `jbboehr/yumemi` as a normal dependency whenever application code calls functions such as `unit()` or `unit_to()`,
or uses runtime classes such as `Units` and `Quantity`. A project using Yumemi only during static analysis, with no
runtime calls or classes, may install it as a development dependency instead.

Most applications should use Yumemi's PHPDoc types directly. Libraries that cannot require Yumemi from every consumer
can instead use the deliberately opt-in
[`@yumemi-*` annotation integration](reference/phpstan.md#extension-optional-annotations).

## Verify Static Analysis

Configure at least one source path for PHPStan. For an application whose PHP code lives under `src/`, a minimal
`phpstan.neon` is:

```neon
parameters:
    level: 8
    paths:
        - src
```

When automatic extension registration is unavailable, add the `includes` entry shown in [Installation](#installation) to
the same file.

Use `unit()` to brand an ordinary native value at a system boundary. PHPStan then carries the unit through arithmetic
and rejects a deliberately incorrect result. Place this example under a configured path, such as `src/YumemiCheck.php`:

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

If PHPStan instead reports unknown `unit_int` or `unit_float` PHPDoc types, the extension is not registered. Install
`phpstan/extension-installer` or add Yumemi's `extension.neon` include explicitly.

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
