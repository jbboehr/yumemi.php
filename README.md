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
  `@yumemi-param` / `@yumemi-return` / `@yumemi-var` annotations through an opt-in parser integration.
- **Runtime library:** usable — unit expressions, UDUNITS2 catalog, quantities, exact rational conversion, dimensional
  checks.

Architecture, implementation status, and roadmap: [docs/planning.md](docs/planning.md). Broader feature comparison:
[docs/pint-parity.md](docs/pint-parity.md).

## Installation

```text
composer require jbboehr/yumemi:dev-master
```

With [`phpstan/extension-installer`](https://github.com/phpstan/extension-installer) the analysis rules register
automatically. Otherwise include the bundled config from your `phpstan.neon`:

```neon
includes:
    - vendor/jbboehr/yumemi/extension.neon
```

Promotion of the extension-optional `@yumemi-*` tags is deliberately not registered automatically. If you need that
feature, include its second config after the main extension (which may already be loaded by the extension installer):

```neon
includes:
    - vendor/jbboehr/yumemi/yumemi-tags.neon
```

Without the extension installer, include both files in that order.

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

// Passing feet where meters are required is a static error:
//! expects unit_float<'meter'>, unit_float<'international_foot'> given
recordDistance($height);
```

(The `//!` line is the exact diagnostic Yumemi reports there — the test suite runs every example in this section through
PHPStan and checks each `//!` line against the real output, so these can't silently drift.)

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

// distance / time is inferred as unit_float<'meter / second'> — accepted.
recordSpeed($distance / $elapsed);

// distance * time is unit_float<'meter * second'> — the wrong dimension, rejected:
//! expects unit_float<'meter / second'>, unit_float<'meter * second'> given
recordSpeed($distance * $elapsed);
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
assert($heightInMeters > 1.82 && $heightInMeters < 1.83);
```

Code that opts into the runtime `Quantity` object gets the same checking on the object path:
`Quantity<'meter / second'>` is a real PHPDoc type, and the fluent methods (`mul`, `div`, `pow`, `to`, `normalize`,
`simplify`, …) carry the unit through — e.g. `$meters->div($seconds)` is inferred as `Quantity<'meter / second'>`.
Conversion methods also check their target dimension. `intValueIn('foot')` and `exactIntValueIn('foot')` return
`unit_int<'foot'>`, bridging an exact runtime quantity back to a statically branded native integer. In the other
direction, passing a `unit_int<'foot'>` magnitude to `Units::quantity(..., 'meter')` is rejected: `quantity()` labels an
existing magnitude and does not implicitly convert it. Finite literal-string target unions are preserved, so a target
typed as `'meter'|'foot'` produces the corresponding union of branded results. An explicit known target also brands
`to()` and integer-extraction results from a plain, unbranded `Quantity`; PHPStan cannot check the source dimension in
that case, but it can still represent the requested result unit.

### Custom PHPStan registries

PHPStan uses the default UDUNITS2 catalog unless `parameters.yumemi.registryFactory` names a class implementing
`UnitRegistryFactory`. The factory returns the complete registry used by every Yumemi PHPStan integration:

```php
<?php

namespace App\PHPStan;

use jbboehr\Yumemi\PHPStan\UnitRegistryFactory;
use jbboehr\Yumemi\Registry\UnitRegistry;
use jbboehr\Yumemi\Registry\UnitRegistryBuilder;

final class YumemiRegistryFactory implements UnitRegistryFactory
{
    public static function create(): UnitRegistry
    {
        return UnitRegistryBuilder::default()
            ->define('widget = 12 * meter')
            ->alias('widgets', 'widget')
            ->build();
    }
}
```

```neon
parameters:
    yumemi:
        registryFactory: App\PHPStan\YumemiRegistryFactory
```

Use `UnitRegistryBuilder::default()` to extend or override UDUNITS2, or `UnitRegistryBuilder::empty()` for an isolated
catalog. PHPStan's result cache is invalidated automatically when the returned registry contents change. This setting
controls static analysis only; application code should construct its runtime `Units` context from the same registry
factory when it uses those custom units at runtime. The configured registry is authoritative for a PHPStan run: unknown
constant unit strings are errors, while genuinely dynamic strings fall back to the native return type. If an application
deliberately uses multiple incompatible runtime registries, PHPStan cannot associate each `Units` or `Quantity` instance
with a separate catalog; use one shared catalog for statically checked code.

### Extension-optional annotations

If you can't (or don't want to) put a Yumemi type in a native PHPDoc position — say, in a library whose consumers may
not have the extension installed — use `@yumemi-param`, `@yumemi-return`, or `@yumemi-var`. Enable their parser
integration explicitly:

```neon
includes:
    - vendor/jbboehr/yumemi/yumemi-tags.neon
```

Without that opt-in config they are unknown tags and the ordinary PHPDoc/native types remain in force. With it, each
Yumemi tag is promoted to PHPStan's native PHPDoc surface, so parameters, function and method returns, properties, and
local variables all use PHPStan's normal type propagation and diagnostics. The feature is off by default because it
replaces internal PHPStan parser services and can conflict with another extension doing the same thing. It is intended
mainly for libraries that embed optional Yumemi support in their own source; application code should normally use Yumemi
types directly, while integrations for libraries you do not control should normally use ordinary PHPStan stubs.

When an ordinary `@param`, `@return`, or `@var` (or its `@phpstan-*` form) exists, the Yumemi type must be its exact
structural transform: every `unit_int<'...'>` erases to `int`, every `unit_float<'...'>` to `float`, and every
`Quantity<'...'>` to `Quantity`, including inside nullable, union, intersection, and generic types. Union/intersection
order and nullable spelling do not matter. `@phpstan-*` takes priority over the ordinary tag. A mismatch leaves the
fallback unchanged and reports an error at the declaration.

```php
<?php

require 'vendor/autoload.php';

use function jbboehr\Yumemi\unit;

/**
 * The ordinary @param is used without tag promotion. With promotion enabled, the
 * exact-transform tag replaces its type while retaining this description.
 *
 * @param int $length
 *
 * @yumemi-param unit_int<'meter'> $length
 */
function storeLength(int $length): void {}

// With tag promotion enabled, PHPStan's ordinary argument checking rejects a bare int:
//! expects unit_int<'meter'>, int given
storeLength(5);

// A branded wrong-unit argument is reported:
//! expects unit_int<'meter'>, unit_int<'international_foot'> given
storeLength(unit(3, 'foot'));
```

Without `yumemi-tags.neon`, both calls above are checked against the ordinary `int` fallback, and `storeLength(5)` is
valid.

## Runtime Unit Conversion (PHP)

When you need actual computed values — not just static guarantees — the runtime library does exact, rational unit
conversion. It is also the engine the PHPStan layer reads from, so its `meter / second` means exactly what the
analyser's does.

The runtime API keeps unit arithmetic and unit conversion separate. Quantity operations reduce the unit expression that
the caller chose, while `to()` and `valueIn()` explicitly convert through the unit catalog.

**String forms:** `Quantity` (and error messages) use display form via `ExprFormatter` (e.g. `meter / second`).
`Expr::toString()` is a structural/debug dump (e.g. `meter * second ^ -1`). Equality uses structure, not either string
form. The default display format preserves unit spellings and uses parser-compatible ASCII, so existing `toString()` and
`unitToString()` output remains stable.

Malformed syntax throws `Parser\ParseException` with an optional `Parser\SourceSpan`. Spans are zero-based, half-open
byte ranges in the decoded unit expression; exception messages render a one-based expression-local line and column with
a bounded caret excerpt. PHPStan preserves the excerpt while anchoring the diagnostic to the containing PHP or PHPDoc
line. Unknown unit names and parsed-but-unsupported semantics are later validation failures and currently have no span.

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

`valueIn()` keeps the converted magnitude exact as a `Rational`. Native output is explicit: decimal output uses a fixed
number of places and a required PHP 8.4 `RoundingMode` (polyfilled on PHP 8.2 and 8.3), exact decimal output rejects
non-terminating representations, and float output rejects overflow or nonzero values that underflow to zero.

```php
<?php

require 'vendor/autoload.php';

use jbboehr\Yumemi\Units;

$length = Units::default()->quantity(1, 'foot');

assert($length->exactDecimalValueIn('meter') === '0.3048');
assert($length->decimalValueIn('meter', 2, \RoundingMode::HalfEven) === '0.30');
assert($length->floatValueIn('meter') === 0.3048);
```

Catalog introspection preserves the difference between canonical names, aliases, symbols, and plurals without
normalizing the unit into an equivalent base-unit expression:

```php
<?php

require 'vendor/autoload.php';

use jbboehr\Yumemi\Catalog\CatalogNameKind;
use jbboehr\Yumemi\Units;

$units = Units::default();
$meter = $units->describe('m');
$kilo = $units->describePrefix('k');

assert($meter !== null);
assert($meter->canonicalName === 'meter');
assert($meter->matchedAs === CatalogNameKind::Symbol);
assert(in_array('metre', $meter->aliases, true));

assert($kilo !== null);
assert($kilo->canonicalName === 'kilo');
assert($kilo->definitionExpression === '1e3');
```

`describe()` and `describePrefix()` perform exact catalog lookup. They do not parse unit expressions, substitute unit
definitions, or synthesize descriptions for dynamically prefixed names.

Formatting policies can canonicalize aliases and generated plurals, select catalog symbols, use Unicode typography, and
control dimensionless output. Formatting does not normalize or substitute unit definitions:

```php
<?php

require 'vendor/autoload.php';

use jbboehr\Yumemi\Formatter\DivisionStyle;
use jbboehr\Yumemi\Formatter\FormatOptions;
use jbboehr\Yumemi\Formatter\Typography;
use jbboehr\Yumemi\Formatter\UnitNameStyle;
use jbboehr\Yumemi\Units;

$units = Units::default();
$options = FormatOptions::create()
    ->withUnitNameStyle(UnitNameStyle::Symbol)
    ->withTypography(Typography::Unicode)
    ->withDivisionStyle(DivisionStyle::NegativePowers);

assert($units->format('kilometers / second^2', $options) === 'km · s⁻²');
assert($units->quantity(3, 'kilometers / second^2')->formatUnit($options) === 'km · s⁻²');
```

`UnitNameStyle::Preserve` keeps each spelling supplied by the caller. `Canonical` resolves aliases, plurals, and one
dynamic prefix to canonical names. `Symbol` selects the shortest deterministic catalog symbol; ASCII typography falls
back to a canonical name when a unit has only Unicode symbols, while Unicode typography may emit symbols such as `Ω`.
Unknown expression leaves are preserved.

Unicode typography emits `·` and superscript integer powers while retaining fraction layout, for example `m · kg / s²`.
The parser accepts `·`, superscript digits, and optional superscript `⁺` or `⁻`, so ordinary Unicode formatter output
can be parsed again. `DimensionlessStyle::Word` and `DimensionlessStyle::Empty` are presentation-only; the default
`DimensionlessStyle::One` remains round-trippable.

`DivisionStyle::NegativePowers` renders denominator units as negative powers while leaving exact rational coefficients
alone: `1/2 * meter / second` becomes `1/2 * meter * second ^ -1`, or `1/2 · m · s⁻¹` with Unicode symbols. The default
`DivisionStyle::Fraction` retains fraction layout. `FormatOptions` supports both named constructor arguments and
immutable `create()->with...()` chains.

Use `Units::formatter($options)` when the same options will format several `Expr` values. `Units::format()` parses a
string symbolically before rendering it. An `Expr` previously returned by `Units::parse()` has already been resolved, so
its formatter output reflects that resolved expression rather than recovering the original source spelling.

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

Addition and subtraction convert a compatible right operand into the left operand's unit. Conversion remains exact, and
the result preserves the left operand's symbolic unit.

```php
<?php

require 'vendor/autoload.php';

use jbboehr\Yumemi\Units;

$units = Units::default();

$total = $units
    ->quantity(1, 'meter')
    ->add($units->quantity(100, 'centimeter'));

assert($total->toString() === '2 * meter');
assert($total->valueIn('centimeter')->toString() === '200');
```

Use `addWithSameUnit()` / `subWithSameUnit()` when conversion should be rejected. These methods accept only
definitionally equivalent units with the same normalized scale.

```php
<?php

require 'vendor/autoload.php';

use jbboehr\Yumemi\Exception\IncompatibleUnitException;
use jbboehr\Yumemi\Units;

$units = Units::default();

try {
    $units->quantity(1, 'meter')->addWithSameUnit($units->quantity(100, 'centimeter'));
    assert(false);
} catch (IncompatibleUnitException) {
}
```

Comparisons also convert the right operand into the left unit and remain exact. Use the explicit methods rather than
PHP's object comparison operators, which do not implement Yumemi's unit semantics.

```php
<?php

require 'vendor/autoload.php';

use jbboehr\Yumemi\Units;

$units = Units::default();
$meter = $units->quantity(1, 'meter');

assert($meter->equals($units->quantity(100, 'centimeter')));
assert($meter->greaterThan($units->quantity(3, 'foot')));
assert($meter->lessThan($units->quantity(4, 'foot')));
assert($meter->compareTo($units->quantity(1000, 'millimeter')) === 0);
```

All comparison methods require compatible dimensions and throw `IncompatibleUnitException` for comparisons such as
`meter` against `second`.

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
