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

PHPStan preserves unknown tags as generic PHPDoc nodes and otherwise ignores them, so consumers without the extension —
plus IDEs and phpDocumentor — see only native `int`: an unfamiliar tag, never a nonexistent type. This is PHPStan's
documented mechanism for custom PHPDoc metadata.

Tag family: `@yumemi-param`, `@yumemi-return`, `@yumemi-var` (one namespaced family; not `@phpstan-yumemi-param` — there
is no conditional-tag mechanism, so it would just be another unknown tag either way).

| Environment                                  | Sees                                |
| -------------------------------------------- | ----------------------------------- |
| Extension installed                          | `int` **plus** dimensional check    |
| No extension (PHPStan / IDE / phpDocumentor) | plain `int`; unknown tag ignored    |
| Third-party consumer                         | no hard dependency on the extension |

### Third-party libraries you don't control

Ship **stub files** from the extension rather than editing foreign source. A bundled `StubFilesExtension` (tagged
`phpstan.stubFilesExtension`) auto-registers `.stub` files carrying `@yumemi-*` tags, so consumers don't hand-add
`parameters.stubFiles`. Stub declarations must match the real namespace/class/method/params; native types written only
in the stub are ignored, so keep matching native signatures for readability.

```php
final class YumemiStubFilesExtension implements StubFilesExtension
{
    public function getFiles(): array
    {
        return [__DIR__ . '/../stubs/some-geometry-library.stub'];
    }
}
```

### Implementation notes (for when this slice lands)

- Unknown tags arrive as `GenericTagValueNode`; read via `PhpDocNode::getTagsByName('@yumemi-param')`.
- Read from the **resolved** PHPDoc attached to reflection (`ExtendedMethodReflection::getResolvedPhpDoc()`; for
  functions route the doc comment through `FileTypeMapper::getResolvedPhpDoc()`), **not** the raw source doc comment —
  otherwise stub-contributed tags are invisible.
- A `TypeNodeResolverExtension` alone will **not** parse the type inside `@yumemi-param`: the whole tag is generic text,
  and PHPStan only invokes the type resolver in recognized type positions. Parse the `unit_int<'foot'> $length` payload
  yourself — feed the type part through `TypeStringResolver` so it reaches the existing resolver and `unit_int<'…'>`
  keeps exactly one parser and one meaning. Reuse `UnitExpressionParser`; do not add a second unit grammar.

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
    yumemi:
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

### Piece 8 — extension-optional `@yumemi-return` for functions (done)

First slice of the extension-optional annotation surface (see "Annotation Surface"). A function keeps a native return
type in its signature and adds `@yumemi-return unit_int<'foot'>`; when the extension is loaded, call sites see the
branded unit type.

- `YumemiDocTagReader` reads the vendor-prefixed tags (`@yumemi-return` today; `@yumemi-param` / `@yumemi-var` reserved)
  from a `ResolvedPhpDocBlock`. Unknown tags survive as `GenericTagValueNode`; the type payload is re-parsed through
  PHPStan's `TypeStringResolver` so it reaches `UnitTypeNodeResolverExtension` — one parser, one meaning
- Only branded unit types are honoured; a native type or an invalid-unit `ErrorType` is treated as absent, so a tag
  never poisons unrelated analysis (fail-open, matching `UnitsQuantityReturnTypeExtension`)
- `YumemiReturnTagFunctionReturnTypeExtension` (a `DynamicFunctionReturnTypeExtension`, covering every function via
  `isFunctionSupported`) resolves the callee's doc comment via `FileTypeMapper` and brands the return
- **Fast path:** because `isFunctionSupported` runs on every function, a `str_contains($docComment, '@yumemi-return')`
  guard short-circuits before any phpdoc resolution/scan for the overwhelmingly common no-tag case
- Covered by `YumemiReturnTagExtensionTest` — a `TypeInferenceTestCase` matrix (the annotated functions are `require`d
  into the process, since the harness does not index functions local to the analysed fixture), plus a CLI enforcement
  fixture proving the brand flows into core `argument.type` checking, not just `assertType`
- **Deferred:** `@yumemi-return` on object methods (blocked by PHPStan's per-class `getClass()` dynamic-return hook);
  `@yumemi-var`; a validation rule surfacing invalid-unit tag payloads (currently silently ignored); bundled stub files
  for third-party libraries

### Piece 9 — extension-optional `@yumemi-param` argument checking (done)

The caller side of graceful degradation. A function/method keeps a native parameter type and declares the intended unit
with `@yumemi-param unit_int<'meter'> $length`; branded arguments carrying the wrong unit are reported at the call site.

- `YumemiDocTagReader::paramTypes()` parses the `<type> $name` payloads (reusing the same `TypeStringResolver` route),
  keyed by parameter name; only branded unit payloads are kept
- `YumemiParamTagRule` is registered on `PhpParser\Node\Expr\CallLike`, so one rule covers function calls, instance and
  static method calls, and `new` (constructor) calls (PHPStan's `LazyRegistry` dispatches a node to rules registered on
  any of its parent classes). Each subtype resolves its callee reflection — function via `ReflectionProvider`, instance
  method via the receiver type, static method via `resolveTypeByName()`, constructor via `ClassReflection` — then shares
  one tail that maps positional and named arguments to parameter names via `ParametersAcceptorSelector` and checks each
  branded argument with the expected type's `accepts()`
- **Only branded arguments are checked**: a bare native value is the graceful escape hatch and passes silently; a
  branded value with an incompatible unit yields a `yumemi.paramType` error carrying the `accepts()` reason as the tip
- **Fast path:** a `str_contains($docComment, '@yumemi-param')` guard skips phpdoc resolution/scan for the common no-tag
  case — unconditionally for functions (no inheritance), and for methods only when they do not inherit phpdoc. A method
  that overrides a parent or implements an interface can inherit the tag from an ancestor (`getResolvedPhpDoc()`
  resolves it), so it always takes the full path; the split is decided by comparing `getPrototype()`'s declaring class
- Covered by `YumemiParamTagRuleTest` (a `RuleTestCase` asserting exact message + line + tip for function, instance
  method, static method, and constructor calls, positional and named args; the annotated free function and inheritance
  types are `require`d into the process, the rest are PSR-4 autoloaded), including an inheritance regression fixture
  proving a tag inherited by a doc-less override / interface implementation is still checked (i.e. the fast path never
  skips it)
- **Deferred:** dynamic (`$class::m()`, `new $class()`) and anonymous-class targets are left unresolved; a stricter
  opt-in mode that also rejects bare-native arguments at `@yumemi-param` positions

### Next pieces

1. **Runtime `Quantity` PHPDoc + method inference** — the object path is still untouched. Resolve
   `Quantity<'meter / second'>` (sugar for `Quantity<Rational, '…'>`), infer `Units::quantity()`, and infer `to()` /
   `mul()` / `div()` / `normalize()` / `simplify()` plus `add()` / `sub()` checks. This is the original headline goal
   and the largest remaining gap.
2. **Exact vs dimension arithmetic mode + neon config** — only exact-unit is implemented today; add the relaxed
   dimension mode and a `parameters.yumemi` config shape (arithmetic mode, catalog, bare-numeric policy).
3. **Richer identifiers / messages** — stable per-cause error identifiers beyond the current `yumemi.invalidUnitCall`.
4. **Extension-optional annotations** — `@yumemi-param` / `@yumemi-return` / `@yumemi-var` graceful-degradation tags
   plus bundled stub files for third-party libraries (see "Annotation Surface"). Distinct from the mandatory
   native-position path already shipped.

**Success criterion (piece 2):** `unit_int<'mass'>` errors; `unit_int<'meter / second'>` is a real type. **(met)**

> **Update 2026-07-25:** Pieces 3–6 are complete (commits `343027f`, `185d01f`, `515e722`, `a2180ed`, and the
> invalid-call rule). The native `unit_int` / `unit_float` path — PHPDoc resolution, validation, operator inference,
> `unit()` / `unit_to()` helpers, assignment checks via the branded types' `accepts()`, and standalone invalid-call
> diagnostics — is now in place. What remains is the runtime `Quantity` object path and the exact-vs-dimension config
> work.

## Later Milestones

1. ~~Operator inference for `+` `-` `*` `/` on native unit types~~ **(done — Piece 3)**
2. Exact vs dimension arithmetic mode
3. Runtime `Quantity` PHPDoc + method inference (Rational × unit)
4. Bridges between native unit types and `Quantity`
5. Neon config for catalog and policies
6. Richer messages / structured identifiers

> **Update 2026-07-25:** Milestone 1 is done. Milestones 2–6 remain; see the reduced "Next pieces" list above, which is
> now the authoritative near-term plan.

## Summary Slogans

- **One unit engine (Yumemi). Two presentation layers (Rational `Quantity` vs native static types).**
- **Runtime stays exact (`Rational`). PHPStan tracks real PHP numbers with units.**
- **Static unit types are `(native number kind × unit expression)`.**
- **PHPStan reuses Yumemi; it does not reimplement units or replace the runtime.**
