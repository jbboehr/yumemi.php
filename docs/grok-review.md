# Grok Code Quality Review

Date: 2026-07-24

Full-repository review of `jbboehr/imm` (Iudex Mensurarum Mysteriorum): code quality, elegance,
potential issues, and readiness for the planned PHPStan dimensional analysis layer.

**Scope:** `src/`, `tests/`, and project config. Not a line-by-line pass over generated
`src/Parser/Parser.php` or the large generated catalog dump.

**Verification at review time:** 150 PHPUnit tests green, PHPStan level max clean, PHP-CS-Fixer
clean. Several edge cases outside the suite were probed manually.

**Follow-up (same day):** Issues are being fixed one-by-one after this review was written. Original
findings are retained below; fixed items are marked **Status: fixed** with a short note on what
changed.

---

## Executive Summary

This is a **strong early library**: small surface area, clear separation of parser / expression /
reduce / normalize / convert / quantity, exact rationals, and unusually good design docs and
invariant tests. It does not read like unreviewed AI churn.

The main quality gaps are not style. They are **correctness hazards in unit-name resolution**, a
few **public API footguns**, and some **duplication / dual representations** that will hurt once
the PHPStan layer depends on this engine.

Confidence summary:

- Runtime units calculator for **trusted** unit strings: fairly high.
- Accepting **arbitrary** user or PHPDoc unit strings (exactly what PHPStan will do): improved
  after the fail-closed resolver fix; still needs care for SI prefix × short-symbol compositions
  (`pa` / `PA`) and for the remaining open issues below.

**Original confidence at review time (superseded for the resolver line):** arbitrary unit strings
were not yet trustworthy because the resolver was too greedy (see issue #1).

---

## Project State (Context)

IMM aims to be both:

1. A runtime unit expression, dimensional compatibility, and conversion library.
2. A PHPStan extension for static dimensional analysis.

The runtime is the source of truth. PHPStan should be an adapter over the same parser, registry,
normalizer, and conversion semantics.

At review time:

| Area                              | State                                                     |
| --------------------------------- | --------------------------------------------------------- |
| Multiplicative runtime core       | Substantial and usable                                    |
| `Quantity` API                    | Implemented with deliberate strict add/sub                |
| UDUNITS2 catalog                  | Generated, smoked in tests                                |
| Explicit `Dimension` model        | Present (recent)                                          |
| PHPStan extension in this package | Absent (~0%)                                              |
| Old prototypes                    | `tmp/` (gitignored); `units.php` was the better reference |

---

## What Is Elegant

1. **One pipeline, clear stages**
   parse → AST convert → `Expr` → reduce → normalize → dimension / conversion factor →
   `Quantity`. Easy to reason about and good for static reuse later.

2. **Dual unit tracking on `Quantity`**
   Symbolic unit (display / chosen syntax) versus resolved unit (catalog-aware conversion) is the
   right model for “no silent conversion on `add`/`sub`.”

3. **Exact `Rational` with GMP**
   Correct choice for conversion factors. Canonicalized sign/GCD, overflow checks on native int
   conversion, `fromDecimalString` for scientific notation.

4. **Parser wider than semantics**
   Affine `@` and `+`/`-` parse then fail with `UnsupportedSyntaxException`. Honest staging for
   temperature later.

5. **Test posture**
   README examples as tests, catalog smoke tests, reciprocal conversion invariants, real-world
   formulas. Above average for a project this young.

6. **Tooling**
   PHPStan level max + strict rules, PHPUnit strict flags, multi-PHP CI, Nix flake, CS Fixer.
   Solid.

---

## Critical / High Correctness Issues

### 1. Unit resolver false positives (bug — high)

**Status: fixed** (2026-07-24), with a residual note on single-letter SI compositions.

**Original finding:** `UnitResolver` tried, in order: catalog lookup → **prefix decomposition** →
**naive plural stripping**, recursively.

That combination invents units for nonsense or wrong words. Probed examples at review time:

| Input   | Resolves to (approx.)              |
| ------- | ---------------------------------- |
| `mass`  | milli × atto × second              |
| `pass`  | pico × atto × second               |
| `ass`   | atto × second                      |
| `has`   | hecto × atto × second              |
| `bus`   | bushel (`bus` → strip `s` → `bu`)  |
| `METER` | mega + further prefixes + roentgen |
| `PA`    | peta × ampere                      |
| `pa`    | pico × are                         |

While `Pa` correctly becomes pascal.

Root causes:

- Single-letter SI prefixes (`m`, `a`, `p`, `M`, …) match almost anything.
- Plural stripping is only `…es` / `…s`, with no catalog plural metadata and no requirement that
  the stem be a known unit before accepting the strip.
- Prefix application does not require the residual to be an exact catalog name without further
  reckless rewriting; recursion allows prefix + prefix + plural stacks.

Relevant code (at review time): `src/Analyzer/UnitResolver.php` (`tryLookupWithPrefixes`,
`tryLookupStripPlural`).

**Why this matters for the end goal:** PHPStan will treat `Quantity<'mass'>` as a valid unit
string if resolution succeeds. Silently mapping `mass` to a time quantity is worse than rejecting
it.

**Suggestion (implemented):** Resolve only with:

1. Exact catalog hit (including aliases / generated plurals).
2. Prefix + residual only if residual is an **exact** known unit or alias (no plural strip inside
   residual, or only catalog plurals).
3. Optional plural pass using catalog `plural` fields only.
4. Prefer fail-closed over fail-open.
5. Case policy: document “case-sensitive, catalog forms only,” or implement a deliberate fold for
   SI symbols — not accidental prefix matches on `METER`.

Add regression tests for at least: `mass`, `bus`, `METER`, `PA` / `pa` / `Pa`, `pass`.

**Fix notes:**

- `UnitResolver` is fail-closed: exact `lookup`, then a single prefix applied only when the
  residual is an exact catalog hit. Recursive plural stripping was removed.
- Plurals such as `meters` / `inches` / `centimeters` work via catalog-backed aliases:
  - `Udunits2UnitRegistry` materializes conservative English plurals (and explicit `plural`
    fields) into aliases at load time.
  - `Udunits2CatalogImporter` registers explicit UDUNITS2 plurals as aliases on future catalog
    regenerations.
- Adversarial tests live in `tests/Analyzer/UnitResolverTest.php` (`mass`, `bus`, `METER`, …).
- **Still open / intentional ambiguity:** one-level SI compositions like `pa` → pico·are and
  `PA` → peta·ampere remain, because `p`/`P` are prefixes and `a`/`A` are real catalog names.
  `Pa` still resolves to pascal via exact lookup. Tightening short residual rules is optional
  follow-up, not the morphology class of bug fixed above.

---

### 2. `meter^0` is not reduced by `parse()` (bug — medium)

**Status: fixed** (2026-07-24).

**Original finding:**

```text
parse("meter^0") => "meter ^ 0"   // Term with power 0
```

`ExprReducer` would collapse power-0 collection to dimensionless `1`, but `Units::parse()` does
not reduce. Callers that format without reducing see a meaningless unit factor.

**Suggestion:** Either always reduce at the `Units` boundary (`parse` / `unit`), or document that
`Expr` values may be unreduced and provide `Units::reduce()`. Prefer reducing at the facade for
predictability.

**Fix notes:** `Units::parse()` and `Units::unit()` now run `ExprReducer::reduce()` before
returning. Covered by `UnitsTest::testParseReducesZeroPowersToDimensionless`.

---

### 3. `Unit::dimension()` is a footgun (bug/API — medium)

**Status: fixed** (2026-07-24).

**Original finding:**

```php
// src/Expr/Unit.php
public function dimension(): Dimension
{
    return (new DimensionResolver(new UnitNormalizer()))->resolve($this);
}
```

- Builds a **fresh** normalizer with **no registry context**.
- Bare `new Unit('foot')` throws `UnsupportedUnitDimensionException`.
- The same name via `Units::dimension('foot')` correctly returns `length`.

So the method only works for SI base names or units whose definitions are already fully expanded
to bases on the object. Easy to misuse; looks public/API-ish.

**Suggestion (evolved):** Keep `Unit::dimension()` (useful API). Make the constructor `@internal` and
steer application code through `Units::unit()`. Dimension uses the definition tree first, then a
weak `Units` fallback when bound.

**Fix notes:**

- `Unit` constructor is `@internal`; docs point at `Units::unit()` / parse / quantity.
- `Units::unit()` and `Units::parse()` stamp a `WeakReference` to the `Units` context onto unit
  leaves via `Unit::withUnits()`.
- `Unit::dimension()`: pure definition-tree path, then catalog fallback through the bound context.
- Bare `new Unit('foot')` still fails with a message that names `Units::unit()`.
- `Dimension` remains a pure value object (no registry).

---

### 4. Quantity equality for `add`/`sub` is stringly typed (design risk — medium)

**Status: fixed** (2026-07-24).

**Original finding:**

```php
// src/Quantity.php
private function assertSameUnit(self $other): void
{
    if ($this->unit->toString() === $other->unit->toString()) {
        return;
    }

    throw IncompatibleUnitException::create($this->unit, $other->unit);
}
```

Uses `Expr::toString()`, not structural equality, and **not** `ExprFormatter` (which produces
different strings, e.g. `meter / second` vs `meter * second ^ -1`).

Today this mostly works because both sides are `ExprReducer::reduce`’d on construction (stable
unit order via `ksort`). It will break or become subtle if:

- formatting of constants or powers changes
- someone passes already-reduced exprs built differently
- a future “same dimension” mode for runtime add is introduced

**Suggestion:** Add structural `Expr` equality (or compare reduced canonical forms / unit maps +
constant), and use that here. Optionally compare **resolved** units when dimensional add is
added.

**Fix notes:**

- `Analyzer\ExprComparer` compares reduced trees structurally.
- `Expr::equals()` via `MathTrait` delegates to it.
- `Quantity::assertSameUnit()` uses structural equality on symbolic units (still not dimensions).
- Unit equality is by name only (ignores definition / bound Units context).

---

## Medium Maintainability / Design Issues

### 5. Near-duplicate converters

**Status: fixed** (2026-07-24).

**Original finding:** `AstConverter` and `SymbolicAstConverter` differ only in `Identifier`
handling (resolve vs bare `Unit`). That will drift.

**Suggestion:** One converter with a strategy or callback for identifiers, or a flag such as
`resolved: bool`.

**Fix notes:** Single `AstConverter` with optional `UnitResolver`. `AstConverter::symbolic()` is
the no-resolver mode used by `Quantity` for chosen syntax. Resolving mode remains the default for
`Units::parse` / registry definition parsing. Dual _semantics_ kept; dual _classes_ removed.

---

### 6. Dual string representations for expressions

| Path                      | Example               |
| ------------------------- | --------------------- |
| `Expr::toString()`        | `meter * second ^ -1` |
| `ExprFormatter::format()` | `meter / second`      |

`Quantity::toString()` / `unitToString()` use the formatter; many tests and exceptions use raw
`toString()`. Fine if intentional, confusing for users and for string equality.

**Suggestion:** Pick one canonical display form for public APIs and exceptions, or name them
explicitly (`toDebugString` vs `toDisplayString`).

---

### 7. Mutable registry behind an assumed-stable `Units` context

`UnitRegistry::register()` is public; `Quantity` treats `$this->units === $other->units` as a
closed world. Mutating a shared registry after quantities exist can desync caches in
`UnitResolver` / `Udunits2UnitRegistry` (both cache lookups, including negative cache of `null`).

**Suggestion:** Freeze registries after build, or document immutability and stop exposing
`register` on the hot path. A builder that returns an immutable registry fits the planned
custom-units work.

---

### 8. Circular construction / layered caching

`Udunits2UnitRegistry` constructs `AstConverter(UnitResolver($this))`, while `UnitResolver`
constructs another `AstConverter($this)`. Prefix defs and unit defs both parse through that loop
with separate caches. It works, but it is hard to follow and easy to re-enter during catalog bugs
(circular aliases would recurse until stack overflow — no cycle guard).

**Suggestion:** Explicit “definition parser” owned by the registry, cycle detection for aliases
and definitions, single resolve cache keyed by name.

---

### 9. `ConversionFactorResolver` vs dimension checks

`compatible()` uses `DimensionResolver::resolve` (normalize inside). `resolve()` re-normalizes
and uses `resolveNormalized`. Consistent enough, but it double-normalizes and constructs its own
`DimensionResolver` even though `Units` already has one.

Not wrong — slightly muddy ownership. Fine until performance matters.

---

### 10. `Units::default()` is not a singleton

Every call is a new instance. Documented behavior is tested (`add` across two defaults throws a
context error). Good for purity, surprising for users who write:

```php
Units::default()->quantity(...)->add(Units::default()->quantity(...));
```

**Suggestion:** README callout; or a lazy singleton with clear “custom registry → new Units.”

---

### 11. Exception quality is minimal

- `IncompatibleUnitException` prints two expressions, not dimensions.
- `IncompatibleQuantityContextException` has no identifying detail.
- `UnitNotFoundException` does not suggest near-matches.

Fine for now; PHPStan diagnostics will want richer structured data (from/to, dimensions, span).

---

### 12. Project packaging / hygiene nits

- README still says “early planning” while the runtime is substantial.
- `composer.json` suggests a bundled PHPStan extension that does not exist yet.
- `tmp/` is gitignored (good); keep old prototypes out of the mental model for contributors.
- PHPUnit reports one deprecation warning (config/tooling).
- `UnitRegistry::defaults()` is a tiny hand-built stub next to full UDUNITS2 — clarify test-only
  vs public.

---

## Smaller / Lower Priority Notes

| Item                                             | Notes                                                      |
| ------------------------------------------------ | ---------------------------------------------------------- |
| `MathTrait` reduces on every `mul`/`div`/`pow`   | Correct; allocates often. OK until hot paths.              |
| No `Expr::equals` / no `Quantity` comparison ops | Needed soon for ergonomics and solid tests.                |
| No float on `Quantity` constructor               | Intentional exactness; document; consider decimal strings. |
| Integer powers via `(int) $ast->right->value`    | Huge exponents may truncate; exotic.                       |
| Affine temperatures correctly rejected           | Good; messages are parse-AST shaped, not user-friendly.    |
| `gray` vs `sievert` same dimension               | SI-correct; not a type system for equivalent dose.         |
| Dimensionless degrees → huge rationals           | Exact from π-based decimal defs; ugly display.             |
| Class coverage ~35% / lines ~70%                 | Inflated by parser AST nodes / generated parser.           |
| Importer special-cases only `cm2`                | Fragile; prefer grammar support if needed.                 |
| `prefixRegex` generated but unused by resolver   | Dead metadata?                                             |
| Catalog plurals imported but unused              | **Fixed with #1:** load-time plural aliases + importer.    |

---

## Test Suite Assessment

**Strengths:** invariants, catalog breadth, quantity semantics, README lock-in.

**Gaps (high value):**

1. Resolver adversarial cases (`mass`, `bus`, `METER`, case variants of `Pa`).
   **Done** for morphology false friends; `pa`/`PA` vs `Pa` still documents SI ambiguity.
2. Power-zero / dimensionless reduction at the facade. **Done** (`Units::parse` / `unit`).
3. Structural unit equality for add (not only happy-path strings). **Done.**
4. Circular alias/definition protection (once cycle guards exist).
5. Registry mutation vs cache coherence (if mutability remains).
6. Error message / unsupported-syntax UX for temperatures (partly covered by smoke lists).

**Original note (resolved for morphology):** the suite encoded permissive plural behavior
(`meters` → `meter`) without locking the false-positive surface. That is how `mass` slipped
through. Adversarial tests now reject those false friends; plurals are catalog-backed.

---

## Elegance Scorecard (Subjective)

| Area                       | Grade | Comment                                             |
| -------------------------- | ----- | --------------------------------------------------- |
| Architecture               | A−    | Clear, one engine; small API                        |
| Numeric model              | A     | Rationals done properly                             |
| Expression reduction       | A−    | Solid; public reduce boundary uneven                |
| Registry / name resolution | B     | Was C; fail-closed resolver + plural aliases (#1)   |
| Quantity API               | B+    | Good semantics; string equality & dual toString     |
| Errors / UX                | B−    | Typed exceptions, thin messages                     |
| Duplication                | B     | Converter twin; dimension ownership                 |
| Tests                      | A−    | Strong invariants; resolver adversarial cases added |
| Docs / planning            | A     | Rare quality of intent                              |
| Static analysis product    | n/a   | Not started                                         |

---

## Recommended Fix Order

**Must fix soon (blocks trustworthy static analysis):**

1. Fail-closed unit resolution (exact + safe prefix + catalog plurals only). **Done.**
2. Regression corpus for false friends and case. **Done** for morphology false friends;
   optional tighten for `pa`/`PA` remains.
3. Canonical reduce at `Units::parse` (or an explicit contract). **Done.**

**Should fix next:**

4. Structural `Expr` equality; use it in `Quantity::add` / `sub`. **Done.**
5. Remove or recontextualize `Unit::dimension()`. **Done** (kept method; internal ctor + Units binding).
6. Collapse the two AST converters. **Done.**
7. Registry immutability + builder sketch.

**Can wait:**

8. Richer exceptions, display-string unification, formula API, affine units, GNU units.
9. Optional short-residual SI policy (`pa` / `PA` vs `Pa`).

---

## Roadmap Alignment

The near-term plan in `docs/planning.md` remains the right order. Resolver trust for morphology
false friends is fixed; PHPStan work can start.

1. PHPDoc `Quantity<'meter / second'>` type parsing via the same parser.
2. Diagnostics for invalid or unknown unit strings.
3. Return-type inference for `to` / `mul` / `div` / `normalize` / `simplify`.
4. `add` / `sub` dimensional (then optional exact-unit) checks.
5. Registry builder / composition.
6. Catalog edge cases (remaining plurals policy, affine, log).

Suggested first PHPStan vertical slice:

```php
/** @var Quantity<'meter / second'> $speed */
$distance = $speed->mul($time); // infer Quantity<'meter'> if $time is Quantity<'second'>
$bad = $speed->add($distance);  // error: length/time vs length
```

If that works against the real runtime reducers, the architecture thesis is proven.

---

## Bottom Line

The runtime core is credible and worth keeping. The design instincts (exact math, no silent add
conversion, UDUNITS2 catalog, dual symbolic/resolved units) are sound.

**Original bottom line (issue #1):** the one place not to “trust and continue” was
**`UnitResolver`**: too clever and too accepting. Morphology false friends (`mass`, `bus`,
`METER`, …) were silent type corruption risk for PHPStan.

**Update:** fail-closed resolution and catalog-backed plurals address that class of bug.
Remaining open items (#2–#12 and optional SI short-residual policy) still matter, but they no
longer block starting the PHPStan `Quantity<'...'>` type layer on a resolver that fails closed
for invented units.
