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

Run the full CI campaign over all configured handwritten runtime source:

```console
composer infection
```

Both commands exclude the generated Bison parser and the PHPStan adapter. The generated parser should be tested through
its grammar and regeneration checks. PHPStan mutation testing is deferred until the PHPUnit-only results have a stable,
understandable baseline.

Infection writes the complete escaped-mutant report to `infection.log` and aggregate counts to `infection-summary.log`.
These files are ignored by Git and uploaded by the mutation CI job.

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
uncovered mutants and is usually the clearer measure of assertion quality. Neither score is currently enforced in CI;
the job reports results and fails only for an infrastructure or test-suite failure.

## Investigating An Escape

Start with mutants that change observable behavior. Add an assertion for the intended API contract, rather than an
assertion coupled to private implementation details. Do not contort a test to kill a behaviorally equivalent mutant.

Each report entry includes a stable ID for the current source revision. Re-run one mutant while investigating it:

```console
vendor/bin/infection --no-progress --filter=src/Number/Rational.php --id=<mutant-id>
```

Mutation IDs can change when the corresponding source changes. After killing an individual mutant, run the relevant core
campaign again to catch related mutations and verify the aggregate result.
