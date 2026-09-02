# Getting Started

<figure class="logion" data-logion="OSD 34:72">
<div class="logion-text">
<blockquote>
<p>Upon the day of ashes, draw a narrow door of salt upon the chapel floor. Let the penitent cross it barefoot, naming the restitution already made, and suffer none to sweep behind them. At evening, if the door remaineth whole, their sorrow lacked weight; if their feet have broken it, admit them to the choir, and let the first hymn be for those they harmed.</p>
</blockquote>
<p class="logion-citation">— <cite>Ordinances of the Synthetic Dawn 34:72</cite></p>
</div>
<img src="images/logia/OSD-34_72.webp" alt="A barefoot penitent crossing a broken salt threshold in a cobalt-lit chapel" width="960" height="540" loading="eager" fetchpriority="high">
</figure>

Yumemi requires PHP 8.2 or later and the GMP extension, which provides the arbitrary-precision integers used for exact
rational arithmetic and conversion.

## Installation

Most applications call Yumemi at runtime and also use its PHPStan extension. Install it as a normal application
dependency:

```shell
composer require jbboehr/yumemi:^0.1
```

Yumemi does not install PHPStan automatically in consuming projects. Install PHPStan and the extension installer as
development dependencies to enable automatic registration:

```shell
composer require --dev phpstan/phpstan:^2.2.5 phpstan/extension-installer
```

Projects that do not use [`phpstan/extension-installer`](https://github.com/phpstan/extension-installer) should install
PHPStan by itself and include Yumemi explicitly from `phpstan.neon`:

```neon
includes:
    - vendor/jbboehr/yumemi/extension.neon
```

Keep `jbboehr/yumemi` as a normal dependency whenever application code calls functions such as `unit()` or `unit_to()`,
or uses runtime classes such as `Units` and `Quantity`. A project using Yumemi only during static analysis, with no
runtime calls or classes, may install it as a development dependency instead.

## Upgrade From 0.1

Yumemi 0.2 keeps the supported 0.1 declarations source-compatible and can still read the documented serialized and JSON
data written by 0.1. It changes some runtime and PHPStan behavior. Check these patterns before changing the Composer
constraint from `^0.1`:

| In 0.1 code...                                                                   | Change for 0.2                                                                                                                                                                                                |
| -------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Relying on `equals()` to throw for incompatible units                            | Use `compareTo() === 0` if incompatibility should still throw. `equals()` now returns `false` for incompatible operands.                                                                                      |
| Comparing `Quantity` or `PointQuantity` with `==`, `!=`, `<`, `<=`, `>`, or `>=` | Use `equals()` or the named ordering methods. PHPStan now reports `yumemi.nativeQuantityComparison`. Strict identity remains available through `===` and `!==`.                                               |
| Changing the default with `Units::setDefault()` inside a Fiber                   | Set the default during synchronous bootstrap. To use another registry inside a Fiber, keep its `Units` instance and call methods on it. Changing the default from a Fiber now throws.                         |
| Calling `$destination->difference($origin)`                                      | Prefer the direction-explicit `$destination->differenceFrom($origin)`. `difference()` remains as a deprecated compatibility alias.                                                                            |
| Passing a resolved `Expr` to a semantic method on another `Units` instance       | Obtain or parse the expression through the `Units` instance that will use it. Cross-context and expired-context semantic operations now throw. Structural equality and formatting still work across contexts. |

Values serialized by PHP from tagged 0.1 releases remain readable. The documented JSON shapes have not changed, and
`Units::quantityFromJson()` and `pointFromJson()` provide typed restoration through the receiving registry. The optional
`ext-yumemi` companion provides native parsing and operator syntax. The method APIs and generated PHP parser work
without it.

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

// @akashi-phpstan-error argument.type: unit_float<'meter / second'>, 1000.0&unit_float<'meter * second'> given
saveJourneySpeed($distance * $duration);
```

Run the PHPStan command used by your project, or the default executable directly:

```shell
vendor/bin/phpstan analyse
```

PHPStan should accept `$speed` and report the expected unit mismatch for the final call. The `@akashi-phpstan-error`
line records the diagnostic identifier and a distinctive fragment of the expected message; it is an ordinary comment,
not a required annotation. Remove the incorrect call once the extension is working.

If PHPStan instead reports unknown `unit_int` or `unit_float` PHPDoc types, the extension is not registered. Install
`phpstan/extension-installer` or add Yumemi's `extension.neon` include explicitly.

If the deliberately incorrect call produces no diagnostic, confirm that the example file is under one of the configured
`paths`, that the invalid call remains in the file, and that the command is loading the `phpstan.neon` where Yumemi is
registered.

The runtime values remain ordinary floats. The additional unit information exists only in PHPStan's type system.

Most applications should use Yumemi's PHPDoc types directly. Libraries that cannot require Yumemi from every consumer
can instead use the deliberately opt-in
[`@yumemi-*` annotation integration](reference/phpstan.md#extension-optional-annotations).

## Runtime Conversion

The runtime unit engine can be used independently of PHPStan brands and extension registration. Use `Units` and
`Quantity` when the application must perform a conversion or retain exact rational values:

```php
<?php

require 'vendor/autoload.php';

use jbboehr\Yumemi\Units;

$length = Units::default()->quantity(1, 'mile')->to('kilometer');

assert($length->exactDecimalValueIn('kilometer') === '1.609344');
assert($length->unitToString() === 'kilometer');
```

For more runtime-only examples, see [Preserve Exact Conversion](recipes.md#preserve-exact-conversion) and
[Convert Temperatures](recipes.md#convert-temperatures). Continue with [Core Concepts](core-concepts.md), then use the
[PHPStan](reference/phpstan.md), [unit syntax](reference/unit-syntax.md), and [runtime API](reference/runtime.md)
references as needed.
