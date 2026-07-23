# Iudex Mensurarum Mysteriorum Notes

Working title:

- Full name: `Iudex Mensurarum Mysteriorum`
- Short package/repo candidate: `jbboehr/imm`
- PHP namespace candidate: `jbboehr\IudexMensurarumMysteriorum\`
- Meaning: roughly "Judge of the Mysteries of Measures"

The name is intentionally overdramatic, Latin, and chuuni-adjacent. The acronym `imm` keeps the package
practical while the namespace and README preserve the full title. `Iudex Mensurarum Mysteriorum` was chosen partly
because its cadence is close to `Index Librorum Prohibitorum`.

## Existing Code Assessment

There are two old projects:

- `units.php`: the stronger core dimensional-analysis library
- `phpstan-units`: an early PHPStan prototype

`units.php` is worth reusing as reference material. It already has the important conceptual pieces:

- Parser for unit expressions
- AST conversion into an expression model
- Unit registry backed by generated UDUNITS2 data
- Prefix and plural resolution
- Normalization of derived units to base units
- Expression reduction and cancellation
- Conversion-factor compatibility checks

`phpstan-units` is less worth preserving directly. It duplicates the unit/expression model, hardcodes a few unit
classes and conversions, and the PHPStan multiplication extension is still a stub returning `NeverType`. It is useful
as a sketch of how to register PHPStan extensions, but not as the foundation.

Conclusion: do not restart the ideas from zero, but do start a clean repo. Treat `units.php` as the reference
implementation and `phpstan-units` as disposable prototype scaffolding.

The new project should not be static-analysis-only. It should also be usable as a runtime unit conversion library.
PHPStan should be an integration layer over the same runtime-safe core, not a parallel implementation.

## Architecture Direction

The new project should integrate the core unit engine and PHPStan extension in one repo.

Suggested shape:

```text
src/
  Expr/
  Parser/
  Registry/
  Analyzer/
  PHPStan/
data/
tests/
  Unit/
  Analyzer/
  PHPStan/
extension.neon
composer.json
```

Important principle:

> One expression model. One registry. One normalization engine.

PHPStan should not have a separate hardcoded unit model. It should ask the core engine to parse, normalize, combine,
and compare unit expressions.

## Design Choices

Prefer unit strings in PHPDoc over one PHP class per unit.

Possible syntax:

```php
/** @var Quantity<'meter'> */
$distance;

/** @var Quantity<'meter / second'> */
$speed;
```

This is more scalable than:

```php
/** @var intWithUnit<Meter> */
$distance;
```

The string form can represent compound units without requiring a PHP class for every unit or derived unit.

Runtime conversion should be a first-class design goal. That means the core should expose a real API for parsing units,
checking compatibility, converting values, and possibly representing quantities.

Static analysis can still use phantom types in PHPDoc, but those phantom types should correspond to the same unit
expressions understood by the runtime library.

## Runtime Library Goals

The runtime API should support at least:

```php
$units = Units::default();

$meter = $units->unit('meter');
$foot = $units->unit('foot');

$factor = $units->conversionFactor('foot', 'meter');
$value = $units->convert(12, 'foot', 'meter');
```

If a runtime quantity object is included:

```php
$distance = Quantity::of(12, 'foot');
$meters = $distance->to('meter');
```

Core runtime responsibilities:

- Parse unit expressions
- Normalize units to base dimensions
- Check dimensional compatibility
- Compute exact rational conversion factors where possible
- Convert numeric values with explicit precision behavior
- Reject incompatible conversions with clear exceptions

Important design point: dimensional compatibility and conversion are related but distinct. `meter` and `foot` have
compatible dimensions, but converting between them requires a scale factor. Some units, especially temperature offsets,
may need affine conversion later and should not be forced into the simple multiplicative model too early.

## First Useful Version

The first milestone should focus on multiplicative dimensional compatibility and runtime conversion for simple
scale-based units.

Implement enough to support:

```php
/** @var Quantity<'meter'> $m */
/** @var Quantity<'second'> $s */

$m + $m; // ok
$m + $s; // error
$m * $s; // Quantity<'meter second'>
$m / $s; // Quantity<'meter / second'>
```

Initial scope:

- Parse unit expressions like `meter`, `meter / second`, and `meter second^-2`
- Normalize expressions to canonical base dimensions
- Compare dimensional compatibility
- Compute conversion factors for simple multiplicative units
- Convert runtime numeric values between compatible scale-based units
- Infer units through multiplication and division
- Report PHPStan errors for incompatible addition/subtraction
- Add PHPStan fixture tests proving the behavior

Defer:

- Full UDUNITS2 import
- Temperature offsets and other affine units
- Exact-unit strictness
- Scalar-specific `unit_int` / `unit_float` types

## Later Modes

Eventually support two compatibility modes:

- `dimension`: allow compatible dimensions such as `meter + foot`
- `exact`: require identical units unless an explicit conversion is used

Default should probably be `dimension`, because dimensional analysis primarily cares that the dimensions match. Exact
unit checking can be a stricter project option.

## Porting Strategy

Port from `units.php` in pieces:

1. Expression model
2. Canonical reducer
3. Parser
4. Tiny hand-written registry
5. Unit normalization
6. Runtime conversion-factor resolver
7. Quantity/value API
8. PHPStan type representation
9. PHPStan operator/rule tests
10. Generated UDUNITS2 registry

The reducer/canonicalizer is the part that needs the most rigor. It should have explicit invariants and tests before
PHPStan integration gets complicated.

## Practical Recommendation

Start the repo under `imm.php`, scaffold Composer, then build and test the runtime core outside PHPStan first.

First real proof:

```php
parse('meter / second')->normalize()->toString();
parse('kilometer')->normalize()->isCompatibleWith(parse('meter'));
convert(1, 'kilometer', 'meter'); // 1000
```

Only after that works should the PHPStan extension wrap it.
