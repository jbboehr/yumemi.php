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
composer require jbboehr/yumemi:^0.2
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

Yumemi 0.2 changes `Rational` component access and some runtime and PHPStan behavior. Review these patterns before
changing a `^0.1` Composer constraint to `^0.2`, then rerun your application tests and PHPStan.

| In 0.1 code...                                                                                     | Change for 0.2                                                                                                                                                                                                      |
| -------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Reading `$value->numerator` or `$value->denominator`                                               | Call `$value->numerator()` or `$value->denominator()`. The properties are private, and the methods return detached GMP values.                                                                                      |
| Relying on `equals()` to throw for incompatible dimensions or registry contexts                    | Use `compareTo() === 0` if incompatibility should still throw. `equals()` now returns `false` for incompatible operands.                                                                                            |
| Comparing `Quantity` or `PointQuantity` with `==`, `!=`, `<`, `<=`, `>`, `>=`, or `<=>`            | Use `equals()`, the named ordering methods, or `compareTo()`. PHPStan reports `yumemi.nativeQuantityComparison`. Strict `===` and `!==` still test object identity.                                                 |
| Changing the default with `Units::setDefault()` inside a Fiber                                     | Set it during synchronous bootstrap, before scheduling concurrent work. Inside a Fiber, use an explicit `Units` instance for a different registry. Changing the process-wide default there now throws.              |
| Passing a resolved `Expr` to another `Units` instance, or keeping it after its context is released | Obtain the expression through the context that will use it and keep that context alive. Cross-context and expired-context semantic operations throw. Structural equality and formatting remain context-independent. |
| Cloning a `Units` instance                                                                         | Reuse the original instance when values must interact. Construct `new Units($registry)` for an independent context and create its expressions and quantities through it. `Units` can no longer be cloned.           |
| Calling `$destination->difference($origin)`                                                        | Prefer `$destination->differenceFrom($origin)`. The result still represents destination minus origin in the destination's difference scale. `difference()` remains a deprecated compatibility alias.                |

For named point-subtraction arguments, `difference(other: $origin)` becomes `differenceFrom(origin: $origin)`.

Component reads still return exact normalized integers:

```php
<?php

use jbboehr\Yumemi\Number\Rational;

$portion = new Rational(6, 8);
$numerator = $portion->numerator();
$denominator = $portion->denominator();

assert(gmp_strval($numerator) === '3');
assert(gmp_strval($denominator) === '4');
```

Changing an input GMP object or a returned component no longer changes the rational value. Construct a new `Rational`
when you need a different value.

For example, compare values in different compatible units with `equals()`:

```php
<?php

use jbboehr\Yumemi\Units;

$units = Units::default();
$measuredLength = $units->quantity(1, 'meter');
$storedLength = $units->quantity(100, 'centimeter');

assert($measuredLength->equals($storedLength));
```

Recheck these static-analysis boundaries even if your application uses no runtime quantity operators:

- `unit()` preserves both alternatives of an `int|float` input. Native division of branded integers can return an
  integer or a float, matching PHP. Keep both kinds in declarations when either is possible, narrow with `is_int()` or
  `is_float()`, or use `fdiv()` when you deliberately need a float result.
- Quantity/scalar unions retain every possible result unit, and native arithmetic retains explicit unit unions through
  chained operations. Narrow the alternatives before passing a result to a parameter that requires one unit.
- Reordered named arguments and fixed-shape argument unpacking now receive unit checks. Correct newly reported unit
  mismatches or convert compatible values explicitly. Dynamic unpacking and calls through first-class callables still
  have [inference limits](reference/phpstan.md#limitations).
- PHPDoc quantity types now follow namespaces and imports. Import `jbboehr\Yumemi\Quantity` and
  `jbboehr\Yumemi\PointQuantity`, or use their fully qualified names. Renamed imports remain valid. Scalar pseudo-types
  such as `unit_float` stay unqualified. These rules also apply to optional `@yumemi-*` tags.

For example, these two division results have different native kinds:

```php
<?php

use function jbboehr\Yumemi\unit;

$wholeHalf = unit(4, 'meter') / 2; // unit_int<'meter'>
$floatHalf = fdiv(unit(4, 'meter'), 2); // unit_float<'meter'>
```

If you use custom units or snapshot formatted output, review the corrected
[ordering rules](reference/runtime.md#conversion-and-comparison) for negative and zero scales and the
[formatting rules](reference/runtime.md#formatting). Prefixed names may keep a longer spelling when a shorter symbol
would change their meaning. Reciprocal zero-scale expressions now fail instead of producing an undefined unit scale.

Supported native-serialization payloads from tagged 0.1 releases remain readable under their documented registry
constraints. JSON shapes are unchanged. The new `Units::quantityFromJson()` and `pointFromJson()` readers use the
receiving registry's meaning for the stored unit name. See
[Serialization](reference/runtime.md#debugging-json-and-serialization) for custom-context restoration. Existing
`UnexpectedValueException` catches also continue to handle exact-output failures, with
[more specific exception categories](reference/runtime.md#native-numeric-output) available when needed.

If you inspect cross-context multiplication exceptions, their context IDs now appear in ascending order. Other quantity
operations retain receiver-then-argument order. See
[Contexts And Construction](reference/runtime.md#contexts-and-construction).

The optional `ext-yumemi` companion adds native parsing and quantity operator syntax. The method APIs and PHP parser
remain available without it. See [Native Parser Selection](reference/runtime.md#native-parser-selection) for automatic
selection and the `YUMEMI_NATIVE_PARSER` control, and
[Optional Quantity Operators](reference/phpstan.md#optional-quantity-operators) before enabling operator inference.

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
