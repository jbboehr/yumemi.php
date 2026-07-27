# PHPStan Extension Plan

Snapshot of design discussion for static dimensional analysis in Yumemi (2026-07-24).

Related: [planning.md](planning.md), [grok-review.md](grok-review.md), [pint-parity.md](pint-parity.md).

## Goal

Ship a PHPStan extension that catches unit mistakes at analysis time:

- Invalid or unknown unit strings in PHPDoc and constant arguments
- Incompatible `add` / `sub`
- Useful return types for `mul` / `div` / `to` / `normalize` / `simplify`

## Runtime vs static product split

These are related but not the same product surface:

| Layer                  | Magnitude model                                     | Primary audience                            |
| ---------------------- | --------------------------------------------------- | ------------------------------------------- |
| **Runtime `Quantity`** | **`Rational` only** (exact conversion)              | Code that opts into the Yumemi value object |
| **PHPStan unit types** | **Native magnitudes** (`int`, `float`, …) plus unit | App code that stays on native PHP types     |

It is fine — and intentional — that runtime storage stays Rational-only while static analysis tracks `int` / `float`
(and later other natives) with units. Most PHPStan users will annotate and check **native-typed** variables and
parameters; they may never construct a runtime `Quantity`.

Yumemi runtime still supplies the **unit engine** (parse, resolve, reduce, dimension, convert factors). PHPStan attaches
that engine to native types and optional `Quantity` types.

## Guiding Principle

> PHPStan reuses Yumemi’s unit engine; it does not require every analysed value to be a runtime `Quantity`.

Use the same:

- parser and `AstConverter`
- `UnitResolver` / registry (including `UnitRegistryBuilder`)
- `ExprReducer`, `ExprComparer`
- `Dimension` / `DimensionResolver`
- conversion-factor rules where relevant

Do **not** reimplement unit parsing, reduction, or catalogs inside PHPStan types.

```text
PHPDoc / call site (often native int|float + unit)
    → constant unit string or type args
    → Yumemi parse / resolve / reduce / compare
    → PHPStan Type + rule identifiers
```

## Type Model: Explicit Native Magnitudes

### Preferred static shape

Static unit types are **native number kind × unit expression**.

Working names (exact PHPDoc spelling TBD):

```php
/** @var unit_int<'meter'> $lengthCm */
/** @var unit_float<'meter / second'> $speed */
/** @var unit_int<'second'> $dt */
```

or equivalently a single generic:

```php
/** @var UnitValue<int, 'meter'> $lengthCm */
/** @var UnitValue<float, 'meter / second'> $speed */
```

These describe **PHPStan-level** magnitudes. At runtime the variable is still a plain `int` or `float` unless the user
also wraps a `Quantity`.

### Runtime `Quantity` (optional path)

Runtime remains:

```php
final class Quantity
{
    public readonly Rational $value;
    // unit expr ...
}
```

For code that uses the object API, PHPStan can still understand:

```php
/** @var Quantity<'meter'> $q */           // sugar
/** @var Quantity<Rational, 'meter'> $q */ // explicit; Rational is the only runtime storage
```

`Quantity` annotations default number kind to **`Rational`**. They are not required for the native-type analysis path.

### Why both layers

| Need                                                     | Mechanism                                                        |
| -------------------------------------------------------- | ---------------------------------------------------------------- |
| Exact library math / conversion                          | Runtime `Quantity` + `Rational`                                  |
| Analyse ordinary PHP (`int`/`float` APIs, loops, arrays) | Static `unit_int` / `unit_float` (or `UnitValue<int\|float, U>`) |
| Unit algebra (mul/div/add checks)                        | Shared Yumemi `Expr` + dimension engine                          |

Codex-style pushback on **`intWithUnit` as a second runtime world** still applies: do not invent a parallel library of
instrumented ints. Do use **PHPStan types** that behave like `int`/`float` with an attached unit for analysis.

### Challenges of native unit types (accepted tradeoffs)

| Challenge                                      | Mitigation                                                                 |
| ---------------------------------------------- | -------------------------------------------------------------------------- |
| Brands strip on cast / `(int)` / some builtins | Rules for common safe ops; error or wipe unit on unsafe casts              |
| `+` / `*` on two unit values                   | Operator type extensions: same unit for `+`/`-`; combine units for `*`/`/` |
| Mixing unit and bare `int`                     | Scalar for `*` / `/`; reject unit-bearing `+` / `-`                        |
| No runtime enforcement                         | Document: static-only unless user also uses `Quantity`                     |

This is harder than “only analyse `Quantity` methods,” but it matches the intended PHPStan use.

### Internal PHPStan representation

```text
UnitMagnitudeType   // name TBD
  - phpNumber: int | float | number | mixed-unknown
  - unitExpr: Expr | null    // reduced symbolic unit; null = unknown unit

QuantityType         // optional object path
  - numberKind: Rational (runtime today)
  - unitExpr: Expr | null
```

Avoid one PHP class per unit. Unit identity is always Yumemi `Expr` from unit strings.

**Unknown unit** (non-constant string):

- `unitExpr = null`
- Operations stay unknown or degrade; do not invent units

## Annotation Surface: extension-required vs extension-optional

Two ways to attach a unit, chosen by whether consumers must install the extension.

### Extension-required (native type position)

`unit_int<'…'>` / `unit_float<'…'>` work in any normal PHPDoc type position — `@param`, `@return`, `@var`, and inside
compound types (`list<unit_int<'foot'>>`, `unit_int<'foot'>|null`, `array<string, unit_float<'meter'>>`).
`UnitTypeNodeResolverExtension` resolves them wherever PHPStan parses a type.

```php
/** @param unit_int<'foot'> $height */
```

Cost: **the extension is required.** Without our resolver, PHPStan treats `unit_int` as an unknown class/type and errors
in consumer code. Use only in first-party projects that mandate the extension.

### Extension-optional (vendor-prefixed tag)

For public APIs that only _optionally_ support Yumemi, pair the ordinary native tag with a vendor-prefixed one:

```php
/**
 * @param int $length
 * @yumemi-param unit_int<'foot'> $length
 * @yumemi-return unit_int<'square-foot'>
 */
function area(int $length): int { /* ... */ }
```

Promotion is a separate, opt-in integration. Load it after the main extension (which the extension installer may have
already loaded):

```neon
includes:
    - vendor/jbboehr/yumemi/yumemi-tags.neon
```

Without this config, PHPStan preserves the annotations as unknown generic tags and uses the ordinary `@param` /
`@return` / `@var` or native signature. With it, the parser promotes `@yumemi-param`, `@yumemi-return`, and
`@yumemi-var` into the corresponding PHPStan type positions before reflection and analysis. From that point onward
PHPStan owns propagation, inheritance, call checking, method returns, properties, locals, and ordinary diagnostics. This
is deliberately true replacement: a bare `int` does not satisfy a promoted `unit_int<'meter'>` parameter.

The parser integration is off by default because it replaces internal PHPStan parser services and could conflict with
another extension that replaces the same services. Its narrow intended use is a library embedding optional Yumemi
annotations in source it controls. Application code should normally use Yumemi types directly in standard PHPDoc, and
support for a third-party library should normally be supplied through standard PHPStan stubs.

If a fallback tag exists, promotion is allowed only when the Yumemi type is its exact structural unit transform:

- erase every `unit_int<'...'>` leaf to `int`, every `unit_float<'...'>` to `float`, and every `Quantity<'...'>` to the
  same `Quantity` base name;
- recurse through nullable, union, intersection, and generic types;
- normalize nullable spelling, union/intersection ordering, flattening, and duplicate members before comparison;
- for parameters, require the reference and variadic markers to match too;
- prefer `@phpstan-param` / `@phpstan-return` / `@phpstan-var` over the corresponding ordinary tag.

On success only the fallback's type node is replaced, preserving its variable name, markers, and description. On a
mismatch the fallback remains effective and `yumemi.docTagTransform` is reported. With no fallback, the custom tag is
promoted directly and PHPStan checks it against the native declaration. Malformed payloads, invalid units, unknown
parameters, duplicates, unsupported targets, and ambiguous unnamed `@var` fallbacks receive their own diagnostics.

| Environment                                    | Effective type                      |
| ---------------------------------------------- | ----------------------------------- |
| Main extension + `yumemi-tags.neon`            | promoted Yumemi unit type           |
| Main extension only (the default)              | ordinary fallback/native type       |
| No extension (PHPStan / IDE / phpDocumentor)   | ordinary fallback/native type       |
| Third-party consumer without the parser opt-in | no hard dependency on the extension |

### Third-party libraries you don't control

Use normal PHPStan **stub files** rather than editing foreign source. Write `unit_int<'...'>`, `unit_float<'...'>`, or
`Quantity<'...'>` directly in the stub's standard `@phpstan-param`, `@phpstan-return`, or `@phpstan-var` tags; only the
main extension is needed to resolve those types, so parser promotion is unnecessary. Stub declarations must match the
real namespace/class/method/parameters; native types written only in the stub are ignored, so keep matching native
signatures for readability.

If a stub deliberately contains `@yumemi-*` tags, the opt-in config also promotes those because it wraps the stub
parser. This is supported for consistency but is usually needless indirection in a file that already requires Yumemi.

```neon
parameters:
    stubFiles:
        - stubs/some-geometry-library.stub
```

### Implementation notes

- `extension.neon` does not replace any PHPStan parser services. The separate `yumemi-tags.neon` config registers the
  promoter and its diagnostics, then replaces the analysis and stub parser cache services.
- When opted in, `YumemiTagPromotingParser` decorates PHPStan's `pathRoutingParser` before the normal analysis cache, so
  it runs after PHPStan chooses the rich parser for analyzed files or the simple parser for reflection-only
  dependencies. A second instance decorates `freshStubParser` before the stub cache.
- `YumemiDocTagPromoter` reparses the custom payload as the corresponding `@phpstan-*` tag using PHPStan's configured
  PHPDoc parser. This supports the full PHPDoc grammar instead of maintaining a second parser for unions, nullable
  types, generics, descriptions, references, or variadics.
- Validity still flows through `TypeStringResolver`, and therefore through `UnitTypeNodeResolverExtension` and the one
  shared unit-expression parser.
- The parser service names and `PhpDocStringResolver` call are internal PHPStan seams. That coupling is localized in the
  optional wrapper/promoter and guarded by analyzed-source, dependency-reflection, stub-parser, and default-off
  integration tests.

References: PHPStan docs — Custom PHPDoc Types, Stub Files, Stub Files Extensions, Reflection.

## Vertical Slices (Implementation Order)

Ship thin end-to-end slices. Prefer proving **native unit types** early, since that is the main PHPStan audience.
Runtime `Quantity` support can follow or trail slightly.

### Slice 1 — Native unit PHPDoc + validation

**Goals:**

- Resolve `unit_int<'meter'>` / `unit_float<'meter / second'>` (or `UnitValue<int, '…'>`)
- Error on unknown units, bad syntax, unsupported affine forms
- Store reduced unit `Expr` + php number kind on the custom type

**Runtime engine:** default or configured registry → parse/resolve → reduce.

**Tests:** fixture files + type inference / rule tests.

This proves “Yumemi engine behind native static types.”

### Slice 2 — Arithmetic on native unit types

| Op                                     | Rule (sketch)                                                     |
| -------------------------------------- | ----------------------------------------------------------------- |
| `+` / `-` on native unit types         | Same normalized unit; result keeps the left unit                  |
| `*` / `/`                              | Combine unit exprs; promote `int`×`float` → `float` per PHP rules |
| `*` / `/` by bare dimensionless number | Preserve unit                                                     |
| Unsafe cast to bare `int`/`float`      | Drop unit or error (config)                                       |

Use PHPStan operator type extensions where possible.

### Slice 3 — Runtime `Quantity` interop (optional but useful)

- PHPDoc `Quantity<'meter'>` → object type with Rational + unit
- `Units::quantity(1, 'meter')` return type
- `value()` / `intValueIn` / `to` bridging native unit types ↔ `Quantity` if desired

### Slice 4 — `add` / `sub` policy

Native `unit_int` / `unit_float` addition and subtraction require the same normalized unit, including scale. Merely
compatible dimensions are insufficient: PHPStan cannot insert the magnitude conversion needed to make raw `meter + foot`
arithmetic correct. Runtime `Quantity::add()` / `sub()` can accept compatible dimensions because those methods perform
the exact conversion; `addWithSameUnit()` / `subWithSameUnit()` remain the no-conversion variants.

### Slice 5 — Extension config

```neon
parameters:
    yumemi:
        registryFactory: App\PHPStan\YumemiRegistryFactory
```

The configured class implements `UnitRegistryFactory` and returns the complete `UnitRegistry`. It can reuse
`UnitRegistryBuilder::default()->define(...)->build()` to extend UDUNITS2 or start from `empty()` for an isolated
catalog. The registry is constructed once, shared across extension services, and fingerprinted through PHPStan's result
cache metadata API.

## Package Layout

In-tree first (acceptable for this package):

```text
src/PHPStan/
  UnitMagnitudeType.php   # native int|float × unit
  QuantityType.php        # optional object path
  ...
extension.neon             # automatically registered core extension
yumemi-tags.neon           # opt-in @yumemi-* parser promotion
```

Composer:

- Autoload PHPStan classes with the library
- `extra.phpstan.includes` → `extension.neon` when published
- Do not automatically include `yumemi-tags.neon`; consumers opt in explicitly when they need parser promotion
- Do **not** hard-require `phpstan/phpstan` at runtime for app code; only when the extension is loaded

## What To Reuse (Do Not Fork)

| Runtime                           | PHPStan use                               |
| --------------------------------- | ----------------------------------------- |
| `UnitResolver` / `Units::parse`   | Validate PHPDoc and constant unit strings |
| `ExprReducer` / `ExprComparer`    | Equality, mul/div unit results            |
| `Dimension` / `DimensionResolver` | Dimensional add/sub mode                  |
| `UnitRegistryBuilder`             | Configured catalogs / custom defines      |
| `ExprFormatter`                   | Human-readable error messages             |
| `ConversionFactorResolver`        | Optional const-fold of conversions later  |
| Domain exceptions                 | Map to stable PHPStan error identifiers   |

## Testing Strategy

1. **Type construction** — good/bad unit strings → native unit type or error
2. **Type inference fixtures** — `unit_int` arithmetic, PHPDoc, assignments
3. **Rule tests** — incompatible add, unknown unit, unsafe cast
4. **Quantity fixtures** — secondary path once native types work

Follow PHPStan extension testing patterns (`TypeInferenceTestCase`, `RuleTestCase`, or current equivalents for the
PHPStan major version in use).

> **Coverage caveat:** `TypeInferenceTestCase` (`assertType`) fixtures and the `shell_exec`-based integration tests run
> PHPStan **out-of-process**, so PCOV attributes no line coverage to the extension classes they exercise (e.g.
> `QuantityMethodReturnTypeExtension`, `QuantityType`). Line coverage on `src/PHPStan/` therefore understates the real,
> functionally strong coverage and must not be treated as a quality gate. Where a class has pure, container-free logic
> (`UnitExpressionAlgebra`, the branded `QuantityType` / `UnitIntegerType` / `UnitFloatType` semantics), add an
> in-process `TestCase` / `PHPStanTestCase` unit test as well so the behavior is both asserted and measured.

## Non-Goals For Early Versions

| Non-goal                                    | Why                                              |
| ------------------------------------------- | ------------------------------------------------ |
| Changing runtime `Quantity` off Rational    | Exact library math stays Rational-only           |
| Full flow-sensitive multi-registry tracking | Hard; use the one statically configured catalog  |
| Perfect recovery through every PHP builtin  | Start with ops that matter; wipe unit on unknown |
| Affine temperature / logarithmic units      | Unsupported in runtime semantics today           |
| Perfect Pint parity                         | Different product                                |
| Class-per-unit types                        | Rejected design; use unit strings + Expr         |
| Special-casing `pa` vs `Pa`                 | Case-sensitive catalog; document, don’t invent   |

## Runtime Readiness

The runtime is in good shape for static work after the review follow-ups:

- Fail-closed unit resolution
- Facade reduction, structural equality, dimension API
- Immutable registries + builder + string `define`
- Shared analysis services on `Units`

Optional polish (not blockers):

- Document case-sensitive unit names for static users
- Circular-alias regression test if desired

## Implementation Progress

### Piece 1 — unit expression bridge (done)

- `PHPStan\UnitExpressionParser` parses unit strings via Yumemi `Units`
- `UnitExpression` / `UnitExpressionParseResult` carry reduced expr, display string, dimension
- `extension.neon` registers the parser service
- Covered by `tests/PHPStan/UnitExpressionParserTest`

### Piece 2 — native unit types + PHPDoc resolver (done)

- `UnitIntegerType` / `UnitFloatType` extend PHPStan int/float with unit identity
- `UnitTypeNodeResolverExtension` resolves `unit_int<'…'>` and `unit_float<'…'>`
- Invalid units become `ErrorType` with Yumemi error messages (e.g. unknown `mass`)
- Covered by type unit tests + PHPStan CLI integration fixtures

### Piece 3 — arithmetic operator inference (done)

- `UnitOperatorTypeSpecifyingExtension` infers `+ - * / ** %` when at least one operand is a unit type;
  `UnitUnaryOperatorTypeSpecifyingExtension` handles unary `+` / `-`
- `+` / `-` require normalized-equivalent units (exact scale, not merely same dimension); `*` / `/` combine unit exprs
  via the Yumemi `Expr` algebra; `**` requires a constant integer exponent; `%` is restricted to `unit_int` operands
  with equivalent units (PHP integer modulo)
- `int` × `float` promotion and `/`-always-float follow native PHP rules
- Covered by `UnitOperatorTypeSpecifyingExtensionTest`, `UnitUnaryOperatorTypeSpecifyingExtensionTest`,
  `UnitMagnitudeTypeTest`, and the `unit-ops.php` CLI integration fixture

### Piece 4 — `unit()` construction helper (done)

- `UnitFunctionDynamicReturnTypeExtension` infers `unit_int<'…'>` / `unit_float<'…'>` from `unit($value, 'meter')` when
  the unit string is constant
- Invalid unit strings yield an `ErrorType` (with reason); non-constant strings fall back to the native signature so
  unrelated code is not poisoned

### Piece 5 — `unit_to()` conversion helper (done)

- `UnitToFunctionDynamicReturnTypeExtension` infers `unit_float<'to'>` from `unit_to($value, 'from', 'to')`, always
  float (conversion factors are generally non-integral)
- Checks that a branded value's unit matches `from`, and that `from` / `to` share a dimension; `ErrorType` (with reason)
  otherwise

### Piece 6 — invalid-call diagnostics (done)

- `InvalidUnitCallRule` reports standalone `yumemi.invalidUnitCall` diagnostics for invalid `unit()` / `unit_to()`
  calls, independent of whether the result is later used
- Reuses the extensions' shared `inferType(FuncCall, Scope): ?Type` and surfaces its `getReason()`, so validation and
  messages stay a single source of truth
- Covered by `InvalidUnitCallRuleTest`
- Note: binary-operator misuse (e.g. `meter + second`) is already diagnosed by PHPStan core's
  `InvalidBinaryOperationRule` (`binaryOp.invalid`), so no sibling operator rule was added

### Piece 7 — `Quantity<'…'>` object type + method inference (done)

The runtime `Quantity` object path — the original headline goal. Code that opts into the value object gets the same
dimensional checking as the native `unit_int` / `unit_float` path.

- `QuantityType` is the branded object type. `Quantity<'meter / second'>` (sugar for `Quantity<Rational, '…'>`) is
  resolved wherever PHPStan parses a type by the same `UnitTypeNodeResolverExtension` that handles the native types —
  one parser, one meaning
- `UnitsQuantityReturnTypeExtension` (a `DynamicMethodReturnTypeExtension` on `Units::quantity()`) infers
  `Quantity<'meter'>` from `Units::quantity($value, 'meter')` when the unit string is constant
- `QuantityMethodReturnTypeExtension` carries the unit through the fluent method chain on a branded receiver: `mul` /
  `div` combine unit exprs via the shared `UnitExpressionAlgebra`; `pow` raises by a constant integer; `neg` keeps its
  unit; converting `add` / `sub` require compatible dimensions; `addWithSameUnit` / `subWithSameUnit` require
  normalized-equivalent units; all four binary methods keep the left unit; `to` validates and rebrands to the constant
  target; integer extraction methods return a target-branded `unit_int`; and `normalize` / `simplify` rebrand to the
  catalog-normalized form, with `simplify` removing the scale constant that the runtime method folds into the magnitude
- **No dedicated assignment/argument rule is needed:** `QuantityType::accepts()` plus PHPStan core's `CallMethodsRule`
  already reject a `Quantity<'foot'>` passed where `Quantity<'meter'>` is expected
- Genuinely dynamic targets and unbranded unit-combining operands fail open to native returns; constant targets are
  validated against the authoritative configured registry
- Covered by `QuantityReturnTypeExtensionTest`, `QuantityArgumentTypeRuleTest`, and the `quantity-assert.php` fixture
- Every current unit-bearing fluent method is now branded, including `simplify()`.

### Pieces 8–9 — first extension-optional param/return implementation (superseded)

The first implementation read generic tags from resolved reflection PHPDoc, used a dynamic function-return extension,
and maintained a custom call-site rule for parameters. It proved the annotation surface, but it duplicated PHPStan's
reflection and argument-mapping logic, covered function returns but not method returns, did not support `@yumemi-var`,
and intentionally allowed bare native arguments. Piece 11 added separate declaration-validation rules. The unified
parser-promotion implementation below replaces all of those components and semantics.

### Piece 10 — `Quantity` addition/subtraction policies and diagnostics (done)

- Runtime `Quantity::add()` / `sub()` convert compatible right operands exactly into the left unit; `addWithSameUnit()`
  / `subWithSameUnit()` retain the former no-conversion behavior
- `QuantityMethodReturnTypeExtension` validates the corresponding dimension or exact-unit precondition and preserves the
  receiver's brand
- `InvalidQuantityArithmeticRule` surfaces invalid branded calls as `yumemi.invalidQuantityArithmetic`, including when
  the result is unused; unbranded operands continue to fail open

### Piece 11 — parser-level promotion for the full `@yumemi-*` family (done)

- The opt-in `yumemi-tags.neon` config makes `YumemiTagPromotingParser` wrap both analysis path routing and stub parsing
  before their caches. Promotion therefore reaches rich-parsed project files, simple-parsed reflection dependencies, and
  configured `.stub` files without making those parser replacements part of the default extension.
- `YumemiDocTagPromoter` parses each custom payload through PHPStan's full PHPDoc parser. Valid tags either replace the
  matching fallback type node or become `@phpstan-param`, `@phpstan-return`, or `@phpstan-var` when no fallback exists.
- Fallback matching recursively erases unit leaves and compares normalized PHPDoc structures. It supports complex
  nullable/union/intersection/generic expressions instead of limiting the fallback to one native scalar.
- PHPStan's native machinery now handles argument mapping, bare-native rejection, inheritance, function and method
  returns, and local/property `@var` propagation. The former dynamic-return and custom call-site extensions were
  removed.
- `YumemiTagPromotionRule` reports cases where promotion safely declined, with stable identifiers for syntax, type,
  target, parameter, duplicate, and exact-transform errors. On failure, any existing fallback remains effective.
- Covered by `YumemiTagPromotionRuleTest` and `YumemiReturnTagExtensionTest`, including composite exact transforms,
  PHPStan-tag priority, ref/variadic mismatches, method returns, locals, bare-native enforcement, base-only/default-off
  behavior, dependency reflection, and stub parsing.

### Piece 12 — configured PHPStan registries (done)

- `parameters.yumemi.registryFactory` accepts a class implementing `UnitRegistryFactory`; `null` retains UDUNITS2.
- The factory returns the complete registry and is invoked once. A shared `Units` context feeds PHPDoc resolution,
  helpers, native operators, `Quantity` inference, and optional Yumemi-tag promotion.
- Registry names, records, prebuilt units, and prefixes are fingerprinted through PHPStan's result-cache metadata API,
  so external catalog changes invalidate cached analysis.
- Missing or invalid factory classes and construction failures stop analysis with configuration-specific messages.

### Piece 13 — `Quantity` boundary soundness and native bridges (done)

- `Units::quantity()` accepts a branded `unit_int` magnitude only when its unit is normalized-equivalent to the target;
  the constructor labels an existing magnitude and does not convert it.
- `Quantity::to()`, `valueIn()`, `intValueIn()`, and `exactIntValueIn()` reject statically known dimension mismatches.
- `intValueIn()` and `exactIntValueIn()` infer `unit_int<'target'>`; Rational-returning accessors remain ordinary
  `Rational` rather than introducing a separate `unit_rational` type.
- `InvalidQuantityConstructionRule` and `InvalidQuantityConversionRule` report unused invalid calls with
  `yumemi.invalidQuantityConstruction` and `yumemi.invalidQuantityConversion`.
- Dynamic targets and unit-combining operations with unknown units continue to fail open.

### Piece 14 — finite `Quantity` targets and an authoritative registry (done)

- A finite union of constant strings is analysed one alternative at a time by `Units::quantity()` and by
  `Quantity::to()`, `valueIn()`, `intValueIn()`, and `exactIntValueIn()`.
- Result-bearing methods return a union of branded target types. Every possible target must be valid, every conversion
  from a branded receiver must be dimensionally compatible, and a branded integer passed to `Units::quantity()` must be
  normalized-equivalent to every possible target.
- An unbranded `Quantity` with an explicit known target now produces a branded `to()` or integer-extraction result. Its
  source compatibility cannot be checked because the receiver carries no static source unit.
- The configured PHPStan registry is authoritative. Unknown constant targets are diagnostics; only genuinely dynamic
  strings fall back to native return types. Runtime code should use the same catalog. Distinguishing several runtime
  registry identities would require carrying a registry identity on every branded type and is deliberately deferred.
- Literal-union support remains limited to the `Quantity` boundary APIs. Extending `unit()` is straightforward, but
  `unit_to()` has independent source and target unions: blindly forming their Cartesian product loses value correlation
  and makes it unclear whether validation should require every pair or merely one pair.

### Next pieces

1. **Bundled stubs for selected third-party libraries** — these should normally use standard PHPStan tags containing
   Yumemi types and therefore need no parser promotion. What remains is deciding which integrations merit bundled stubs
   and registering those files through a `StubFilesExtension`.
2. **Richer identifiers / messages elsewhere** — Pieces 11 and 13 have stable per-cause identifiers; other extension
   diagnostics can be split further where callers need more precise suppression.

**Success criterion (piece 2):** `unit_int<'mass'>` errors; `unit_int<'meter / second'>` is a real type. **(met)**

> **Update 2026-07-26:** Piece 7 (the runtime `Quantity<'…'>` object path — `Quantity<'…'>` PHPDoc resolution,
> `Units::quantity()` inference, and fluent-method inference through `mul` / `div` / `pow` / `neg` / `add` / `sub` /
> `to` / `normalize`) landed in commits `7b8b759` and `64786af`; `simplify()` inference has since completed the current
> unit-bearing fluent-method surface.

> **Update 2026-07-27:** Piece 12 adds typed custom-registry configuration and cache invalidation. The proposed native
> dimension-only addition mode was dropped because it cannot convert ordinary PHP numeric magnitudes and would therefore
> be unsound. Piece 13 closes the native/`Quantity` boundary by checking branded construction and conversion targets and
> branding integer extraction results.

## Later Milestones

1. ~~Operator inference for `+` `-` `*` `/` on native unit types~~ **(done — Piece 3)**
2. ~~Runtime `Quantity` PHPDoc + method inference (Rational × unit)~~ **(done — Piece 7)**
3. ~~Bridges between native unit types and `Quantity`~~ **(done — Piece 13)**
4. ~~Neon config for custom catalogs~~ **(done — Piece 12)**
5. Richer messages / structured identifiers

> **Update 2026-07-27:** The core native and `Quantity` paths, configured catalogs, and boundary bridges are complete;
> see "Next pieces" for the remaining integration and diagnostic polish.

## Summary Slogans

- **One unit engine (Yumemi). Two presentation layers (Rational `Quantity` vs native static types).**
- **Runtime stays exact (`Rational`). PHPStan tracks real PHP numbers with units.**
- **Static unit types are `(native number kind × unit expression)`.**
- **PHPStan reuses Yumemi; it does not reimplement units or replace the runtime.**
