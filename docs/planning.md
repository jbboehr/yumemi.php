# Iudex Mensurarum Mysteriorum Planning

Working title:

- Full name: `Iudex Mensurarum Mysteriorum`
- Package/repo: `jbboehr/imm`
- PHP namespace: `jbboehr\IudexMensurarumMysteriorum\`
- Meaning: roughly "Judge of the Mysteries of Measures"

The name is intentionally overdramatic, Latin, and chuuni-adjacent. The acronym `imm` keeps day-to-day package use
practical while the namespace and README preserve the full title. `Iudex Mensurarum Mysteriorum` was chosen partly
because its cadence is close to `Index Librorum Prohibitorum`.

## Project Goal

IMM should be both:

- A runtime unit expression, dimensional compatibility, and conversion library.
- A PHPStan extension for static dimensional analysis.

The runtime library is the source of truth. PHPStan should be an adapter over the same parser, registry, normalizer,
and conversion semantics rather than a separate implementation.

Important principle:

> One expression model. One registry. One normalization engine.

## Old Code Assessment

The old work was split across two repositories:

- `units.php`: stronger runtime/parser/analyzer foundation
- `phpstan-units`: early PHPStan prototype

Useful pieces from `units.php`:

- Bison parser for unit expressions
- AST model for parsed unit syntax
- AST-to-expression conversion approach
- Unit registry backed by generated UDUNITS2 data
- Prefix and plural resolution
- Derived-unit normalization
- Expression reduction and cancellation
- Conversion-factor compatibility checks

Less useful pieces from `phpstan-units`:

- Hardcoded unit classes and conversion classes
- Duplicated expression model
- Stub PHPStan operator extension

Conclusion: `units.php` is the reference implementation. `phpstan-units` is useful only as a sketch of PHPStan
extension registration.

## Current Status

Already implemented:

- Composer/Nix/project scaffold
- Exact `Rational` number type
- Core expression model:
  - `Expr`
  - `Expr\Constant`
  - `Expr\Unit`
  - `Expr\Term`
  - `Expr\Compound`
- Canonical expression reduction:
  - flatten compounds
  - combine constants
  - combine unit powers
  - cancel inverse units
  - deterministic unit ordering
- Derived-unit normalization
- Generated UDUNITS2 catalog data in `data/udunits2.php`
- Catalog import/export tooling:
  - `Udunits2CatalogImporter`
  - `PhpCatalogExporter`
  - `GenerateUdunits2CatalogCommand`
  - `bin/generate-udunits2-catalog`
- Runtime registry layer:
  - `UnitRegistry`
  - `Udunits2UnitRegistry`
  - generated aliases
  - generated prefix data
  - resolver-side prefix handling
  - simple plural stripping
- Runtime conversion-factor resolver
- Public `Units` facade with `Expr|string` ergonomics:
  - `unit()`
  - `parse()`
  - `normalize()`
  - `compatible()`
  - `conversionFactor()`
  - `convert()`
  - `quantity()`
- Ported generated parser from `units.php`:
  - grammar
  - lexer
  - AST nodes
  - generated `Parser.php`
  - Composer/Makefile generation wiring
- AST converter for supported runtime syntax:
  - identifiers
  - integer constants
  - decimal/scientific constants
  - multiplication
  - division
  - integer powers
- Explicit rejection for parsed-but-unsupported syntax:
  - addition
  - subtraction
  - `@`
- Runtime `Quantity` value object:
  - explicit `Units` context
  - exact `Rational` value storage
  - symbolic unit storage for display/chosen syntax
  - resolved unit storage for catalog-aware conversion
  - `to()` and `valueIn()` explicit conversion
  - `add()` and `sub()` for matching reduced unit syntax
  - `mul()` and `div()` for unit arithmetic
  - `normalize()` for unit-definition substitution without changing stored value
  - `simplify()` for unit-definition substitution with scale folded into the stored value
  - context checks that reject arithmetic between quantities from different `Units` instances

Current verification:

- PHPUnit passes
- PHPStan passes
- PHPCS passes
- Composer validation passes

Known test-suite issue:

- PHPUnit reports one deprecation warning, likely from config/tooling. It has not affected test execution.

## Runtime API Direction

The runtime API is intentionally small, but it now has both expression-level and value-level entry points.

```php
$units = Units::default();

$units->normalize('kilometer'); // 1000 * meter
$units->compatible('meter / second', 'kilometer / minute'); // true
$units->conversionFactor('meter / second', 'kilometer / minute'); // 3/50
$units->convert(1, 'kilometer', 'meter'); // 1000
```

The main user-facing value API is `Quantity`:

```php
$distance = $units->quantity(12, 'foot');
$meters = $distance->to('meter');

$speed = $units->quantity(1, 'meter / second');
$pace = $speed->to('kilometer / minute');
```

Current `Quantity` methods:

- `value(): Rational`
- `unit(): Expr`
- `expr(): Expr`
- `to(Expr|string $unit): self`
- `valueIn(Expr|string $unit): Rational`
- `intValueIn(Expr|string $unit): int`
- `exactIntValueIn(Expr|string $unit): int`
- `add(self $other): self`
- `sub(self $other): self`
- `mul(self|int|Rational $other): self`
- `div(self|int|Rational $other): self`
- `normalize(): self`
- `simplify(): self`
- `valueToString(): string`
- `unitToString(): string`
- `toString(): string`

Important runtime rule:

> Quantity arithmetic does not implicitly convert compatible units.

For example, `(meter / second) * second` reduces to `meter`, but `centimeter / second / foot` stays in chosen symbolic
units until the caller explicitly asks for conversion or simplification.

There are currently two catalog-aware operations with different intent:

- `normalize()`: substitutes unit definitions without changing the stored quantity value.
- `simplify()`: substitutes unit definitions and folds any unit scale factor into the stored quantity value.

## Parser And Syntax Direction

The parser is intentionally broader than the semantic runtime layer. This is acceptable because the long-term goal is
UDUNITS2 compatibility, but syntax must not imply semantic support.

Supported by parser and converter now:

- `meter`
- `meter * second`
- `meter second`
- `meter / second`
- `meter^2`
- `second^-2`
- `(meter / second)^2`
- decimal constants such as `1.25 meter`
- scientific notation accepted by the lexer

Parsed but unsupported by converter:

- `meter + second`
- `meter - second`
- `meter @ 2`

This should remain explicit until offsets/affine units are designed.

The parser can read more UDUNITS2 syntax than the runtime chooses to support semantically. The importer currently skips
logarithmic definitions and the runtime rejects affine syntax such as Celsius offsets.

## Design Choices

Prefer unit strings over one PHP class per unit.

Good user-facing syntax:

```php
/** @var Quantity<'meter'> */
$distance;

/** @var Quantity<'meter / second'> */
$speed;
```

Avoid making users define or reference classes like:

```php
/** @var intWithUnit<Meter> */
$distance;
```

The string form can represent compound units without requiring a PHP class for every base, derived, or compound unit.

The PHPStan extension should eventually make `Quantity<'meter / second'>` meaningful statically, but runtime
application code should continue to use ordinary `Units` and `Quantity` objects.

## Compatibility And Conversion

Dimensional compatibility and conversion are related but distinct:

- `meter` and `foot` are dimensionally compatible.
- Converting between them requires a scale factor.
- `meter` and `second` are incompatible.

Eventually support two compatibility modes:

- `dimension`: allow compatible dimensions such as `meter + foot`
- `exact`: require identical units unless an explicit conversion is used

Default should probably be `dimension`, because dimensional analysis primarily cares that dimensions match. Exact unit
checking can be a stricter project/PHPStan option.

The current runtime `Quantity::add()` and `Quantity::sub()` are stricter than that future PHPStan default: they require
the same reduced symbolic unit expression unless the caller explicitly converts one side first. That keeps runtime math
predictable while leaving room for PHPStan configuration later.

## Deferred Work

The multiplicative runtime foundation is now strong enough to start static analysis work. Remaining runtime gaps are
mostly catalog semantics, API polish, and edge-case formatting.

- GNU Units import
- User-defined registry/catalog composition
- Better plural handling using generated UDUNITS2 plural metadata rather than suffix stripping alone
- Offset and affine units, especially temperature
- Logarithmic units
- Better numeric output policies for decimal/float conversion
- Exact-unit strictness mode
- PHPStan static analysis extension
- Scalar-specific PHPDoc types such as `unit_int` or `unit_float`
- Public documentation for generated catalog regeneration
- Public documentation for runtime guarantees and non-goals

## Near-Term Roadmap

Suggested next slices:

1. Add PHPStan type parsing.
   Start with PHPDoc `Quantity<'meter / second'>`. Parse the unit string through IMM's runtime parser and store the
   parsed expression in a custom PHPStan type.

2. Add PHPStan diagnostics for invalid unit strings.
   Invalid unit syntax or unknown units in `Quantity<'...'>` should produce normal PHPStan errors with useful messages.

3. Add PHPStan return-type inference for `Quantity` methods.
   Infer `to('foot')`, `normalize()`, `simplify()`, `mul()`, and `div()` using the same runtime expression logic.

4. Add PHPStan checks for `add()` and `sub()`.
   Reject incompatible dimensions first. Later, make exact-unit strictness configurable.

5. Harden registry extensibility.
   Decide whether `UnitRegistry` should remain a concrete base class, become an interface, or be composed behind a
   resolver that can merge generated and user-defined units.

6. Improve catalog semantics.
   Replace simple plural stripping with catalog plural aliases where possible, and design explicit behavior for affine
   and logarithmic definitions.

## Current Architecture Sketch

```text
UDUNITS2 XML -> Udunits2CatalogImporter -> PhpCatalogExporter -> data/udunits2.php
data/udunits2.php -> Udunits2UnitRegistry -> UnitResolver
Parser string -> Parser\Ast -> AstConverter/SymbolicAstConverter -> Expr
Expr -> ExprReducer -> reduced Expr
Expr -> UnitNormalizer -> normalized Expr
normalized Expr -> ConversionFactorResolver -> Rational factor
Units facade -> Quantity/runtime expression API
```

The PHPStan layer should later reuse the same pipeline.
