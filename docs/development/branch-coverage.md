# Branch Coverage

Xdebug can collect branch and path coverage that PCOV does not expose. This is an occasional diagnostic tool for finding
untested decisions in focused handwritten runtime code; it is intentionally absent from CI and `nix flake check`.

## Running An Audit

Enter the dedicated shell and run the default audit:

```console
nix develop .#xdebug
composer coverage:branch
```

The default runs the complete PHPUnit suite while reporting only `src/Number`. Running every test captures indirect
coverage of `Rational` through higher-level APIs without asking Xdebug to enumerate paths for the entire source tree.
The text report is written to `coverage/branch/coverage.txt`, and the browsable report is written to
`coverage/branch/html/`. Both locations are ignored by Git.

Retarget the source directory and, when a quicker exploratory run is sufficient, the tests:

```console
make coverage-branch BRANCH_COVERAGE_SOURCE=src/Analyzer
make coverage-branch BRANCH_COVERAGE_SOURCE=src/Analyzer BRANCH_COVERAGE_TESTS=tests/Analyzer
```

PHPUnit's command-line coverage filter accepts directories, not individual files. Restricting the test set can
understate coverage from callers elsewhere in the suite. Use it to investigate a specific decision, then omit
`BRANCH_COVERAGE_TESTS` when recording the subsystem's aggregate result.

## Interpreting Results

Branch coverage asks whether each decision outcome ran. Path coverage asks which complete combinations of decisions ran
through a function and can grow combinatorially, so its percentage is informational rather than a target. Start with
uncovered branches in code whose alternatives have observable behavior; do not add tests solely to increase a path
percentage or exercise impossible combinations.

Keep PCOV for normal coverage and Infection. Xdebug path collection is materially slower and should remain focused on
one subsystem at a time. No branch or path floor is enforced until repeated audits establish a stable, useful baseline.

## Recorded Audits

These are point-in-time diagnostics, not enforced floors:

- The 2026-08-04 focused `src/Registry` audit covered 98.95% of branches and 98.65% of lines. It added contract tests
  for malformed UDUNITS2 catalog shapes, malformed and empty builder definitions, transactional duplicate batches, and
  introspection in the presence of unrelated broken aliases. The remaining reported outcomes depend on duplicate PHP
  array keys, mirrored constructor states already rejected earlier, or post-match empty values excluded by the matching
  expression; tests should not synthesize impossible states merely to reach 100%.

The next useful focused targets are `src/Catalog`, `PointQuantity`, formatting, and parser diagnostics.
