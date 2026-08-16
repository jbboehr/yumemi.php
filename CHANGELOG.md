# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to
[Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Context-bound preferred-unit profiles for exact application-selected `Quantity` conversion by dimension.
- Exact `Quantity::toCompact()` engineering-prefix selection within a caller-selected named unit family.
- PHPStan brand inference for `intval()`, `floatval()`, `doubleval()`, `fdiv()`, `intdiv()`, `fmod()`, `hypot()`, and
  native integer-exponent `pow()` calls.
- The stable `yumemi.invalidUnitMathFunction` diagnostic for incompatible branded `fmod()`/`hypot()` calls and invalid,
  ambiguous, or unrepresentable `fdiv()`/`intdiv()`/`pow()` unit algebra.
- PHPStan inference for canonical `deg2rad()` and `rad2deg()` conversions and branded trigonometric functions, including
  `atan2()`, with the stable `yumemi.invalidUnitAngleFunction` diagnostic for incorrect angle, ratio, or operand brands.
- Exact PHPStan constant inference for branded `round()` calls with statically known precision and supported
  half-rounding modes.
- PHPStan brand inference for `array_sum()` over definitionally equivalent numeric elements, with the stable
  `yumemi.invalidUnitAggregation` diagnostic for mixed or incompatible summands.
- PHPStan unit composition for `array_product()` over sealed, statically known array shapes, using
  `yumemi.invalidUnitAggregation` when no sound product unit can be inferred.
- PHPStan brand inference for native `range()` endpoints and explicit steps, with exact small ranges, bounded integer
  lists, and the stable `yumemi.invalidUnitRange` diagnostic for mixed or incompatible arguments.

### Changed

- Yumemi-owned PHPStan diagnostics preserve statically known unit spellings at direct argument boundaries while inferred
  types and diagnostics formed after semantic joins remain canonical.
- Newly modeled native functions can expose unit errors at existing call sites that previously received bare numeric
  results; convert, rebrand, or explicitly cast at the intended unit boundary.

### Deprecated

### Removed

### Fixed

- Branded `**` expressions now report an exponent-range diagnostic instead of aborting PHPStan analysis when their
  derived unit exceeds the supported exponent bounds.
- Branded native multiplication and division now report an invalid operation instead of aborting PHPStan analysis when
  their derived unit exceeds the supported exponent bounds.

### Security

## [0.1.0] - 2026-08-11

### Added

- Initial PHPStan extension with unit-branded native integers, floats, and numeric strings; inferred arithmetic,
  comparisons, selected scalar functions, stable diagnostics, custom registry configuration, and optional `@yumemi-*`
  annotations.
- Exact runtime arithmetic and conversion through `Rational`, `Units`, `Quantity`, and affine `PointQuantity` values,
  with explicit integer, decimal, significant-digit, and binary floating-point output policies.
- A shared bounded unit-expression language, generated UDUNITS2 catalog, authored image and document units, custom
  registry builder, extension dimensions, affine difference units, and catalog introspection.
- Configurable ASCII and Unicode formatting, exact JSON representations, compact debug output, and versioned native
  serialization with custom-registry restoration.
- Verified public documentation, portable runtime conformance fixtures, release-style consumer tests, and automatic or
  manual PHPStan extension registration.

[Unreleased]: https://github.com/jbboehr/yumemi.php/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/jbboehr/yumemi.php/releases/tag/v0.1.0
