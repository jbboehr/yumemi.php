# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to
[Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Added structured affine and logarithmic support metadata to catalog descriptors, with deliberate runtime and PHPStan
  diagnostics for known unsupported units.

### Changed

- Changed `UnitRegistryBuilder` fluent methods to mutate and return the current builder while preserving immutable
  registry snapshots from `build()`.
- Retained logarithmic UDUNITS2 definitions in the generated catalog instead of reporting those names as unknown.

### Deprecated

### Removed

### Fixed

- Made generated UDUNITS2 plural aliases authoritative, preserving explicit and suppressed plurals without pluralizing
  symbols at runtime.

### Security

[Unreleased]: https://github.com/jbboehr/yumemi.php/commits/master
