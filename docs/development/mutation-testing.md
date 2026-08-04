# Mutation Testing

[Infection](https://infection.github.io/guide/) measures how effectively the PHPUnit suite detects small changes to
covered source code. It changes one expression at a time, runs the tests that cover that expression, and classifies the
resulting mutant.

The project allows Infection `^0.32.6`. The current lock file selects 0.32.6 because the default Nix development shell
runs PHP 8.2, while newer Infection releases require PHP 8.3. PCOV in the development shell provides the line coverage
Infection uses to select tests.

## Running Infection

Run a focused campaign over `Rational`, `Dimension`, and the core analyzer layer:

```console
composer infection:core
```

Run the full campaign over all configured handwritten runtime source:

```console
composer infection
```

Run the focused campaign over the directly tested PHPStan type, expression, interval, and operator layer:

```console
composer infection:phpstan:core
```

Run the broader experimental campaign over all handwritten PHPStan adapter source:

```console
composer infection:phpstan
```

CI runs the same full campaign with minimum total and covered MSI thresholds of 86%:

```console
composer infection:ci
```

CI runs the full PHPStan campaign separately with minimum total and covered MSI thresholds of 85%:

```console
composer infection:phpstan:ci
```

The runtime commands exclude the generated Bison parser and the PHPStan adapter. The generated parser should be tested
through its grammar and regeneration checks. PHPStan uses a separate configuration and separate report files so its
slower analyzer-backed tests and initial score do not affect the established runtime campaign or CI floor. The PHPStan
campaign runs in a separate CI job, while the focused command remains available for local investigation.

Most PHPStan adapter tests execute in the PHPUnit process through direct unit tests, `RuleTestCase`, `PHPStanTestCase`,
or `TypeInferenceTestCase`. Infection can therefore replace those source paths normally. CLI integration tests that
spawn the real PHPStan binary remain useful end-to-end checks, but their child processes do not reliably execute the
active mutant and should not be treated as mutation coverage.

PHPStan bundles `phpstan/phpdoc-parser` inside its PHAR rather than exposing it through Composer's project autoloader.
The PHPStan Infection configuration loads `infection.phpstan.bootstrap.php` only in Infection's mutation generator so it
can reflect adapter classes that extend those bundled AST visitors. This does not add a runtime dependency or alter the
autoloading used by PHPUnit, PHPStan, or consumers.

Infection writes the runtime campaign's complete escaped-mutant report to `infection.log` and aggregate counts to
`infection-summary.log`. PHPStan campaigns use `infection-phpstan.log` and `infection-phpstan-summary.log`. These files
are ignored by Git; CI uploads both report sets as separate artifacts.

## Reading Results

The most useful result categories are:

- **Killed:** a test failed after the mutation. The suite detected the changed behavior.
- **Escaped:** the tests still passed. This may reveal a missing assertion, but some mutations are behaviorally
  equivalent to the original program.
- **Uncovered:** no test executed the mutated line.
- **Errored:** the mutant caused an unexpected test-process error.
- **Timed out:** the mutant did not finish within the configured limit. Loop-boundary mutations can legitimately cause
  this, but repeated unexplained timeouts need investigation.

The mutation score indicator (MSI) is the percentage of all generated mutants that were defeated. Covered MSI ignores
uncovered mutants and is usually the clearer measure of assertion quality. CI enforces both total and covered MSI floors
of 86% for runtime source and 85% for PHPStan source, so it detects regressions in assertion quality as well as mutation
coverage. The thresholds intentionally remain below the current scores to allow minor tool and runtime variation.

## Investigating An Escape

Start with mutants that change observable behavior. Add an assertion for the intended API contract, rather than an
assertion coupled to private implementation details. Do not contort a test to kill a behaviorally equivalent mutant.

Each report entry includes a stable ID for the current source revision. Re-run one mutant while investigating it:

```console
vendor/bin/infection --no-progress --filter=src/Number/Rational.php --id=<mutant-id>
```

Mutation IDs can change when the corresponding source changes. After killing an individual mutant, run the relevant core
campaign again to catch related mutations and verify the aggregate result.
