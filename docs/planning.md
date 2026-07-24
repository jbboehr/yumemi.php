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
- Tiny in-memory default registry:
  - `meter`
  - `second`
  - `foot`
  - `kilometer`
  - `minute`
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

Current verification:

- PHPUnit passes
- PHPStan passes
- PHPCS passes
- Composer validation passes

Known test-suite issue:

- PHPUnit reports one deprecation warning, likely from config/tooling. It has not affected test execution.

## Runtime API Direction

The current API is intentionally small:

```php
$units = Units::default();

$units->normalize('kilometer'); // 1000 * meter
$units->compatible('meter / second', 'kilometer / minute'); // true
$units->conversionFactor('meter / second', 'kilometer / minute'); // 3/50
$units->convert(1, 'kilometer', 'meter'); // 1000
```

The next runtime API should introduce a real `Quantity` value object. Returning raw `Expr` from `quantity()` is not a
good long-term API for a runtime conversion library.

Desired shape:

```php
$distance = $units->quantity(12, 'foot');
$meters = $distance->to('meter');

$speed = $units->quantity(1, 'meter / second');
$pace = $speed->to('kilometer / minute');
```

Possible `Quantity` methods:

- `value(): Rational`
- `unit(): Expr`
- `to(Expr|string $unit): self`
- `mul(self|int|Rational $other): self`
- `div(self|int|Rational $other): self`
- `toExpr(): Expr`
- `toString(): string`

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

## Deferred Work

Defer these until the multiplicative runtime foundation is stronger:

- Full UDUNITS2 import
- GNU Units import
- Prefix and plural resolution
- Offset and affine units, especially temperature
- Logarithmic units
- Better numeric output policies for decimal/float conversion
- Exact-unit strictness mode
- PHPStan static analysis extension
- Scalar-specific PHPDoc types such as `unit_int` or `unit_float`

## Near-Term Roadmap

Suggested next slices:

1. Add `Quantity`.
   Make runtime values first-class instead of returning raw expression objects from `Units::quantity()`.

2. Add a registry abstraction.
   The current `UnitRegistry` is concrete and tiny. Before UDUNITS2, introduce an interface or registry composition
   strategy so generated and user-provided registries have a clean place to plug in.

3. Add aliases/prefixes/plurals.
   This should reuse the old `UnitResolver` idea, but adapt it to IMM's expression and registry model.

4. Port/generated UDUNITS2 registry.
   Bring over the old data generation scripts after the registry API is ready.

5. Add PHPStan type parsing.
   Start with PHPDoc `Quantity<'meter / second'>` and make PHPStan parse the unit string through IMM's runtime parser.

6. Add PHPStan rules/operators.
   Use runtime normalization and compatibility checks to report incompatible addition, subtraction, assignments, and
   function arguments.

## Current Architecture Sketch

```text
Parser string -> Parser\Ast
Parser\Ast -> Analyzer\AstConverter -> Expr
Expr -> Analyzer\UnitNormalizer -> normalized Expr
normalized Expr -> Analyzer\ConversionFactorResolver -> Rational factor
Units facade -> public runtime API
```

The PHPStan layer should later reuse the same pipeline.
