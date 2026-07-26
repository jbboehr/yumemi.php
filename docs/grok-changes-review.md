# Grok Changes Review

Review date: 2026-07-24

Scope: review of the current `develop` branch after the Grok-authored changes, with emphasis on regressions,
static/runtime semantic mismatches, and gaps in test coverage.

## Findings

### 1. PHPStan Fails On Committed Code

**Status: FIXED** (commit `343027f`, "Fix PHPStan handling for dynamic unit strings").
`vendor/bin/phpstan analyse --no-progress --error-format=raw` now exits 0. The `unit()` / `unit_to()` dynamic return
type extensions fall back to the native signature when unit strings are not compile-time constants (so `$converted` at
`tests/UnitFunctionTest.php:107` is no longer `mixed`), and the redundant `(float)` cast at
`tests/UnitFunctionTest.php:153` was removed.

_Original finding below._

`vendor/bin/phpstan analyse --no-progress --error-format=raw` currently fails:

```text
tests/UnitFunctionTest.php:107: Parameter #1 $value of function
jbboehr\Yumemi\unit_to expects float|int, mixed given.
tests/UnitFunctionTest.php:153: Casting to float something that's already float.
```

The first issue appears to come from the `unit_to()` dynamic return type extension. When `$from` and `$to` are runtime
strings instead of constant strings, the extension returns `ErrorType`; that poisons `$converted`, so the next
`unit_to($converted, ...)` call sees `mixed`.

The second issue is smaller: after the `is_int($value)` branch in `expectedConvertedFloat()`, `$value` is already known
to be `float`, so `(float) $value` is redundant under strict rules.

Relevant locations:

- `tests/UnitFunctionTest.php:107`
- `tests/UnitFunctionTest.php:153`
- `src/PHPStan/UnitToFunctionDynamicReturnTypeExtension.php:40`
- `src/PHPStan/UnitToFunctionDynamicReturnTypeExtension.php:77`

### 2. Invalid `unit()` / `unit_to()` Calls Are Not Diagnostics By Themselves

**Status: FIXED.** Added `InvalidUnitCallRule` (`src/PHPStan/InvalidUnitCallRule.php`), a PHPStan `Rule` over `FuncCall`
nodes, registered in `extension.neon` under the `phpstan.rules.rule` tag. It now reports a standalone diagnostic
(`yumemi.invalidUnitCall`) for invalid `unit()` / `unit_to()` calls, independent of whether the result is later used.

> **Note (added by Claude, 2026-07-25):** Implemented by reusing the existing validation rather than duplicating it. The
> two dynamic return-type extensions already computed exactly these error conditions and returned an `ErrorType`
> carrying a reason string; I extracted their bodies into a shared `inferType(FuncCall, Scope): ?Type` and had the rule
> call the same method, emit a diagnostic when it gets an `ErrorType`, and surface its `getReason()` verbatim. So the
> extension and the rule stay a single source of truth — messages can never drift. Coverage:
> `tests/PHPStan/InvalidUnitCallRuleTest.php` pins the five invalid cases (unknown unit in `unit()`; unknown from/to and
> dimensional mismatch and value/from mismatch in `unit_to()`) and confirms valid and non-constant (unanalysable) calls
> produce nothing. The rule also fired on a real intentional call in `tests/UnitFunctionTest.php:26` (a
> runtime-rejection test), which now carries an inline `@phpstan-ignore yumemi.invalidUnitCall` documenting the intent.
>
> Follow-up worth tracking (not done): the operator layer has the same silent-`ErrorType` gap. `meter + second`,
> `unit_int % unit_float`, and unit ± bare-numeric all infer `ErrorType` from `UnitOperatorTypeSpecifyingExtension` with
> a good reason, but produce no diagnostic unless the poisoned result is later used. A sibling rule over binary-op nodes
> (gated on at least one unit operand, reusing the same `getReason()` pattern) would close it.

The dynamic return type extensions return `ErrorType` for invalid unit strings or incompatible conversions, but PHPStan
does not report a bare invalid helper call just because a dynamic return type extension returned `ErrorType`.

I tested standalone invalid calls like:

```php
unit(1.0, 'not_a_real_unit_xyz');
unit_to(1.0, 'meter', 'second');
```

PHPStan exited cleanly unless the `ErrorType` result was later used in a context that trips another rule. The
`assertType('*ERROR*', ...)` fixtures prove that the extension can infer an error-like type, but they do not prove users
get diagnostics for invalid calls in ordinary code.

This likely needs a separate PHPStan rule over function calls. The dynamic return type extension should infer branded
types when it can, and a rule should emit actual diagnostics for invalid constants, incompatible `from`/`to` units, and
branded values whose unit does not match `from`.

Relevant locations:

- `src/PHPStan/UnitFunctionDynamicReturnTypeExtension.php:41`
- `src/PHPStan/UnitToFunctionDynamicReturnTypeExtension.php:77`
- `tests/PHPStan/data/unit-construction-assert.php:21`
- `tests/PHPStan/data/unit-construction-assert.php:55`
- `tests/PHPStan/UnitFunctionDynamicReturnTypeExtensionTest.php:25`

### 3. `%` Allows Unit Floats Even Though PHP Modulo Is Integer Semantics

**Status: FIXED** (commit `185d01f`, "Restrict unit modulo inference to integers"). `%` is now handled separately from
`+` / `-` by `specifyMod()` (`src/PHPStan/UnitOperatorTypeSpecifyingExtension.php:80`): it rejects unless both operands
are `UnitIntegerType` with equivalent normalized units, and returns `UnitIntegerType`, matching PHP's integer modulo
semantics. Unit/integration coverage was added for the `unit_float` rejection, bare-numeric rejection, and
dimensionless-RHS rejection cases.

_Original finding below._

`UnitOperatorTypeSpecifyingExtension` treats `%` like `+` and `-`, so unit floats with matching units are accepted and
the result may be inferred as `unit_float`.

PHP's modulo operator coerces operands to integers. For example, `5.5 % 2.0` emits a deprecation about implicit float to
int conversion and returns `int(1)`.

The extension should probably require `UnitIntegerType` operands for `%` and return `UnitIntegerType`. The current test
coverage only pins the good `unit_int % unit_int` case, so this path is easy to miss.

Relevant locations:

- `src/PHPStan/UnitOperatorTypeSpecifyingExtension.php:39`
- `src/PHPStan/UnitOperatorTypeSpecifyingExtension.php:72`
- `src/PHPStan/UnitOperatorTypeSpecifyingExtension.php:220`
- `tests/PHPStan/UnitOperatorTypeSpecifyingExtensionTest.php:194`

### 4. Runtime And PHPStan Addition Semantics Differ For Exact-Scale Aliases

**Status: FIXED.** `Quantity::assertSameUnit()` (`src/Quantity.php:236`) now falls back to normalized-expression
equality (`ExprComparer::equal($this->normalizedUnit(), $other->normalizedUnit())`) when the symbolic units are not
structurally identical. Because normalized equality includes the leading constant, it holds only when the conversion
factor is exactly 1, so raw magnitude addition stays exact and no value conversion is performed. This is the same
predicate the PHPStan operator layer uses (`UnitExpression::equivalent()`), so the two layers now agree. The symbolic
fast-path is kept ahead of it so identical / bare symbolic units never need to normalize. Runtime coverage added in
`tests/QuantityTest.php`: exact-scale alias `add` / `sub` accepted, and `1 km + 1000 m` (a real scale difference,
factor 1000) still rejected.

> **Note (added by Claude, 2026-07-25):** Implemented as recommended below — narrow alignment to exact-scale aliases
> only. `1 km + 1 * (1000 * meter)` now yields `2 km`; `1 km + 1000 m` still throws (needs explicit conversion). The
> result keeps the left operand's symbolic unit for display. A broader display-time "denormalizer" (finding a prefix +
> unit with no leading constant) was discussed but deliberately left out of scope: it is an opt-in presentation concern,
> not required for this semantic alignment.

_Original finding below._

PHPStan now accepts definitionally equivalent units for assignment and `+` / `-`. For example, `kilometer` and
`1000 * meter` are considered equivalent because their normalized expressions match.

Runtime `Quantity::add()` still compares the symbolic stored units directly:

```php
$units->quantity(1, 'kilometer')->add($units->quantity(1, '1000 * meter'));
```

This throws:

```text
Incompatible unit expressions: kilometer and 1000 * meter.
Both have dimension length; convert explicitly before adding or subtracting.
```

Rejecting `1 kilometer + 1000 meter` is correct because that needs value conversion. Rejecting
`1 kilometer + 1 * (1000 * meter)` is more questionable because the units are exact-scale aliases and no value
conversion is needed. We should decide whether the runtime `Quantity` API is meant to stay stricter than the native
PHPStan scalar API, or whether `Quantity::add()` / `sub()` should use normalized equivalence too.

Relevant locations:

- `src/Quantity.php:236`
- `src/PHPStan/UnitExpression.php:30`
- `src/PHPStan/UnitFloatType.php:37`
- `src/PHPStan/UnitOperatorTypeSpecifyingExtension.php:61`
- `tests/PHPStan/UnitOperatorTypeSpecifyingExtensionTest.php:68`

### 5. `Units::unit()` And `Unit::dimension()` Are Easy To Misuse

**Status: FIXED.** `dimension()` is now part of the `Expr` interface (`src/Expr.php`) and implemented on every node —
`Compound`, `Term`, and `Constant` in addition to the existing `Unit` — so the value returned by `Units::unit()` (an
`Expr`, often a `Compound`) is dimensionable directly: `$units->unit('centimeter')->dimension()` now returns `length`
instead of erroring. Resolution is recursive and structural (a product multiplies its factors' dimensions, a power
scales them, a constant is dimensionless, a unit leaf resolves through its definition tree with the existing
bound-`Units` fallback), so it needs no registry reference threaded through `Expr`. Verified to agree with
`DimensionResolver::resolve()` and the `Units::dimension()` facade across prefixed, derived, and compound expressions.
Coverage added in `tests/UnitsTest.php::testUnitExpressionsExposeDimensionDirectly`.

> **Note (added by Claude, 2026-07-25):** Implemented the "uniform" option — dimension access at the `Expr` level —
> rather than the docblock-only mitigation, since this surface is still pre-stable and it removes the type-dependent
> sharp edge entirely. `Units::dimension(Expr|string)` (`src/Units.php:71`) remains as the name/string facade. One
> documented boundary: a `Compound` hand-built from bare _symbolic_ unit leaves (no definitions and no bound context —
> not something `unit()` / `parse()` produce) still throws `UnsupportedUnitDimensionException`, the same limitation
> `Unit` has for context-less bare symbols.

_Original finding below._

The docs on `Units::unit()` say it is the supported way to obtain `Unit` values, but the method returns `Expr`. For
derived or prefixed units, it may return a `Compound`, not a `Unit`.

For example:

```php
$units->unit('centimeter')->dimension();
```

fails because the returned value is a `Compound` and has no `dimension()` method.

There is a related sharp edge with `Quantity::unit()`: it returns the symbolic unit expression. In simple cases that may
be an unbound symbolic `Unit`, and calling `dimension()` on that unit can fail because it has no catalog context.

This is lower severity than the PHPStan failures, but the API should probably be clarified before these accessors become
stable public surface. Options include documenting `Units::unit()` as `Expr`, adding `Units::dimensionOfUnitName()`, or
adding dimension access at the `Expr`/facade level instead of on `Unit` leaves only.

Relevant locations:

- `src/Units.php:93`
- `src/Units.php:99`
- `src/Quantity.php:202`
- `src/Expr/Unit.php:63`

## Checks Run

Passing:

```text
vendor/bin/phpunit --colors=never
composer validate --strict
composer cs
nix flake check --print-build-logs
```

Failing:

```text
vendor/bin/phpstan analyse --no-progress --error-format=raw
```

The failing PHPStan command matters because `.github/workflows/ci.yml` runs the same analysis in the PHP CI job.

> **Note (added by Claude, 2026-07-25):** `vendor/bin/phpstan analyse` now exits 0 (see finding #1). The repo is
> PHPStan-clean again, so this CI job passes.

## Overall Assessment

The Grok changes add substantial useful surface area: PHPStan branded scalar types, native `unit()` / `unit_to()`
helpers, operator inference, registry builder work, runtime invariants, and more fixture coverage. The runtime analyzer
core still looks conservative: resolution is fail-closed, dimensions are canonicalized through normalized expressions,
and conversion factors remain exact rationals internally.

The risky area is the PHPStan layer. It is close enough to be worth continuing, but it needs cleanup before we trust it:
make the repo PHPStan-clean again, separate diagnostics from return-type inference, fix `%`, and deliberately choose the
relationship between runtime `Quantity` semantics and static native-scalar semantics.

> **Note (added by Claude, 2026-07-25):** All five findings are now resolved — the repo is PHPStan-clean again (#1),
> invalid `unit()` / `unit_to()` calls now produce standalone diagnostics via `InvalidUnitCallRule` (#2), `%` is fixed
> (#3), the runtime-vs-static equivalence relationship is aligned (#4), and dimension access is now uniform across
> `Expr` (#5). One follow-up remains as a tracked improvement rather than a review item: the operator layer has the same
> silent-`ErrorType` gap #2 addressed for helper calls (see the note under #2), which a sibling binary-op rule would
> close.
