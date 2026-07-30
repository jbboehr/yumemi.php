![Iudex Mensurarum Mysticarum Yumemi](images/yumemi-banner.png)

# Yumemi

Yumemi provides static dimensional analysis for PHPStan and exact runtime unit conversion for PHP.

The PHPStan extension tracks units on ordinary `int` and `float` values. It can reject incompatible arguments and
arithmetic without requiring runtime wrapper objects. The runtime library uses the same parser, unit catalog, and
normalization engine for quantity arithmetic and conversion.

## Start Here

- [Getting Started](getting-started.md) covers installation and the shortest static-analysis and runtime examples.
- [Core Concepts](core-concepts.md) helps choose between branded native values and exact quantities.
- [PHPStan](reference/phpstan.md) defines branded native types, operator inference, helpers, generic quantities,
  configuration, and diagnostics.
- [Unit Syntax](reference/unit-syntax.md) defines the expression language shared by PHPStan and the runtime.
- [Runtime API](reference/runtime.md) documents quantities, conversion, numeric output, dimensions, and formatting.
- [Catalog](reference/catalog.md) documents UDUNITS2 behavior, custom registries, introspection, and regeneration.

The project is usable but does not yet have a tagged stable release. Architecture, implementation status, and deferred
work are tracked in the
[repository planning document](https://github.com/jbboehr/yumemi.php/blob/master/docs/development/planning.md).
