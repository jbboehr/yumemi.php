{{#title Yumemi - Static dimensional analysis for PHPStan}}

![Iudex Mensurarum Mysticarum Yumemi](images/yumemi-banner.png)

# Yumemi

PHP ordinarily treats meters, feet, and seconds as interchangeable numbers, allowing incorrect arguments and arithmetic
to pass unnoticed. Yumemi provides static dimensional analysis for PHPStan and exact runtime unit conversion for PHP.

The PHPStan extension tracks units on ordinary `int` and `float` values. It can reject incompatible arguments and
arithmetic without requiring runtime wrapper objects. The runtime library uses the same parser, unit catalog, and
normalization engine for exact quantity arithmetic, temperature scales and other coordinate points, and conversion.

## Start Here

```php
<?php

use function jbboehr\Yumemi\unit;

/** @param unit_float<'meter'> $height */
function setDoorHeight(float $height): void {}

// @akashi-phpstan-error argument.type: unit_float<'meter'>, 6.0&unit_float<'international_foot'> given
setDoorHeight(unit(6.0, 'foot'));
```

PHPStan reports the unit mismatch while PHP still receives an ordinary `float`. In tested examples, an
`@akashi-phpstan-error` comment records the stable PHPStan diagnostic identifier and a distinctive fragment of the
expected message on the following statement. It is documentation-test notation, not Yumemi syntax. `foot` is an alias of
the catalog's canonical `international_foot` unit, and diagnostics use the canonical name after resolving aliases.

- **I want PHPStan to catch unit mistakes in native numbers.** Start with
  [Static Analysis](getting-started.md#verify-static-analysis).
- **I need exact runtime conversion, quantity arithmetic, or values on temperature scales such as Celsius.** Start with
  [Runtime Conversion](getting-started.md#runtime-conversion).

A **branded native value** is still an ordinary PHP `int` or `float`. Types such as `unit_float<'meter'>` add a unit
only inside PHPStan; they do not create runtime wrappers.

## Browse Documentation

- [Getting Started](getting-started.md) provides a complete installation and verification path.
- [Core Concepts](core-concepts.md) helps choose among branded native values, exact quantities, and coordinate points.
- [Recipes](recipes.md) shows common integration, conversion, custom-unit, and display tasks.
- [PHPStan](reference/phpstan.md) defines branded native types, operator inference, helpers, generic quantities,
  configuration, and diagnostics.
- [Unit Syntax](reference/unit-syntax.md) defines the expression language shared by PHPStan and the runtime.
- [Runtime API](reference/runtime.md) documents quantities, coordinate points, conversion, numeric output, dimensions,
  and formatting.
- [Built-in and Custom Units](reference/catalog.md) documents UDUNITS2 behavior, custom registries, and introspection.

Yumemi 0.1 is an initial public development release. Patch releases within the 0.1 line preserve the documented
contract, while later 0.x minor releases may deliberately introduce documented breaking changes before 1.0.
Architecture, implementation status, and deferred work are tracked in the
[repository planning document](https://github.com/jbboehr/yumemi.php/blob/master/docs/development/planning.md).
