# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to
[Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Add `PrefixApplicationDescriptor` and `CatalogNameKind::Prefixed` for dynamically prefixed unit provenance.
- Add `unit_factor()` with PHPStan target/source quotient inference for typed native conversion-factor arithmetic.
- Add exact affine conversion for UDUNITS2 and custom offset units, including `Units::convertFloat()` and PHPStan-aware
  `unit_to()` results.

### Changed

- Make `Units::describe()` and `UnitRegistry::describe()` synthesize descriptors for dynamically prefixed unit names.
- Make `conversionFactor()` reject conversions with a nonzero offset through `NonMultiplicativeConversionException` and
  expose affine dimensions through `compatible()` and `dimension()`.

### Deprecated

### Removed

### Fixed

### Security

[Unreleased]: https://github.com/jbboehr/yumemi.php/commits/master
