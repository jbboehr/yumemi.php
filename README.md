![Iudex Mensurarum Mysticarum『夢見』〜ＹＵＭＥＭＩ〜 — dimensional analysis for PHP](.github/assets/yumemi-banner.png)

# Yumemi

**Static dimensional analysis for PHPStan** on ordinary `int` and `float` values, backed by a **runtime unit engine**
for exact rational conversion and quantity arithmetic.

The PHPStan extension catches incompatible units without requiring runtime wrapper objects. When an application needs
real conversion, the runtime library supplies `Units`, `Quantity`, and exact `Rational` values. Both layers share one
parser, unit catalog, normalization engine, and meaning for expressions such as `meter / second`.

**Status:** the PHPStan extension and runtime library are usable, but Yumemi does not yet have a tagged stable release.

## Installation

Yumemi requires PHP 8.2 or later and the GMP extension. Until the first tagged release, install the development branch:

```shell
composer require jbboehr/yumemi:dev-master
```

When [`phpstan/extension-installer`](https://github.com/phpstan/extension-installer) is installed, Yumemi's primary
PHPStan extension is registered automatically. Otherwise, include it from `phpstan.neon`:

```neon
includes:
    - vendor/jbboehr/yumemi/extension.neon
```

The extension-optional `@yumemi-*` annotation integration is deliberately opt-in. See
[Extension-Optional Annotations](docs/pages/reference/phpstan.md#extension-optional-annotations) for configuration and
tradeoffs.

## Static Analysis

Use `unit()` to brand an ordinary native magnitude. PHPStan then propagates its unit through arithmetic while runtime
behavior remains unchanged:

```php
<?php

require 'vendor/autoload.php';

use function jbboehr\Yumemi\unit;

/** @param unit_float<'meter / second'> $speed */
function storeTelemetrySpeed(float $speed): void {}

$distance = unit(100.0, 'meter');
$elapsed = unit(10.0, 'second');

storeTelemetrySpeed($distance / $elapsed);

//! expects unit_float<'meter / second'>, unit_float<'meter * second'> given
storeTelemetrySpeed($distance * $elapsed);

assert($distance / $elapsed === 10.0);
```

A `//!` comment records part of the PHPStan diagnostic expected on the following line in Yumemi's tested documentation.
It is documentation-test notation, not Yumemi syntax.

`unit_int<'...'>` and `unit_float<'...'>` work in ordinary PHPDoc positions. Yumemi also models runtime objects as
`Quantity<'unit'>`, preserving their units through arithmetic, conversion, and native extraction.

## Runtime Conversion

Use `Units` and `Quantity` when the program must perform a conversion or retain an exact rational magnitude:

```php
<?php

require 'vendor/autoload.php';

use jbboehr\Yumemi\Units;

$length = Units::default()->quantity(1, 'foot')->to('meter');

assert($length->exactDecimalValueIn('meter') === '0.3048');
assert($length->unitToString() === 'meter');
```

Quantity arithmetic distinguishes symbolic reduction from catalog conversion. Addition, subtraction, and comparisons
convert compatible operands exactly; multiplication and division reduce the caller's chosen units. Explicit
`normalize()` and `simplify()` operations control definition substitution.

## Documentation

- [Getting Started](docs/pages/getting-started.md) covers installation and the shortest complete examples.
- [Core Concepts](docs/pages/core-concepts.md) helps choose between branded native values and exact quantities, then
  points each operation to its authoritative reference.
- [Recipes](docs/pages/recipes.md) provides task-oriented examples for common integration and conversion workflows.
- [PHPStan Reference](docs/pages/reference/phpstan.md) defines branded types, operators, conversion helpers, generic
  quantities, configuration, optional annotations, diagnostics, and limitations.
- [Unit Syntax](docs/pages/reference/unit-syntax.md) defines expressions, name resolution, Unicode forms, and errors.
- [Runtime Reference](docs/pages/reference/runtime.md) documents exact conversion, quantity arithmetic, native output,
  dimensions, formatting, and string forms.
- [Built-in and Custom Units](docs/pages/reference/catalog.md) documents UDUNITS2 data, introspection, custom
  registries, and semantic capabilities.

Architecture, implementation status, and deferred work are tracked in the
[planning document](docs/development/planning.md). The broader feature comparison is in
[Pint parity](docs/development/pint-parity.md).

## License

Yumemi is licensed under the **GNU Affero General Public License version 3 with the Romic Exception**:

```text
AGPL-3.0-only WITH romic-exception
```

The Romic Exception permits Yumemi to be linked or combined with other code without subjecting that other code to the
AGPL merely because of the linking or combination. Modifications to the covered project remain subject to the Project
License, including its source-availability requirements for modified versions made available over a computer network.

See [LICENSE.md](LICENSE.md) and [docs/LICENSE_EXCEPTION.md](docs/LICENSE_EXCEPTION.md) for the complete terms. The
generated UDUNITS2 catalog and portions of the parser grammar incorporate material under the UCAR License; see
[docs/UDUNITS-COPYRIGHT](docs/UDUNITS-COPYRIGHT).

Contributions are accepted under the terms in [CONTRIBUTING.md](CONTRIBUTING.md). Unless a contributor elects the CLA
route, each contribution is offered under `AGPL-3.0-only WITH romic-exception OR Apache-2.0`, at each recipient's
option, while the public project incorporates it under the Project License. The Apache-2.0 alternative applies only to
the contributor-authored portions and does not make the project as a whole available under Apache-2.0.

A contributor may instead elect [the CLA](docs/CLA-v1.md), keeping the contribution publicly under the Project License
while granting the [Project Steward](docs/STEWARD.md) the additional rights specified there.

Alternative commercial licenses may be available from the Project Steward. Contact John Boehr at `jbboehr@gmail.com`.
