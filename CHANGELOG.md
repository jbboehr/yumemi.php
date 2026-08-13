# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to
[Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Context-bound preferred-unit profiles for exact application-selected `Quantity` conversion by dimension.

### Changed

- Yumemi-owned PHPStan diagnostics preserve statically known unit spellings at direct argument boundaries while inferred
  types and diagnostics formed after semantic joins remain canonical.

### Deprecated

### Removed

### Fixed

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
