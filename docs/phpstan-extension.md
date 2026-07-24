# PHPStan Extension Plan

Snapshot of design discussion for static dimensional analysis in IMM (2026-07-24).

Related: [planning.md](planning.md), [grok-review.md](grok-review.md), [pint-parity.md](pint-parity.md).

## Goal

Ship a PHPStan extension that catches unit mistakes at analysis time:

- Invalid or unknown unit strings in PHPDoc and constant arguments
- Incompatible `add` / `sub`
- Useful return types for `mul` / `div` / `to` / `normalize` / `simplify`

## Runtime vs static product split

These are related but not the same product surface:

| Layer                  | Magnitude model                                     | Primary audience                         |
| ---------------------- | --------------------------------------------------- | ---------------------------------------- |
| **Runtime `Quantity`** | **`Rational` only** (exact conversion)              | Code that opts into the IMM value object |
| **PHPStan unit types** | **Native magnitudes** (`int`, `float`, …) plus unit | App code that stays on native PHP types  |

It is fine — and intentional — that runtime storage stays Rational-only while static analysis
tracks `int` / `float` (and later other natives) with units. Most PHPStan users will annotate
and check **native-typed** variables and parameters; they may never construct a runtime
`Quantity`.

IMM runtime still supplies the **unit engine** (parse, resolve, reduce, dimension, convert
factors). PHPStan attaches that engine to native types and optional `Quantity` types.

## Guiding Principle

> PHPStan reuses IMM’s unit engine; it does not require every analysed value to be a runtime
> `Quantity`.

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
    → IMM parse / resolve / reduce / compare
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

These describe **PHPStan-level** magnitudes. At runtime the variable is still a plain `int` or
`float` unless the user also wraps a `Quantity`.

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

`Quantity` annotations default number kind to **`Rational`**. They are not required for the
native-type analysis path.

### Why both layers

| Need                                                     | Mechanism                                                        |
| -------------------------------------------------------- | ---------------------------------------------------------------- |
| Exact library math / conversion                          | Runtime `Quantity` + `Rational`                                  |
| Analyse ordinary PHP (`int`/`float` APIs, loops, arrays) | Static `unit_int` / `unit_float` (or `UnitValue<int\|float, U>`) |
| Unit algebra (mul/div/add checks)                        | Shared IMM `Expr` + dimension engine                             |

Codex-style pushback on **`intWithUnit` as a second runtime world** still applies: do not invent
a parallel library of instrumented ints. Do use **PHPStan types** that behave like `int`/`float`
with an attached unit for analysis.

### Challenges of native unit types (accepted tradeoffs)

| Challenge                                      | Mitigation                                                                 |
| ---------------------------------------------- | -------------------------------------------------------------------------- |
| Brands strip on cast / `(int)` / some builtins | Rules for common safe ops; error or wipe unit on unsafe casts              |
| `+` / `*` on two unit values                   | Operator type extensions: same unit for `+`/`-`; combine units for `*`/`/` |
| Mixing unit and bare `int`                     | Configurable: error, or treat bare as dimensionless                        |
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

Avoid one PHP class per unit. Unit identity is always IMM `Expr` from unit strings.

**Unknown unit** (non-constant string):

- `unitExpr = null`
- Operations stay unknown or degrade; do not invent units

## Vertical Slices (Implementation Order)

Ship thin end-to-end slices. Prefer proving **native unit types** early, since that is the main
PHPStan audience. Runtime `Quantity` support can follow or trail slightly.

### Slice 1 — Native unit PHPDoc + validation

**Goals:**

- Resolve `unit_int<'meter'>` / `unit_float<'meter / second'>` (or `UnitValue<int, '…'>`)
- Error on unknown units, bad syntax, unsupported affine forms
- Store reduced unit `Expr` + php number kind on the custom type

**Runtime engine:** default or configured registry → parse/resolve → reduce.

**Tests:** fixture files + type inference / rule tests.

This proves “IMM engine behind native static types.”

### Slice 2 — Arithmetic on native unit types

| Op                                     | Rule (sketch)                                                     |
| -------------------------------------- | ----------------------------------------------------------------- |
| `+` / `-`                              | Same unit (exact) or same dimension (config); result keeps unit   |
| `*` / `/`                              | Combine unit exprs; promote `int`×`float` → `float` per PHP rules |
| `*` / `/` by bare dimensionless number | Preserve unit                                                     |
| Unsafe cast to bare `int`/`float`      | Drop unit or error (config)                                       |

Use PHPStan operator type extensions where possible.

### Slice 3 — Runtime `Quantity` interop (optional but useful)

- PHPDoc `Quantity<'meter'>` → object type with Rational + unit
- `Units::quantity(1, 'meter')` return type
- `value()` / `intValueIn` / `to` bridging native unit types ↔ `Quantity` if desired

### Slice 4 — `add` / `sub` policy config

1. **Exact** (default; align with runtime quantity arithmetic): same reduced symbolic unit
2. **Dimension**: same `Dimension`

### Slice 5 — Extension config

```neon
parameters:
    imm:
        # catalog: default UDUNITS2, or builder recipe / defines later
        arithmetic: exact   # or dimension
        # bare_numeric: allow | dimensionless | forbid
```

Custom units should reuse `UnitRegistryBuilder::default()->define(...)->build()`.

## Package Layout

In-tree first (acceptable for this package):

```text
src/PHPStan/
  UnitMagnitudeType.php   # native int|float × unit
  QuantityType.php        # optional object path
  ...
extension.neon
```

Composer:

- Autoload PHPStan classes with the library
- `extra.phpstan.includes` → `extension.neon` when published
- Do **not** hard-require `phpstan/phpstan` at runtime for app code; only when the extension is
  loaded

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

Follow PHPStan extension testing patterns (`TypeInferenceTestCase`, `RuleTestCase`, or current
equivalents for the PHPStan major version in use).

## Non-Goals For Early Versions

| Non-goal                                    | Why                                              |
| ------------------------------------------- | ------------------------------------------------ |
| Changing runtime `Quantity` off Rational    | Exact library math stays Rational-only           |
| Full flow-sensitive multi-registry tracking | Hard; start with default catalog                 |
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

- `PHPStan\UnitExpressionParser` parses unit strings via IMM `Units`
- `UnitExpression` / `UnitExpressionParseResult` carry reduced expr, display string, dimension
- `extension.neon` registers the parser service
- Covered by `tests/PHPStan/UnitExpressionParserTest`

### First full milestone (next pieces)

1. Native unit magnitude PHPStan `Type` (`int`/`float` × `UnitExpression`)
2. PHPDoc resolver for `unit_int<'meter'>` / `unit_float<'…'>` (names TBD)
3. Errors for invalid / unknown unit strings in PHPDoc
4. One green inference or rule fixture suite

**Success criterion:** `unit_int<'mass'>` (or similar false friend) is a hard PHPStan error, and
`unit_int<'meter / second'>` is a real type carrying that unit on an int-like magnitude.

## Later Milestones

1. Operator inference for `+` `-` `*` `/` on native unit types
2. Exact vs dimension arithmetic mode
3. Runtime `Quantity` PHPDoc + method inference (Rational × unit)
4. Bridges between native unit types and `Quantity`
5. Neon config for catalog and policies
6. Richer messages / structured identifiers

## Summary Slogans

- **One unit engine (IMM). Two presentation layers (Rational `Quantity` vs native static types).**
- **Runtime stays exact (`Rational`). PHPStan tracks real PHP numbers with units.**
- **Static unit types are `(native number kind × unit expression)`.**
- **PHPStan reuses IMM; it does not reimplement units or replace the runtime.**
