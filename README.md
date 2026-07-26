![Iudex Mensurarum Mysticarum『夢見』〜ＹＵＭＥＭＩ〜 — dimensional analysis for PHP](.github/assets/yumemi-banner.png)

# Yumemi

**Static dimensional analysis for PHPStan** — catch unit mistakes (feet passed where meters are expected, a speed added
to a distance) at analysis time, on ordinary `int`/`float` values, with no runtime wrapper required. Backed by a
**runtime unit engine** for PHP that does exact rational conversion when you need real values.

The same parser, UDUNITS2 catalog, and normalization engine drive both layers — one expression model, one registry, one
meaning for `meter / second` whether PHPStan is reading it or your code is computing with it.

**Status**

- **PHPStan extension** (the headline): usable, not yet a tagged stable release. Implemented — unit-branded native types
  (`unit_int<'…'>` / `unit_float<'…'>`), arithmetic operator inference (`+ - * / ** %`), `unit()` / `unit_to()` helpers,
  invalid-unit-string diagnostics, `Quantity<'…'>` object types with fluent-method inference, and extension-optional
  `@yumemi-param` / `@yumemi-return` annotations.
- **Runtime library:** usable — unit expressions, UDUNITS2 catalog, quantities, exact rational conversion, dimensional
  checks.

Design notes: [docs/planning.md](docs/planning.md). PHPStan design:
[docs/phpstan-extension.md](docs/phpstan-extension.md). Code-quality snapshot:
[docs/grok-review.md](docs/grok-review.md).

## Installation

```text
composer require jbboehr/yumemi
```

With [`phpstan/extension-installer`](https://github.com/phpstan/extension-installer) the analysis rules register
automatically. Otherwise include the bundled config from your `phpstan.neon`:

```neon
includes:
    - vendor/jbboehr/yumemi/extension.neon
```

## Static Dimensional Analysis (PHPStan)

The primary use of Yumemi is to make dimensional mistakes a static error. You annotate ordinary native values with a
unit; PHPStan tracks that unit through arithmetic and flags incompatible uses. Nothing here changes runtime behavior —
the values stay plain `int`/`float`, and the code below runs fine. PHPStan is what rejects it.

Feet are not meters, even though both are `float`:

```php
<?php

require 'vendor/autoload.php';

/** @param unit_float<'meter'> $length */
function recordDistance(float $length): void {}

/** @var unit_float<'foot'> $height */
$height = 6.0;

// PHPStan: Parameter #1 $length of function recordDistance() expects
//   unit_float<'meter'>, unit_float<'international_foot'> given.
//   Unit unit_float<'international_foot'> is not assignable to
//   unit_float<'meter'> (normalized forms differ).
recordDistance($height);
```

Units propagate through arithmetic, so `distance / time` is inferred as a speed and a wrong combination is caught:

```php
<?php

require 'vendor/autoload.php';

/** @param unit_float<'meter / second'> $speed */
function recordSpeed(float $speed): void {}

/** @var unit_float<'meter'> $distance */
$distance = 100.0;
/** @var unit_float<'second'> $elapsed */
$elapsed = 9.58;

// PHPStan infers unit_float<'meter / second'> for the quotient — accepted.
recordSpeed($distance / $elapsed);

// Multiplying instead would infer unit_float<'meter * second'> and be rejected:
//   recordSpeed($distance * $elapsed);
```

Definitional equivalence is understood (`newton` ≡ `kilogram * meter / second^2`, `kilometer` ≡ `1000 * meter`), while a
mere shared dimension is not enough — `foot` is not accepted where `meter` is required. The full matrix of physics
formulas that must pass or fail lives in
[`tests/PHPStan/data/unit-real-world-native.php`](tests/PHPStan/data/unit-real-world-native.php).

Use the `unit()` and `unit_to()` helpers to brand and convert at the boundaries of your code:

```php
<?php

require 'vendor/autoload.php';

use function jbboehr\Yumemi\unit;
use function jbboehr\Yumemi\unit_to;

// unit() brands a native magnitude (and validates the unit string).
// PHPStan infers unit_int<'foot'>; the runtime value is the untouched int.
$height = unit(6, 'foot');

// unit_to() converts across the catalog. PHPStan infers unit_float<'meter'>.
$heightInMeters = unit_to(6, 'foot', 'meter');

assert($height === 6);
assert(abs($heightInMeters - 1.8288) < 1e-9);
```

Code that opts into the runtime `Quantity` object gets the same checking on the object path:
`Quantity<'meter / second'>` is a real PHPDoc type, and the fluent methods (`mul`, `div`, `pow`, `to`, `normalize`, …)
carry the unit through — e.g. `$meters->div($seconds)` is inferred as `Quantity<'meter / second'>`.

### Extension-optional annotations

If you can't (or don't want to) put a Yumemi type in a native PHPDoc position — say, in a library whose consumers may
not have the extension installed — use the vendor-prefixed `@yumemi-param` / `@yumemi-return` tags. They sit alongside a
plain native signature and **degrade gracefully**: without the extension they are unknown tags and simply ignored; with
it, the declared units are checked.

```php
<?php

require 'vendor/autoload.php';

/**
 * The native @param keeps every caller working. The @yumemi-param adds the
 * unit constraint only for analysers that understand it.
 *
 * @param int $length
 *
 * @yumemi-param unit_int<'meter'> $length
 */
function storeLength(int $length): void {}

// A bare int passes (graceful degradation). A branded wrong-unit argument
// — e.g. unit(3, 'foot') — would be reported as a @yumemi-param mismatch.
storeLength(5);
```

## Runtime Unit Conversion (PHP)

When you need actual computed values — not just static guarantees — the runtime library does exact, rational unit
conversion. It is also the engine the PHPStan layer reads from, so its `meter / second` means exactly what the
analyser's does.

The runtime API keeps unit arithmetic and unit conversion separate. Quantity operations reduce the unit expression that
the caller chose, while `to()` and `valueIn()` explicitly convert through the unit catalog.

**String forms:** `Quantity` (and error messages) use display form via `ExprFormatter` (e.g. `meter / second`).
`Expr::toString()` is a structural/debug dump (e.g. `meter * second ^ -1`). Equality uses structure, not either string
form.

**`Units::default()`** returns a shared instance (safe to call repeatedly). Use `new Units($registry)` when you need an
isolated catalog or context.

The PHP examples in this section are executed by the test suite.

```php
<?php

require 'vendor/autoload.php';

use jbboehr\Yumemi\Units;

$units = Units::default();

$length = $units->quantity(1488, 'inch')->to('foot');

assert($length->toString() === '124 * foot');
assert($length->valueIn('inch')->toString() === '1488');
```

Multiplication and division reduce chosen unit syntax, but do not substitute compatible unit definitions.

```php
<?php

require 'vendor/autoload.php';

use jbboehr\Yumemi\Units;

$units = Units::default();

$distance = $units->quantity(2, 'meter / second')->mul($units->quantity(3, 'second'));

assert($distance->toString() === '6 * meter');
assert($distance->unitToString() === 'meter');
```

Compatible dimensions are not implicitly converted during addition or subtraction. Convert explicitly when that is what
you want.

```php
<?php

require 'vendor/autoload.php';

use jbboehr\Yumemi\Units;

$units = Units::default();

$total = $units
    ->quantity(1, 'meter')
    ->add($units->quantity(100, 'centimeter')->to('meter'));

assert($total->toString() === '2 * meter');
assert($total->valueIn('centimeter')->toString() === '200');
```

Without that explicit conversion, addition and subtraction require the same reduced unit syntax.

```php
<?php

require 'vendor/autoload.php';

use jbboehr\Yumemi\Exception\IncompatibleUnitException;
use jbboehr\Yumemi\Units;

$units = Units::default();

try {
    $units->quantity(1, 'meter')->add($units->quantity(100, 'centimeter'));
    assert(false);
} catch (IncompatibleUnitException) {
}
```

You can still ask for converted values from a composed quantity when you need the catalog-aware result.

```php
<?php

require 'vendor/autoload.php';

use jbboehr\Yumemi\Units;

$units = Units::default();

$rate = $units->quantity(2, 'centimeter / second')->div($units->quantity(3, 'foot'));

assert($rate->toString() === '2/3 * centimeter / (foot * second)');
assert($rate->valueIn('1 / second')->toString() === '25/1143');
```

Use `normalize()` when you want to substitute unit definitions without changing the quantity value.

```php
<?php

require 'vendor/autoload.php';

use jbboehr\Yumemi\Units;

$units = Units::default();

$rate = $units->quantity(2, 'centimeter / second')->normalize();

assert($rate->valueToString() === '2');
assert($rate->unitToString() === '1/100 * meter / second');
assert($rate->toString() === '1/50 * meter / second');
assert($rate->valueIn('meter / second')->toString() === '1/50');
```

Use `simplify()` when you want to substitute unit definitions and fold the unit scale factor into the value.

```php
<?php

require 'vendor/autoload.php';

use jbboehr\Yumemi\Units;

$units = Units::default();

$rate = $units->quantity(2, 'centimeter / second')->simplify();

assert($rate->valueToString() === '1/50');
assert($rate->unitToString() === 'meter / second');
assert($rate->toString() === '1/50 * meter / second');
assert($rate->valueIn('centimeter / second')->toString() === '2');
```

## License

This project is licensed under the **GNU Affero General Public License version 3 with the Romic Exception**:

```text
AGPL-3.0-only WITH romic-exception
```

The Romic Exception permits this project to be linked or combined with other code without subjecting that other code to
the AGPL merely because of the linking or combination.

Modifications to the covered project remain subject to the Project License, including its source-availability
requirements for modified versions made available over a computer network.

See [`LICENSE`](LICENSE.md) and [`LICENSE_EXCEPTION`](docs/LICENSE_EXCEPTION.md) for the complete terms.

### Contributions

Contributions are accepted under special contribution terms.

Unless the contributor affirmatively elects the CLA route described in [`CONTRIBUTING.md`](CONTRIBUTING.md), each
contribution is submitted under either of the following licenses, at each recipient’s option:

```text
AGPL-3.0-only WITH romic-exception OR Apache-2.0
```

The public project incorporates accepted contributions under the Project License.

The Apache-2.0 alternative applies only to the contributor-authored portions of a contribution. It does **not** make
this project as a whole available under Apache-2.0.

A contributor who prefers their contribution to remain publicly copyleft-only may instead elect the project’s
Contributor License Agreement directly in the applicable pull request. Under that route, the contribution is publicly
licensed under the Project License while the [Project Steward](docs/STEWARD.md) receives the additional rights specified
in the CLA.

See [`CONTRIBUTING.md`](CONTRIBUTING.md) before submitting a contribution.

### Commercial licensing

Alternative commercial licenses may be available from the Project Steward for users who want to modify or use the
project under different terms.

Contact:

> John Boehr \
> jbboehr@gmail.com
