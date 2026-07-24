# Pint Parity Analysis

Snapshot date: 2026-07-24

This document compares IMM's current direction with Pint, the mature Python unit/quantity library. It is not a
commitment to clone Pint. Pint is useful as a feature checklist and as prior art for user expectations, but IMM's
distinctive goal is runtime unit handling plus PHPStan-backed static dimensional analysis for PHP.

Relevant Pint documentation:

- Tutorial: <https://pint.readthedocs.io/en/stable/getting/tutorial.html>
- API facets: <https://pint.readthedocs.io/en/stable/api/facets.html>
- Defining units: <https://pint.readthedocs.io/en/stable/advanced/defining.html>
- Contexts: <https://pint.readthedocs.io/en/stable/user/contexts.html>
- Unit systems: <https://pint.readthedocs.io/en/stable/user/systems.html>
- Temperature conversion: <https://pint.readthedocs.io/en/stable/user/nonmult.html>
- Logarithmic units: <https://pint.readthedocs.io/en/stable/user/log_units.html>
- Formatting: <https://pint.readthedocs.io/en/stable/getting/tutorial.html#string-formatting>
- NumPy support: <https://pint.readthedocs.io/en/stable/user/numpy.html>
- Typing: <https://pint.readthedocs.io/en/stable/advanced/typing.html>
- Wrapping/checking functions: <https://pint.readthedocs.io/en/stable/advanced/wrapping.html>
- Measurements: <https://pint.readthedocs.io/en/stable/advanced/measurement.html>
- Buckingham Pi theorem: <https://pint.readthedocs.io/en/stable/advanced/pitheorem.html>

## Rating Scale

Importance is rated for IMM, not for Pint:

- P0: Required for IMM to be credible at its own stated goal.
- P1: Important for a useful first public release.
- P2: Valuable, but can wait until the core is stable.
- P3: Optional, niche, or probably better as a separate package.

Difficulty is a rough estimate for implementation in this codebase:

- S: Small. Usually one focused change.
- M: Moderate. Several files and tests, but no major model change.
- L: Large. Requires new design surface or changes to core semantics.
- XL: Very large. Cross-cutting, high-risk, or likely multi-stage.

Status labels:

- Done: Implemented well enough to rely on.
- Partial: Useful behavior exists, but important semantics or API polish are missing.
- Absent: No meaningful implementation yet.
- Deliberately absent: Not a current goal unless project scope changes.

## Executive Summary

IMM has the hard center of a scalar, multiplicative unit runtime:

- exact rational arithmetic
- a unit expression AST
- a reducer/canceller
- a UDUNITS2-backed generated catalog
- alias and prefix resolution
- explicit conversion factors
- a `Quantity` value object
- `normalize()` and `simplify()` semantics
- real-world formula tests and runtime invariants

That is enough foundation to continue. Starting over would mostly repeat solved work.

IMM is not close to full Pint parity. Pint is a mature runtime library with extensive ergonomics, contexts, systems,
formatters, nonmultiplicative units, NumPy integration, measurements, function wrappers, and more. A fair estimate is:

- Scalar multiplicative runtime parity: about 50-65%.
- Full Pint runtime parity: about 15-25%.
- IMM's intended static-analysis feature set: about 0-5%.

The right strategy is not "be Pint in PHP." The right strategy is:

1. Keep the current runtime engine as the single source of truth.
2. Make the scalar multiplicative runtime boring and reliable.
3. Build the PHPStan extension on top of the same parser, registry, reducer, and conversion logic.
4. Add advanced runtime features only when they support real user workflows or static analysis.

The most important missing feature is not an exotic unit feature. It is the PHPStan type layer.

## Current IMM Foundation

Current codebase facts:

- Public runtime facade: `Units`
- Public quantity object: `Quantity`
- Exact numeric representation: `Number\Rational`
- Expression model: `Expr`, `Expr\Constant`, `Expr\Unit`, `Expr\Term`, `Expr\Compound`
- Parser stack: Bison grammar, lexer, AST nodes, generated parser
- Runtime analyzers: `ExprReducer`, `UnitNormalizer`, `ConversionFactorResolver`, `UnitResolver`
- Catalog: generated `data/udunits2.php`
- Generated catalog size at this snapshot:
  - 559 total unit names/aliases
  - 261 derived unit entries
  - 290 aliases
  - 7 base units
  - 1 dimensionless unit entry
  - 40 prefixes
- Catalog smoke tests currently expect 526 supported definitions to normalize.
- Affine temperature definitions remain intentionally unsupported.
- Logarithmic definitions are skipped by the importer.

The architecture is good enough to keep:

```text
UDUNITS2 XML -> Udunits2CatalogImporter -> PhpCatalogExporter -> data/udunits2.php
data/udunits2.php -> Udunits2UnitRegistry -> UnitResolver
Parser string -> Parser\Ast -> AstConverter/SymbolicAstConverter -> Expr
Expr -> ExprReducer -> reduced Expr
Expr -> UnitNormalizer -> normalized Expr
normalized Expr -> ConversionFactorResolver -> Rational factor
Units facade -> Quantity/runtime expression API
PHPStan extension -> same parser/registry/reducer/conversion semantics
```

## Feature Analysis

### 1. Expression Model And Reduction

Status: Partial
Importance: P0
Difficulty to finish: M

IMM has the core expression representation and deterministic reduction:

- constants combine
- compound expressions flatten
- inverse units cancel
- powers combine
- output ordering is stable

This is the foundation for runtime conversion and static analysis. It is one of the best parts of the current code.

Remaining work:

- Add a first-class equality/comparison helper instead of comparing formatted strings in some call paths.
- Consider a `Dimension` or `NormalizedExpr` public-ish value object for dimensional identity.
- Add more tests for negative powers, nested powers, dimensionless terms, and repeated constants.
- Decide whether expression objects are intended to be immutable public API or internal implementation details.

Recommendation: Keep the strategy. Improve equality and dimensions before building too much PHPStan logic.

### 2. Unit Parser

Status: Partial
Importance: P0
Difficulty to finish: M

IMM already parses a useful unit expression language:

- identifiers
- integer constants
- decimal and scientific constants
- multiplication with `*`
- implicit multiplication by adjacency
- division with `/`
- powers with `^`
- parentheses
- parsed but semantically rejected additions, subtractions, and affine `@`

This maps well to UDUNITS2-style definitions and is much better than hand-written parsing.

Remaining work:

- Improve parse error messages and locations.
- Define the exact public grammar for user-facing strings.
- Add corpus tests from generated UDUNITS2 definitions and from README examples.
- Decide whether to accept more Pint-like expression syntax, such as `**`, if users expect it.
- Decide whether `+` and `-` should remain parsed but unsupported or be rejected lexically for public runtime strings.

Recommendation: Keep the Bison parser. Do not hand-write a parser unless the generator becomes a serious maintenance
problem.

### 3. Registry And Default Catalog

Status: Partial
Importance: P0
Difficulty to finish: M

IMM uses generated UDUNITS2 catalog data and resolves aliases and prefixes. This is the right foundation. It avoids
inventing definitions and gives immediate coverage across SI, common derived units, US/imperial units, astronomical
units, and assorted constants.

Remaining work:

- Make registry composition a real API.
- Make user-defined units possible without subclassing `UnitRegistry`.
- Use generated plural metadata instead of simple suffix stripping alone.
- Decide case-sensitivity policy.
- Decide whether symbols and canonical names are both first-class metadata.
- Expose catalog introspection: names, aliases, prefixes, symbols, comments, supported/unsupported reason.

Recommendation: Do registry extensibility before advanced unit semantics. A usable registry builder will unblock many
other features.

### 4. Custom Unit Definitions

Status: Absent
Importance: P0/P1
Difficulty to finish: M/L

Pint lets users define units in text files and programmatically. IMM can currently construct a `UnitRegistry`, but the
ergonomics are too low-level for application use.

Likely minimum API:

```php
$registry = UnitRegistry::builder()
    ->withUdunits2()
    ->define('widget = 12 * meter')
    ->alias('widget', 'widgets')
    ->build();

$units = new Units($registry);
```

Design questions:

- Is the definition language the same as runtime unit expressions?
- How do we represent base dimensions defined by users?
- Are redefinitions rejected, ignored, or allowed with an explicit override mode?
- Do aliases, plural aliases, and symbols share one namespace?
- Should custom registries be immutable after construction?

Recommendation: Implement this before a public release. It is essential for real applications and for PHPStan config.

### 5. Quantity Creation API

Status: Partial
Importance: P0
Difficulty to finish: S/M

Current API:

```php
$units = Units::default();
$distance = $units->quantity(12, 'foot');
```

This is clear and PHP-appropriate. Pint's Python syntax can use `12 * ureg.foot`, but PHP operator overloading does not
exist, so IMM should not try to mimic that directly.

Remaining work:

- Add convenient constructors for decimal strings once numeric policy is settled.
- Decide whether floats are accepted directly or require explicit opt-in.
- Consider `Units::parseQuantity('12 foot')` for user input.
- Consider `Quantity::of(...)` only if it can avoid losing the `Units` context.

Recommendation: Keep `Units::quantity()` as the primary constructor. It makes registry context explicit.

### 6. Quantity Arithmetic

Status: Partial
Importance: P0
Difficulty to finish: M

IMM supports:

- addition/subtraction with matching reduced symbolic units
- multiplication/division by quantities
- multiplication/division by integers and rationals
- symbolic unit cancellation during multiplication/division
- explicit context checks

Current behavior intentionally does not auto-convert compatible units during `add()` and `sub()`. For example,
`meter + centimeter` fails unless the caller explicitly converts one side first.

Pint is more permissive at runtime and often converts compatible units automatically, usually preserving the left-hand
unit. IMM's stricter behavior is defensible, but it should be documented as a deliberate difference.

Remaining work:

- Add explicit `addConverted()` or `plus()` policy if we want Pint-like arithmetic.
- Add comparisons: `eq`, `lt`, `lte`, `gt`, `gte`, `compareTo`.
- Add unary negation and absolute value.
- Decide scalar behavior for dimensionless quantities.
- Decide whether numeric methods should accept `numeric-string`.

Recommendation: Keep strict `add()`/`sub()` for now. Consider adding an explicit converted-add operation later rather
than silently changing current semantics.

### 7. Explicit Conversion And Compatibility

Status: Partial
Importance: P0
Difficulty to finish: M

IMM can compute conversion factors, convert values, check compatibility, and convert `Quantity` instances to a target
unit. This is the minimum viable runtime.

Remaining work:

- Expose dimensionality directly.
- Improve exception messages to show source unit, target unit, and dimensions.
- Add conversion APIs for floats/decimals once numeric policy is settled.
- Cache parsed target expressions and normalized dimensions where it matters.

Recommendation: This is close to good enough for multiplicative units. Polish errors and dimensionality before widening
scope.

### 8. Normalization, Simplification, Base Units, And Root Units

Status: Partial
Importance: P1
Difficulty to finish: M/L

IMM currently has:

- `normalize()`: substitute unit definitions without changing the stored quantity value.
- `simplify()`: substitute unit definitions and fold unit scale into the stored quantity value.

Pint has several related operations:

- `to_base_units()`
- `to_root_units()`
- `to_reduced_units()`
- `to_preferred()`
- `to_compact()`

IMM's `simplify()` is closest to a root/base-unit conversion. It does not yet support preferred units, compact display,
or named derived-unit output.

Remaining work:

- Decide names around "base", "root", "reduced", "normalized", and "simplified".
- Add `toBaseUnits()` or `toRootUnits()` if the current `simplify()` name is not explicit enough.
- Add preferred unit selection later.
- Add compact prefix selection later.

Recommendation: Keep current semantics, but document them precisely. Naming matters here because Pint users will
expect specific distinctions.

### 9. Dimensionality API

Status: Partial/internal
Importance: P0/P1
Difficulty to finish: M

IMM can determine compatibility internally by normalizing units and comparing dimension expressions. It does not yet
expose a clean public dimensionality object.

Useful public API:

```php
$units->dimension('newton')->toString(); // mass * length / time^2, or equivalent canonical form
$quantity->dimension();
$units->sameDimension('meter', 'foot');
```

Design questions:

- Should dimensions use base unit names like `meter`, `second`, `kilogram`, or abstract names like `[length]`?
- UDUNITS2 data is unit-centered, while Pint has explicit dimension definitions.
- Static analysis probably wants dimensions independent of display units.

Recommendation: Add this before or during PHPStan MVP. Static diagnostics need good dimension rendering.

### 10. Numeric Types And Output Policy

Status: Partial
Importance: P1
Difficulty to finish: M

IMM currently uses exact rational values, which is a strong choice for conversion correctness and deterministic tests.
It avoids hidden float rounding in core logic.

What exists:

- `Rational`
- exact decimal-string parsing for unit constants
- rational output strings
- integer truncation with `toInt()`
- exact integer conversion with `toIntExact()`

Missing:

- direct float inputs
- decimal string quantity inputs
- decimal output with precision/rounding modes
- float output
- policy for overflow/huge rationals
- maybe integration with `brick/math`

Recommendation: Keep rational as the core. Add opt-in output adapters:

- `toFloat()`
- `toDecimal(int $scale, RoundingMode $mode)`
- `toInt()` with `intdiv()`-like truncation, already implemented
- `toIntExact()`, already implemented

Do not let floats become the internal representation.

### 11. Formatting And Display Units

Status: Partial
Importance: P1
Difficulty to finish: M/L

IMM has a basic expression formatter and string methods. Pint has extensive formatting: plain, abbreviated, pretty,
HTML, LaTeX, siunitx, localized output, and custom magnitude formatting.

IMM does not need all of that immediately, but it does need a deliberate display model.

Likely near-term API:

```php
$quantity->format();
$quantity->format(UnitFormat::symbols());
$quantity->format(UnitFormat::canonical());
$quantity->format(UnitFormat::ascii());
```

Things to decide:

- canonical names vs symbols
- ASCII vs Unicode output
- whether constants in units should display separately from quantity values
- whether derived units are preserved, expanded, or selected by preference
- how dimensionless quantities render

Recommendation: Build a small formatter now, not a Pint-sized formatter. Aim for stable plain text first.

### 12. Aliases, Prefixes, Plurals, Symbols, And Case Sensitivity

Status: Partial
Importance: P1
Difficulty to finish: M

IMM currently resolves generated aliases and prefixes, and uses simple plural stripping. That is enough for common
examples, but it will surprise users on edge cases.

Remaining work:

- Use generated plural aliases from UDUNITS2.
- Keep longest-prefix-first behavior.
- Decide whether symbols are preferred for output.
- Decide if lookup is case-sensitive by default.
- Provide explicit canonicalization: `canonicalName('meters') -> meter`.

Recommendation: Tighten this before calling the catalog "UDUNITS2-compatible" in public docs.

### 13. Offset And Affine Units

Status: Absent/unsupported
Importance: P1
Difficulty: L/XL

This is the biggest runtime semantic gap for ordinary users. Celsius and Fahrenheit matter in everyday software, and
Pint supports them with nonmultiplicative conversion rules and delta units.

Why it is hard:

- Affine conversion is not just multiplication by a scale factor.
- `10 degC + 10 kelvin` is ambiguous unless kelvin is treated as a delta.
- Multiplication and division involving offset units need restrictions.
- Static analysis must distinguish absolute temperature from temperature difference.
- Current `ConversionFactorResolver` assumes compatible units differ by a rational multiplicative factor.

Likely model:

- Keep multiplicative units on the current `Expr` path.
- Introduce converter objects, such as scale-only and affine converters.
- Add delta units for temperature differences.
- Reject ambiguous arithmetic by default.

Recommendation: Important, but do not start here. It is safer after registry, dimensions, formatting, and PHPStan MVP
are in place.

### 14. Logarithmic Units

Status: Absent/skipped by importer
Importance: P2/P3
Difficulty: XL

Pint supports logarithmic units such as decibels, though its own docs present the feature carefully. IMM currently skips
logarithmic definitions during UDUNITS2 import.

Why it is hard:

- Conversions are nonlinear.
- Compound log units have subtle semantics.
- Static analysis can check dimensions, but value-level conversion needs special cases.
- Rational arithmetic is not enough for logarithmic conversion.

Recommendation: Defer. It is not needed for a useful first version.

### 15. Contexts

Status: Absent
Importance: P2
Difficulty: XL

Pint contexts allow conversions that are dimensionally invalid under normal rules, such as wavelength to frequency via
the speed of light. They can also be parameterized and scoped.

This is powerful, but it cuts across IMM's static-analysis goal:

- Runtime contexts can use arbitrary value transformations.
- Static analysis can represent some context conversions by dimension graph edges.
- Parameterized contexts make inference and diagnostics much harder.
- Scoped contexts in PHP would need an API that does not rely on Python's `with` statement.

Possible PHP shape:

```php
$units->withContext('spectroscopy', fn (Units $u) => $wavelength->to('hertz'));
```

Recommendation: Defer until after the static analyzer has ordinary dimensional checks. Contexts are useful, but they
are not necessary for the first compelling version.

### 16. Unit Systems

Status: Absent
Importance: P2
Difficulty: L

Pint has systems such as MKS, CGS, SI, US, and imperial. A system changes what "base units" or preferred reductions
mean.

IMM has the unit data to convert many of these units, but not the metadata to choose system-specific base/preferred
units.

Remaining work:

- Define system metadata.
- Decide how systems interact with UDUNITS2's root definitions.
- Add `toSystem('si')`, `toBaseUnits(system: 'cgs')`, or similar.
- Resolve name collisions like US pint vs imperial pint.

Recommendation: Useful, but secondary. Preferred units are probably more valuable than full systems at first.

### 17. Preferred And Compact Units

Status: Absent
Importance: P2
Difficulty: M/L

Pint can choose more readable units, such as turning a very large hertz value into terahertz. IMM does not currently do
this.

Needed pieces:

- Unit metadata with preferred symbols/names.
- Prefix selection.
- Heuristics for magnitude ranges.
- User-provided preference lists.

Recommendation: Defer until formatting and custom registry composition exist.

### 18. Constants

Status: Partial
Importance: P2
Difficulty: M

UDUNITS2 includes constants such as `pi`, `gravity`, and `avogadro_constant`. IMM imports many of them as units or
dimensionless units with decimal definitions. Because IMM parses decimal definitions into rationals, constants become
exact rational approximations of the catalog values.

This is good for reproducibility, but users need to understand that `pi` is not symbolic or irrational. It is the exact
rational represented by the catalog decimal string.

Remaining work:

- Decide whether constants are units, quantities, or both.
- Expose constants intentionally instead of only as catalog entries.
- Document exact-decimal approximation behavior.
- Maybe allow symbolic constants later if formulas need them.

Recommendation: Keep current behavior, document it, and avoid pretending constants are mathematically exact unless the
catalog says they are exact.

### 19. Comparisons, Equality, And Predicates

Status: Absent/partial
Importance: P1
Difficulty: M

Pint quantities can participate in many mathematical and comparison operations. PHP cannot overload operators, but IMM
can still provide explicit methods.

Useful API:

```php
$a->equals($b);
$a->compareTo($b);
$a->isCompatibleWith($b);
$q->isDimensionless();
$q->isZero();
```

Design question:

- Should `equals()` auto-convert compatible units, or require exact same symbolic unit?

Recommendation: Add explicit methods with clear names. For example, `equalsQuantity()` can convert, while
`sameStoredUnit()` can be exact.

### 20. Math Functions

Status: Absent
Importance: P2
Difficulty: L

Pint integrates with many NumPy functions and enforces dimensional rules for functions like trig, sqrt, exp, and log.
In PHP, the equivalent would be explicit methods or utility functions.

Useful subset:

- `sqrt()` for units with even powers
- `pow(int $power)`
- `reciprocal()`
- dimensionless-only `sin`, `cos`, `tan`, `exp`, `log`

Difficulty comes from fractional powers. Current `Term` powers are integers, which is good for static analysis but
restrictive for `sqrt(meter^2)`.

Recommendation: Add integer `pow()` soon. Defer fractional powers and transcendental functions.

### 21. Static Analysis With PHPStan

Status: Absent
Importance: P0
Difficulty: XL

This is IMM's main differentiator. Pint has Python typing for magnitude types, but it is not primarily a static
dimensional analyzer. IMM should aim to make PHPDoc unit strings meaningful to PHPStan.

MVP target:

```php
/** @var Quantity<'meter'> $distance */
/** @var Quantity<'second'> $time */
$speed = $distance->div($time);
// inferred: Quantity<'meter / second'>
```

Must-have pieces:

- Parse `Quantity<'...'>` PHPDoc generic strings.
- Report invalid unit syntax and unknown unit names.
- Infer `Units::quantity($value, 'meter')`.
- Infer `Quantity::to('foot')`.
- Infer `mul()` and `div()` unit expressions.
- Check `add()` and `sub()` compatibility.
- Keep runtime and static semantics shared.

Likely PHPStan integration points:

- custom type class for unit-bearing quantities
- type node resolver extension for generic unit strings, if needed
- dynamic method return type extensions
- method type-specifying extensions or custom rules for diagnostics
- optional config for registry data and strictness mode

Main risks:

- PHPStan's extension APIs are detailed and easy to misuse.
- Literal-string inference matters. If unit strings are dynamic, diagnostics must degrade gracefully.
- Static analysis cannot run arbitrary runtime code safely.
- User-defined registries need a config story, not just runtime APIs.

Recommendation: This should be the next major feature track. Start with static parsing and diagnostics, then return
type inference, then arithmetic checks.

### 22. Function Boundary Checking

Status: Absent
Importance: P1/P2
Difficulty: M/L

Pint has runtime decorators such as `wraps()` to convert and check function arguments. PHP has a better opportunity:
attributes plus PHPStan rules.

Possible runtime/static design:

```php
#[UnitParam('meter')]
#[UnitReturn('second')]
function pendulumPeriod(Quantity $length): Quantity
{
    // ...
}
```

Or PHPDoc-only:

```php
/**
 * @param Quantity<'meter'> $length
 * @return Quantity<'second'>
 */
function pendulumPeriod(Quantity $length): Quantity
{
    // ...
}
```

Recommendation: Prefer PHPDoc generics first. Attributes can be added later if they provide real ergonomics.

### 23. Serialization

Status: Absent
Importance: P2
Difficulty: M

Applications will eventually need to store quantities in JSON, databases, messages, and config files.

Reasonable JSON shape:

```json
{
  "value": "355/113",
  "unit": "meter / second"
}
```

Design questions:

- Should serialized units preserve display syntax or canonicalize?
- How do custom registry definitions version with stored data?
- Are decimals stored as decimals or rationals?

Recommendation: Add after the public quantity API stabilizes.

### 24. Arrays, Collections, And Scientific-PHP Integration

Status: Absent
Importance: P3
Difficulty: L/XL

Pint has major NumPy, xarray, Dask, and ecosystem integration. PHP does not have an equivalent dominant numerical array
ecosystem.

Possible PHP use cases:

- arrays of `Quantity`
- column metadata for tabular data
- integration with math/statistics packages

Recommendation: Do not chase Pint here. Add small helpers only when a real PHP use case appears.

### 25. Measurements And Uncertainty

Status: Absent
Importance: P3
Difficulty: M/L

Pint supports measurements with uncertainty. IMM does not.

This can probably be a separate package or later layer:

```php
Measurement<Quantity<'meter'>>
```

Recommendation: Defer. It is not needed for dimensional analysis MVP.

### 26. Buckingham Pi Theorem

Status: Absent
Importance: P3
Difficulty: M/L

Pint includes Buckingham Pi theorem helpers for dimensional analysis. This is mathematically related but not central to
runtime conversion or PHPStan diagnostics.

Recommendation: Defer indefinitely unless IMM grows a scientific-computing audience.

### 27. Currency

Status: Absent
Importance: P3
Difficulty: L

Pint documents currency conversion as an advanced topic. IMM should avoid this in core because exchange rates are
time-varying, jurisdictional, and application-specific.

Recommendation: Keep currency out of core. A custom registry can support fixed contractual conversions if the user
really needs them.

### 28. Localization

Status: Absent
Importance: P3
Difficulty: M/L

Pint can localize formatted unit names with Babel. PHP has internationalization tooling, but this is not central to
IMM's static-analysis goal.

Recommendation: Defer. Make formatter internals extensible enough that localization can be added later.

### 29. Performance And Caching

Status: Partial
Importance: P1
Difficulty: M

Current performance is probably fine for tests and small runtime use, but PHPStan integration will stress the parser,
normalizer, and registry repeatedly.

Likely needs:

- parse cache by unit string
- normalized expression cache
- conversion factor cache
- immutable registry snapshot
- benchmark suite with common expressions and whole-catalog checks

Recommendation: Add caches when PHPStan work begins. Static analysis will make performance problems obvious.

### 30. Error Messages And Developer UX

Status: Partial
Importance: P1
Difficulty: M

Current exceptions exist, but user-facing diagnostics need more detail.

Needed improvements:

- Unknown unit messages with spelling suggestions.
- Parse errors with source positions.
- Incompatible unit errors showing source and target dimensions.
- Unsupported syntax errors that explain whether the blocker is affine, logarithmic, addition, subtraction, or `@`.
- PHPStan diagnostics that point at the exact PHPDoc or string literal.

Recommendation: Treat this as part of the PHPStan MVP, not as later polish.

### 31. Documentation And Examples

Status: Partial
Importance: P1
Difficulty: M

The README examples are executable tests, which is good. Documentation now needs to separate current behavior from
future intent.

Potential docs:

- runtime quickstart
- unit syntax reference
- registry/custom units guide
- generated catalog regeneration guide
- numeric precision guide
- PHPStan setup guide
- unsupported units and semantics page

Recommendation: Keep README small. Put deeper material in `docs/`.

### 32. Packaging, CI, And Release Hygiene

Status: Partial
Importance: P0/P1
Difficulty: M

The project has Composer, Nix, treefmt, pre-commit hooks, PHP-CS-Fixer, PHPStan, PHPUnit, and GitHub Actions. That is a
good base.

Remaining work:

- Decide whether Composer metadata currently overpromises static-analysis support before the extension exists.
- Add release workflow later.
- Add mutation or property-based tests only if bugs justify it.
- Add lowest-dependency and highest-dependency Composer CI jobs eventually.
- Verify generated parser/catalog regeneration in CI or document it as a maintainer task.

Recommendation: Keep the current setup. Do not spend more time here until feature work needs it.

## Parity Matrix

| Feature                        | IMM status       | Importance | Difficulty | Priority          |
| ------------------------------ | ---------------- | ---------- | ---------- | ----------------- |
| Expression model and reduction | Partial          | P0         | M          | Now               |
| Parser                         | Partial          | P0         | M          | Now               |
| Default catalog                | Partial          | P0         | M          | Now               |
| Custom unit definitions        | Absent           | P0/P1      | M/L        | Soon              |
| Quantity creation              | Partial          | P0         | S/M        | Now               |
| Quantity arithmetic            | Partial          | P0         | M          | Now               |
| Explicit conversion            | Partial          | P0         | M          | Now               |
| Dimensionality API             | Internal/partial | P0/P1      | M          | Soon              |
| Numeric output policies        | Partial          | P1         | M          | Soon              |
| Formatting                     | Partial          | P1         | M/L        | Soon              |
| Prefix/plural/symbol semantics | Partial          | P1         | M          | Soon              |
| Offset temperature units       | Absent           | P1         | L/XL       | Later             |
| Logarithmic units              | Absent           | P2/P3      | XL         | Defer             |
| Contexts                       | Absent           | P2         | XL         | Defer             |
| Unit systems                   | Absent           | P2         | L          | Later             |
| Preferred/compact units        | Absent           | P2         | M/L        | Later             |
| Constants                      | Partial          | P2         | M          | Later             |
| Comparisons/predicates         | Absent/partial   | P1         | M          | Soon              |
| Math functions                 | Absent           | P2         | L          | Later             |
| PHPStan unit types             | Absent           | P0         | XL         | Now               |
| Function boundary checking     | Absent           | P1/P2      | M/L        | After PHPStan MVP |
| Serialization                  | Absent           | P2         | M          | Later             |
| Arrays/scientific ecosystem    | Absent           | P3         | L/XL       | Defer             |
| Measurements/uncertainty       | Absent           | P3         | M/L        | Defer             |
| Buckingham Pi theorem          | Absent           | P3         | M/L        | Defer             |
| Currency                       | Absent           | P3         | L          | Avoid core        |
| Localization                   | Absent           | P3         | M/L        | Defer             |
| Performance/caching            | Partial          | P1         | M          | During PHPStan    |
| Error messages                 | Partial          | P1         | M          | Soon              |
| Documentation                  | Partial          | P1         | M          | Ongoing           |
| Packaging/CI                   | Partial          | P0/P1      | M          | Ongoing           |

## Recommended Roadmap

### Milestone 1: Runtime Core Hardening

Goal: make scalar multiplicative units boringly reliable.

Work:

- Add a dimensionality API.
- Add expression equality helpers.
- Improve incompatible-unit and parse errors.
- Tighten plural/alias/canonical-name behavior.
- Add public comparison methods.
- Add minimal formatter options.
- Decide numeric input/output policy.

This is mostly P0/P1 work and should happen before broad PHPStan inference.

### Milestone 2: Custom Registry And Definitions

Goal: allow real projects to use project-specific units.

Work:

- Add a registry builder or immutable registry composition layer.
- Add programmatic `define()` and `alias()` APIs.
- Consider a definition-file parser, likely reusing the unit expression parser.
- Add config shape that PHPStan can consume without executing arbitrary app code.

This should be done before claiming the library is generally useful outside canned UDUNITS2 conversions.

### Milestone 3: PHPStan MVP

Goal: make `Quantity<'meter / second'>` meaningful.

Work:

- Parse PHPDoc unit generics.
- Diagnose invalid unit strings and unknown units.
- Infer `Units::quantity()`.
- Infer `Quantity::to()`, `mul()`, `div()`, `normalize()`, and `simplify()`.
- Check `add()` and `sub()`.
- Decide strict exact-unit vs dimension-compatible modes.

This is the project's main differentiator and should take priority over Pint-style convenience features.

### Milestone 4: Runtime API Polish

Goal: make the runtime feel pleasant enough that the static analyzer has a good companion library.

Work:

- Add quantity parsing from strings.
- Add JSON serialization helpers.
- Add better format presets.
- Add `toBaseUnits()` or rename/alias `simplify()` if needed.
- Add preferred unit lists only if there is a real use case.

### Milestone 5: Advanced Semantics

Goal: cover important nonmultiplicative real-world cases without destabilizing the core.

Work:

- Offset temperatures and delta temperatures.
- Unit systems.
- Contexts, if still desired.
- Logarithmic units only after affine units are solved.

This is where Pint's feature set becomes expensive. Avoid pulling this milestone forward unless a user workflow demands
it.

## Strategic Conclusions

Do not restart from scratch. The current code has the right core shape:

- string units instead of one class per unit
- generated catalog instead of hand-written definitions
- exact rational core instead of float-first conversion
- shared runtime machinery intended for PHPStan
- explicit registry context via `Units`

The parts to change are mostly API boundaries and missing layers, not the underlying strategy.

Do not chase full Pint parity as the product goal. Pint parity is too broad and too Python-specific. IMM should instead
target:

1. A reliable scalar multiplicative runtime.
2. Strong PHPStan diagnostics and inference.
3. Enough runtime ergonomics that users can adopt the static analysis without resenting the companion API.

If IMM gets those right, it can be meaningfully useful long before it supports every advanced Pint feature.
