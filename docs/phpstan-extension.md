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
is no conditional-tag mechanism, so it would just be another unknown tag either way). `@yumemi-param` and
`@yumemi-return` are implemented; `@yumemi-var` was investigated and deferred (feasible via an AST-rewrite pass — see
the progress log below).

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
| `+` / `-` on native unit types         | Same unit (exact) or same dimension (config); result keeps unit   |
| `*` / `/`                              | Combine unit exprs; promote `int`×`float` → `float` per PHP rules |
| `*` / `/` by bare dimensionless number | Preserve unit                                                     |
| Unsafe cast to bare `int`/`float`      | Drop unit or error (config)                                       |

Use PHPStan operator type extensions where possible.

### Slice 3 — Runtime `Quantity` interop (optional but useful)

- PHPDoc `Quantity<'meter'>` → object type with Rational + unit
- `Units::quantity(1, 'meter')` return type
- `value()` / `intValueIn` / `to` bridging native unit types ↔ `Quantity` if desired

### Slice 4 — `add` / `sub` policy config

1. **Exact** (default): same normalized unit, including scale
2. **Dimension**: same `Dimension` (static checking only; native values cannot be converted automatically)

Runtime `Quantity` methods are not governed by this native-operator setting: `add()` / `sub()` perform exact runtime
conversion between compatible dimensions, while `addWithSameUnit()` / `subWithSameUnit()` require exact-unit equivalence
and perform no conversion.

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
  normalized-equivalent units; all four binary methods keep the left unit; `to` rebrands to the constant target; and
  `normalize` / `simplify` rebrand to the catalog-normalized form, with `simplify` removing the scale constant that the
  runtime method folds into the magnitude
- **No dedicated assignment/argument rule is needed:** `QuantityType::accepts()` plus PHPStan core's `CallMethodsRule`
  already reject a `Quantity<'foot'>` passed where `Quantity<'meter'>` is expected
- **Fails open** like the native helpers: a non-constant exponent/target, an unbranded `Quantity` operand, or a unit
  unknown to the default catalog falls back to the native `Quantity` return (since `to()` is instance-scoped and may run
  against a custom registry), so a `Quantity` value never poisons unrelated analysis
- Covered by `QuantityReturnTypeExtensionTest`, `QuantityArgumentTypeRuleTest`, and the `quantity-assert.php` fixture
- Every current unit-bearing fluent method is now branded, including `simplify()`.

### Piece 8 — extension-optional `@yumemi-return` for functions (done)

First slice of the extension-optional annotation surface (see "Annotation Surface"). A function keeps a native return
type in its signature and adds `@yumemi-return unit_int<'foot'>`; when the extension is loaded, call sites see the
branded unit type.

- `YumemiDocTagReader` reads the vendor-prefixed tags (`@yumemi-return` here, `@yumemi-param` in Piece 9; `@yumemi-var`
  reserved; deferred but feasible — see below) from a `ResolvedPhpDocBlock`. Unknown tags survive as
  `GenericTagValueNode`; the type payload is re-parsed through PHPStan's `TypeStringResolver` so it reaches
  `UnitTypeNodeResolverExtension` — one parser, one meaning
- Only branded unit types are honoured; a native type or an invalid-unit `ErrorType` is treated as absent, so a tag
  never poisons unrelated analysis (fail-open, matching `UnitsQuantityReturnTypeExtension`)
- `YumemiReturnTagFunctionReturnTypeExtension` (a `DynamicFunctionReturnTypeExtension`, covering every function via
  `isFunctionSupported`) resolves the callee's doc comment via `FileTypeMapper` and brands the return
- **Fast path:** because `isFunctionSupported` runs on every function, a `str_contains($docComment, '@yumemi-return')`
  guard short-circuits before any phpdoc resolution/scan for the overwhelmingly common no-tag case
- Covered by `YumemiReturnTagExtensionTest` — a `TypeInferenceTestCase` matrix (the annotated functions are `require`d
  into the process, since the harness does not index functions local to the analysed fixture), plus a CLI enforcement
  fixture proving the brand flows into core `argument.type` checking, not just `assertType`
- **Deferred:** `@yumemi-return` on object methods (blocked by PHPStan's per-class `getClass()` dynamic-return hook); a
  validation rule surfacing invalid-unit tag payloads (currently silently ignored); bundled stub files for third-party
  libraries

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

### Piece 10 — `Quantity` addition/subtraction policies and diagnostics (done)

- Runtime `Quantity::add()` / `sub()` convert compatible right operands exactly into the left unit; `addWithSameUnit()`
  / `subWithSameUnit()` retain the former no-conversion behavior
- `QuantityMethodReturnTypeExtension` validates the corresponding dimension or exact-unit precondition and preserves the
  receiver's brand
- `InvalidQuantityArithmeticRule` surfaces invalid branded calls as `yumemi.invalidQuantityArithmetic`, including when
  the result is unused; unbranded operands continue to fail open

### `@yumemi-var` — investigated, feasible, deferred (2026-07-26)

The third tag in the family was investigated in depth. It turns out to be **feasible via a supported hook**; it is
deferred on product-value grounds, not because it can't be done. The reasoning, in order:

**Propagation is already solved by the extension-required `@var`.** A native-position `@var unit_int<'…'>` brands a
local variable and flows through operators, so the local/property use case needs no new tag:

```php
/** @var unit_int<'foot'> $x */
$x = 3;
// assertType confirms: $x is unit_int<'international_foot'>, and so is $x + $x
```

(Verified via a CLI assertType probe. Property `@var` should resolve through the same `UnitTypeNodeResolverExtension`;
confirm with a fixture if ever pursued.)

**You cannot _inject_ a var type via an extension — but you don't need to.** Reviewed every `*Extension` interface
PHPStan exposes; the type-affecting ones are all call / operator / property-reflection shaped
(`Dynamic{Function,Method,StaticMethod}ReturnTypeExtension`, `OperatorTypeSpecifyingExtension`,
`ExpressionTypeResolverExtension`, `TypeNodeResolverExtension`, `PropertiesClassReflectionExtension`). None can inject a
type into a variable's scope from an unknown statement-level tag, and the one internal seam that does
(`PhpDocNodeResolver::resolveVarTags()`) is a `final` class hinted by concrete type — un-decoratable without forking
PHPStan. So _direct type injection_ is a dead end.

**The viable route is AST rewrite (sugar expansion), via a supported hook.** Instead of injecting a type, rewrite
`@yumemi-var` into a real `@var` before analysis, and let the already-working machinery do the rest. PHPStan exposes the
`phpstan.parser.richParserNodeVisitor` service tag: `RichParser` runs container-tagged nikic node visitors on every
file's AST during parsing. The design:

1. Register a `NodeVisitor` tagged `phpstan.parser.richParserNodeVisitor`.
2. In `enterNode`, guard with `str_contains($docText, '@yumemi-var')` (cheap; skips ~everything).
3. On a match, rewrite the node's doc comment — turn `@yumemi-var unit_int<'foot'> $x` into a real
   `@var unit_int<'foot'> $x`, **replacing** the native `@var int $x` for that variable (not adding a second `@var`),
   and `$node->setDocComment(new Doc($rewritten, …))`.
4. Downstream, PHPStan's own `@var` handling reads the now-real tag and brands + propagates through
   `UnitTypeNodeResolverExtension`. No internal-class hacking, no reimplementation of scope injection, and when the
   extension is absent `@yumemi-var` is just an ignored comment — clean graceful degradation.

This is legitimately clean: a registered extension point plus the propagation path we already proved. It is far better
than either a `final`-class fork or a **check-only** assignment rule (which would validate the RHS but not propagate,
i.e. strictly weaker than `@var unit_int<'…'>`).

**Open questions before committing** (settle with a ~10-line spike):

- **The one unverified link:** confirm a doc-comment mutation inside a richParser visitor is actually honoured by
  `NodeScopeResolver`'s `@var` reading (parsing runs before analysis, so it should be — but verify).
- **Dedup:** the graceful pattern is `@var int $x` + `@yumemi-var unit_int<'foot'> $x`; the rewrite must replace the
  native `@var` for that variable, keyed by name, rather than emit a duplicate.
- Minor: per-node cost (mitigated by the `str_contains` guard), doc-line-position shifts, and that the tag — while
  supported and stable across 1.x/2.x — is lightly documented.

**Why defer anyway (product, not feasibility).** Graceful degradation — the reason `@yumemi-param` / `@yumemi-return`
exist — protects _external_ consumers (other libraries, IDEs, phpDocumentor) from seeing `unit_int` as an unknown type
on a _public API_. A `@var` is a local implementation detail with no external audience; anyone annotating local units
already runs the extension, so `@var unit_int<'…'>` serves them directly. The one niche a custom tag would add — a
codebase that runs the extension in some CI configs but not others and wants a local `@var unit_int` to stay quiet in
the without-extension runs — is narrow. Now that the implementation cost is "one node visitor," the call is purely
whether that niche is worth it, not whether it's possible.

**Decision:** deferred. Default guidance stays `@var unit_int<'…'>` for locals and properties. If the mixed-config niche
becomes real, implement the richParser-visitor sugar-expansion above (spike the unverified link first); do **not** reach
for a `final`-class fork or a check-only rule. (The `YumemiDocTagReader` already reserves the `@yumemi-var` constant.)

### Next pieces

1. **Exact vs dimension native-arithmetic mode + neon config** — native `+` / `-` remain exact-unit today; add the
   relaxed dimension mode and a `parameters.yumemi` config shape (arithmetic mode, catalog, bare-numeric policy). This
   is now the largest remaining PHPStan item. Runtime `Quantity` method semantics remain fixed as described in Piece 10.
2. **Richer identifiers / messages** — stable per-cause error identifiers beyond the current `yumemi.invalidUnitCall`.
3. **Stub files for third-party libraries** — the last remaining piece of the extension-optional surface. The
   `@yumemi-return` (Piece 8) and `@yumemi-param` (Piece 9) tags are done; `@yumemi-var` is feasible via an AST-rewrite
   pass but deferred (see above). What is left is bundling `.stub` files via a `StubFilesExtension` so `@yumemi-*` tags
   can enrich libraries you do not control (see "Annotation Surface").

**Success criterion (piece 2):** `unit_int<'mass'>` errors; `unit_int<'meter / second'>` is a real type. **(met)**

> **Update 2026-07-25:** Pieces 3–6 are complete (commits `343027f`, `185d01f`, `515e722`, `a2180ed`, and the
> invalid-call rule). The native `unit_int` / `unit_float` path — PHPDoc resolution, validation, operator inference,
> `unit()` / `unit_to()` helpers, assignment checks via the branded types' `accepts()`, and standalone invalid-call
> diagnostics — is now in place. What remains is the runtime `Quantity` object path and the exact-vs-dimension config
> work.

> **Update 2026-07-26:** Piece 7 (the runtime `Quantity<'…'>` object path — `Quantity<'…'>` PHPDoc resolution,
> `Units::quantity()` inference, and fluent-method inference through `mul` / `div` / `pow` / `neg` / `add` / `sub` /
> `to` / `normalize`) landed in commits `7b8b759` and `64786af` and is now documented above. The open-item lists ("Next
> pieces", "Later Milestones") were corrected accordingly — the largest remaining PHPStan item is now the
> exact-vs-dimension arithmetic mode plus `parameters.yumemi` config. `simplify()` inference has since completed the
> current unit-bearing fluent-method surface.

## Later Milestones

1. ~~Operator inference for `+` `-` `*` `/` on native unit types~~ **(done — Piece 3)**
2. Exact vs dimension arithmetic mode
3. ~~Runtime `Quantity` PHPDoc + method inference (Rational × unit)~~ **(done — Piece 7)**
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
