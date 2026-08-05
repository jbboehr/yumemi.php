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
- The 2026-08-05 focused `PointQuantity` audit covered 100% of its 83 branches and 132 executable lines. It added
  explicit contracts for string casting and for rejecting a custom-context point restored outside
  `Units::deserialize()`. Focused mutation testing killed all 137 generated mutants. Xdebug reports 37 of 4,132 paths
  because the serialized-payload validator has many compound boolean combinations; its field-by-field malformed-payload
  tests already exercise the observable validation policy, so exhaustive path enumeration would not add useful evidence.
- The 2026-08-05 catalog semantic-core audit covered 98.75% of branches and 99.03% of lines across
  `AffineDeltaUnitSynthesizer` and `UnitDefinitionClassifier`. The synthesizer reached 100% of its 59 branches and 79
  lines after adding direct contracts for compatibility-symbol continuation, same-batch generated lookup, and generated
  entries masking base records. The classifier covered 20 of 21 branches; the remaining fallback requires a nameless
  record excluded by the catalog-record shape. Of 100 focused mutants, 98 were killed, removal of cycle termination
  errored through recursion, and changing a seen-map value from `true` to `false` was equivalent because only key
  presence is observed.
- The 2026-08-05 catalog importer/exporter audit covered 100% of 214 branches and 233 executable lines. It added clean
  domain-error contracts for unreadable files and malformed XML, plus an XML-comment case for ignored non-element name
  children. The Nix-backed `udunits2` test group now regenerates the complete five-file catalog and compares it
  byte-for-byte with `data/udunits2.php`. Of 303 focused mutants, 298 were killed and three were syntax errors; the two
  survivors are equivalent because plural generation receives only names of at least three characters and a nameless,
  symbolless prefix cannot mutate the catalog after its early return is removed.

The next useful focused targets are formatting and parser diagnostics.
