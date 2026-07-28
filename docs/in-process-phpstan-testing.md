# In-Process PHPStan Testing

## Status

**Deferred.** The in-process harness has landed (this branch): the PHPUnit suite no longer spawns any `phpstan`
subprocess and runs in ~0.9s instead of ~9s. The corrective follow-up described below (genuine level-max, identifier
assertions, no fixture execution) is **not yet applied** — the harness ships with the known gaps recorded here. See
"Verification notes" for what was confirmed and an additional option (`scanFiles`) that avoids splitting fixtures.

Proposed follow-up work for the PHPStan test-harness rewrite.

## Decision

Keep PHPStan analysis inside the PHPUnit process. Do not restore the per-test `phpstan` subprocesses.

The rewrite substantially improves test speed and coverage measurement. Its weaknesses are specific to the current
harness and can be corrected without giving up the zero-subprocess goal. The resulting tests should be described as
comprehensive in-process extension tests, not as a byte-for-byte reproduction of PHPStan's CLI execution path.

## Goals

- Keep the normal PHPUnit suite free of `shell_exec()`, `exec()`, `proc_open()`, and equivalent process spawning.
- Exercise all PHPStan rules enabled at `level: max`.
- Preserve stable diagnostic messages, tips, lines, and identifiers.
- Load extension configuration through PHPStan's real dependency-injection container.
- Avoid executing analyzed fixture files as ordinary PHP code.
- Keep parser, PHPDoc, dynamic-return-type, operator, rule, registry, and stub behavior covered in-process.

## Non-Goals

- Reproduce PHPStan CLI output formatting, progress reporting, parallel scheduling, or result-cache behavior.
- Test PHPStan's executable itself.
- Add subprocess smoke tests to the default PHPUnit suite.
- Treat PHPStan's internal testing APIs as a permanent compatibility guarantee across major versions.

## Proposed Fixes

### 1. Make The Harness Genuinely Level-Max

`PHPStan\Testing\RuleTestCase` starts from PHPStan's level-8 test configuration. With PHPStan 2.2, level 9 enables
explicit-`mixed` checking and level 10 enables implicit-`mixed` checking. The current harness therefore does not match
the old tests' `level: max` configuration, despite saying that it does.

Add a small test-only NEON file containing the level-9 and level-10 delta:

```neon
parameters:
    checkExplicitMixed: true
    checkImplicitMixed: true

autowiredAttributeServices:
    level: 10
```

Have `InProcessAnalysisTestCase::getAdditionalConfigFiles()` include that file before `extension.neon`. Subclasses with
additional configuration should merge their files with `parent::getAdditionalConfigFiles()` instead of replacing the
base list.

Add one harness self-test that obtains the configured container and asserts:

- `checkExplicitMixed` is `true`;
- `checkImplicitMixed` is `true`;
- the autowired rule level is 10, if PHPStan exposes that value through a stable test API.

The first two assertions are the important behavior checks. Avoid reaching deeply into PHPStan internals merely to
assert the numeric level.

### 2. Preserve Diagnostic Identifiers

`RuleTestCase::analyse()` compares diagnostic messages, lines, and tips, but not identifiers. The old CLI assertions
covered identifiers such as:

- `yumemi.invalidQuantityConstruction`;
- `yumemi.invalidQuantityConversion`;
- `argument.type`;
- `parameter.phpDocType`;
- `return.phpDocType`;
- `binaryOp.invalid`.

Identifiers are part of the user-facing PHPStan contract because users reference them in `ignoreErrors` entries. They
must not be allowed to change silently.

Add an assertion helper to `InProcessAnalysisTestCase` that calls `gatherAnalyserErrors()` once and compares a list of
expected diagnostics containing:

```php
array{
    message: string,
    line: int,
    identifier: string,
    tip?: string|null,
}
```

The helper should sort deterministically by line, message, identifier, and tip before comparing. It should include all
fields in assertion failures so a regression is easy to diagnose.

Use this helper for tests that intentionally pin Yumemi or PHPStan identifiers. Ordinary rule tests that only care about
a message can continue using `RuleTestCase::analyse()`.

Do not analyze a fixture once through `analyse()` and again through `gatherAnalyserErrors()`. That would duplicate work
and could reintroduce the parser-cache and coverage problems that motivated the custom fixture helpers.

### 3. Stop Executing Analyzed Fixtures

The current test classes `require_once` several files from `tests/PHPStan/data`. Those files contain both function
declarations and top-level calls, so loading the test class also executes the analyzed program and adds its functions
and variables to PHPUnit's process.

This creates unnecessary risks:

- global function-name collisions;
- test-order dependence;
- accidental runtime warnings or exceptions while discovering tests;
- PHPUnit global-state pollution;
- symbol resolution that differs from analysis of an unexecuted source file.

Split declarations required by native function reflection into dedicated bootstrap fixtures. Keep analyzed files limited
to expressions and calls that PHPStan should inspect. Follow the existing `Fixtures/YumemiTagReturnFunctions.php`
pattern.

Where practical, put fixture functions in unique namespaces. Configure the declaration-only files through test-only NEON
`bootstrapFiles` entries so the PHPStan test container owns their loading. No bootstrap file should execute the behavior
being analyzed.

The real-world formula fixture will require the largest mechanical split: move its `expect*()` sink declarations into a
bootstrap file and leave the annotated values and formula calls in the analyzed data file.

### 4. Tighten The Composite Rule Harness

Keep `CompositeRule`, but document its actual responsibility precisely:

- obtain every service tagged `phpstan.rules.rule` from the configured test container;
- dispatch each rule only to nodes compatible with its declared node type;
- let `RuleTestCase` perform node traversal and error finalization;
- provide broad in-process interaction coverage for Yumemi and PHPStan's configured rules.

Do not claim that it reproduces a real CLI analysis. It intentionally does not cover CLI formatting, process startup,
parallel execution, result caching, or collector-driven whole-program behavior.

Remove the stale reference to `coreRuleClasses()` from `InProcessAnalysisTestCase`; no such extension point currently
exists. Either all tagged rules are composed or a future explicit filtering API should be designed and tested.

Add a defensive assertion that the composed rule list is non-empty. Optionally assert that selected critical rules are
present by class name, but avoid pinning the complete PHPStan rule list because that would make patch upgrades noisy.

Useful presence checks would cover:

- at least one Yumemi rule;
- PHPStan's invalid binary-operation rule;
- PHPStan's function-call parameter rule.

### 5. Keep Configuration Validation In-Process

The removed invalid-registry CLI test duplicated behavior already covered by `ConfiguredUnitRegistryProviderTest`. Keep
those direct tests as the primary validation of missing classes, invalid factory contracts, and factory failures.

Retain `ConfiguredRegistryAnalysisTest` as the dependency-injection integration test proving that a valid
`parameters.yumemi.registryFactory` value reaches every extension path.

If configuration-schema validation needs dedicated coverage later, construct PHPStan's container in-process with a
known-invalid test NEON file and assert the resulting exception. Do not restore a CLI subprocess solely for this case.

### 6. Correct The Documentation

Update `docs/phpstan-extension.md` after the harness changes:

- say that tests run the configured level-max rule set in-process;
- describe `CompositeRule` as a broad extension-interaction harness;
- avoid claiming exact CLI equivalence;
- retain the explanation of why in-process coverage is valuable;
- mention that analyzed declarations may be supplied through declaration-only bootstrap fixtures.

### 7. Keep The Nix Update Separate

The `flake.lock` update and corresponding `flake.nix` change are valid together, and `nix flake check` passes with both
present. They are unrelated to the PHPStan harness and should be committed separately.

At the time of this review, `flake.lock` is staged while `flake.nix` is not. Do not commit that index state: the updated
pre-commit-hooks input requires the new `hooks.treefmt.package` location in `flake.nix`.

## Suggested Implementation Order

1. Add the level-max test configuration and make every subclass merge the base configuration.
2. Add the structured diagnostic helper and restore identifier assertions.
3. Split declaration-only bootstrap fixtures from analyzed programs.
4. Tighten `CompositeRule` checks and comments.
5. Correct `docs/phpstan-extension.md`.
6. Run the complete verification suite.
7. Commit the PHPStan harness independently from the Nix input update.

## Acceptance Criteria

- The PHPUnit suite contains no PHPStan subprocess invocation.
- The in-process container reports explicit- and implicit-`mixed` checking as enabled.
- Public Yumemi diagnostic identifiers are asserted directly.
- Relevant PHPStan core identifiers remain asserted where their behavior is part of the integration contract.
- No test class loads an executable file from `tests/PHPStan/data` with `require` or `require_once`.
- Every analyzed fixture is processed once per test.
- `vendor/bin/phpunit --colors=never` passes.
- `vendor/bin/phpstan analyse --no-progress --error-format=raw` passes.
- `composer cs` passes.
- `nix flake check` passes when the Nix changes are included.

This is internal test and documentation work. It does not require a changelog entry unless implementation reveals or
changes user-visible PHPStan behavior.

## Verification notes (2026-07-27)

Confirmed against the code and the PHPStan phar before deferring:

- **Level.** `RuleTestCase` starts from PHPStan's level-8 base (`conf/bleedingEdge.neon`); the current harness enables
  neither `checkExplicitMixed` nor `checkImplicitMixed`, so the "level-max" claim in its comments is inaccurate. The
  genuine deltas are `checkExplicitMixed: true` (level 9) and `checkImplicitMixed: true` +
  `autowiredAttributeServices: level: 10` (level 10), copied verbatim from `conf/config.level9.neon` /
  `config.level10.neon`. For the current fixtures (no `mixed`) enabling them changes no outcomes, so this is primarily
  an honesty fix. There is also a stale `coreRuleClasses()` reference in `InProcessAnalysisTestCase`'s docblock (the
  method was removed).
- **Identifiers.** `RuleTestCase::analyse()` compares message/line/tip but not identifier; the converted tests assert
  **zero** identifiers. `Error::getIdentifier(): ?string` exists, so a structured helper over `gatherAnalyserErrors()`
  can restore them.
- **Fixture execution / `bootstrapFiles`.** The test container executes NEON `bootstrapFiles` (`PHPStanTestCaseTrait`
  `require_once`s them), so declaration-only bootstrap fixtures work — but several fixtures conflate a _declaration_
  error (e.g. `yumemi-tag-native-mismatch.php`, `yumemi-tag-return-enforced.php` line 14) with a _call-site_ error, so a
  clean sink→bootstrap split is only straightforward for pure-scaffolding fixtures.
- **`scanFiles` (alternative to splitting).** `BetterReflectionSourceLocatorFactory` reads the `scanFiles` parameter, so
  a test-only neon `scanFiles: [<fixture>]` should make a fixture's own functions resolvable **from source** — no
  execution, no global-namespace pollution, and no need to split fixtures (preserving deliberately colocated designs
  such as `unit-real-world-native.php`, whose sinks are one-lined next to each case on purpose). Needs a short spike to
  confirm that analysing a file and `scanFiles`-ing it coexist in `RuleTestCase` without a double-declaration;
  namespaced `require_once` is the fallback.
- **Commit hygiene.** The `flake.lock` / `flake.nix` pre-commit-hooks bump is independent of the harness and must be
  committed with **both** files together (the bumped input needs `hooks.treefmt.package`).
