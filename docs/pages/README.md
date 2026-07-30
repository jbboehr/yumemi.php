{{#title Yumemi - Static dimensional analysis for PHPStan}}

![Iudex Mensurarum Mysticarum Yumemi](images/yumemi-banner.png)

# Yumemi

Yumemi provides static dimensional analysis for PHPStan and exact runtime unit conversion for PHP.

The PHPStan extension tracks units on ordinary `int` and `float` values. It can reject incompatible arguments and
arithmetic without requiring runtime wrapper objects. The runtime library uses the same parser, unit catalog, and
normalization engine for quantity arithmetic and conversion.

## Start Here

```php
<?php

use function jbboehr\Yumemi\unit;

/** @param unit_float<'meter'> $height */
function setDoorHeight(float $height): void {}

//! expects unit_float<'meter'>, unit_float<'international_foot'> given
setDoorHeight(unit(6.0, 'foot'));
```

PHPStan reports the unit mismatch while PHP still receives an ordinary `float`. In tested examples, a `//!` comment
records part of the diagnostic expected on the following line. It is documentation-test notation, not Yumemi syntax.

- **I want PHPStan to catch unit mistakes in native numbers.** Start with
  [Static Analysis](getting-started.md#verify-static-analysis).
- **I need exact runtime conversion and quantity arithmetic.** Start with
  [Runtime Conversion](getting-started.md#runtime-conversion).

A **branded native value** is still an ordinary PHP `int` or `float`. Types such as `unit_float<'meter'>` add a unit
only inside PHPStan; they do not create runtime wrappers.

## Browse Documentation

- [Getting Started](getting-started.md) provides a complete installation and verification path.
- [Core Concepts](core-concepts.md) helps choose between branded native values and exact quantities.
- [Recipes](recipes.md) shows common integration, conversion, custom-unit, and display tasks.
- [PHPStan](reference/phpstan.md) defines branded native types, operator inference, helpers, generic quantities,
  configuration, and diagnostics.
- [Unit Syntax](reference/unit-syntax.md) defines the expression language shared by PHPStan and the runtime.
- [Runtime API](reference/runtime.md) documents quantities, conversion, numeric output, dimensions, and formatting.
- [Built-in and Custom Units](reference/catalog.md) documents UDUNITS2 behavior, custom registries, and introspection.

The project is usable but does not yet have a tagged stable release. Architecture, implementation status, and deferred
work are tracked in the
[repository planning document](https://github.com/jbboehr/yumemi.php/blob/master/docs/development/planning.md).
