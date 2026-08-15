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

If path collection makes a complete-suite run impractical because a generative or property test expands dramatically
under Xdebug, record the exact focused test scope instead of presenting its percentage as an aggregate. Include the
relevant direct and integration callers, then run the complete suite normally to retain behavioral verification.

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
- The 2026-08-05 focused `src/Formatter` audit covered 86.36% of 110 branches and 96.00% of 150 executable lines across
  the formatter, parser/formatter round-trip, quantity, point, and `Units` tests. It added a contract proving that
  symbol selection prefers the shortest codepoint length even when catalog lexical order differs, including
  deterministic handling of malformed UTF-8 catalog text. Of 125 focused mutants, 119 were killed; the six survivors are
  equivalent cache, array-reindexing, prefix-contract, regular-expression-flag, dominated-comparison, or scalar-coercion
  changes. The remaining renderer branches are eliminated by canonical reduction before rendering, while the uncovered
  unit and prefix fallbacks require a registry whose exact lookup and descriptor APIs contradict each other. A
  complete-suite Xdebug run was stopped when bounded generative round trips took several minutes per data case; the
  recorded percentage therefore belongs only to the stated 156-test scope, with the complete suite verified normally.
- The 2026-08-06 focused handwritten `src/Parser` audit covered 99.31% of 145 branches and 98.91% of 183 executable
  lines across 330 parser, runtime, analyzer, PHPStan-adapter, conformance, and native-helper tests. The branch
  configuration now excludes generated `Parser.php`, whose state-machine branches measure Bison output rather than
  authored decisions. Added contracts cover addition and subtraction AST identity, nonnumeric negation, absent parser
  locations, incomplete exception context, malformed UTF-8, multiline and carriage-return display, exact long-excerpt
  boundaries, and clipped highlights. The audit found and fixed an off-by-one that displayed a leading omission marker
  when no prefix had been omitted. Focused mutation testing killed 186 of 193 mutants for 96% covered MSI; the seven
  survivors alter dominated guards or choose an equally valid excerpt boundary. The sole uncovered branch is
  `ParserUtils::parseString()`'s defensive `parse() === false` fallback: the generated parser either succeeds or invokes
  the throwing error handler.
- The 2026-08-10 parser resource-budget follow-up covered 98.84% of 172 branches and 98.83% of 256 executable lines in
  the handwritten `src/Parser` scope across 286 focused parser and integration tests. `Lexer` reached 100% of its 70
  branches and 101 executable lines. Added contracts exercise eager lexer input rejection, nesting separated by other
  tokens, depth recovery after balanced groups, and all four limit-message descriptors. The remaining parser outcomes
  are the unknown-limit diagnostic fallback and the generated parser's defensive `parse() === false` fallback. A
  separate focused analyzer run exercised both caller-span remapping paths for oversized catalog and prefix definitions;
  its percentages are not an aggregate because only direct resolver and registry-builder tests were included. Focused
  mutation testing across the lexer, parser utilities, limit exception, and unit resolver generated 239 mutants and
  killed 205, improving covered MSI from 82% to 86%; 33 escaped and one timed out. The surviving changes are equivalent
  cache and ranking mutations, unreachable defensive parser states, or exception-code and prose details, while the
  timeout removes circular-resolution termination. No runtime defect was found.
- The 2026-08-15 repository-wide mutation refresh generated 3,813 runtime mutants and 3,341 PHPStan mutants. The runtime
  campaign killed 3,454, with 259 escaped, 85 timed out, and 15 errored or syntactically invalid; the PHPStan campaign
  killed 3,007, with 291 escaped, 38 timed out, and five errored. Triage added observable contracts for cross-context
  same-unit arithmetic, unknown compaction families, and all `UnitSemantics` capability combinations. Other sampled
  survivors were equivalent normalization, cache, ordering, native-return, or exception-prose changes, or defensive
  states excluded by registry and PHPStan type contracts.
- A corresponding 2026-08-15 focused runtime branch audit ran 118 quantity, compaction, preferred-profile, and catalog
  semantic tests. `PreferredUnitProfile` covered all 20 branches and 42 executable lines, while `UnitSemantics` covered
  all four branches and two executable lines. `Units::compactQuantity()` covered 44 of 52 branches and 72 of 81 lines;
  its remaining outcomes require contradictory registry introspection, malformed prefix definitions, unavailable
  prefixed candidates, or failed conversions after successful descriptor resolution. These percentages belong only to
  the stated focused scope.
- The 2026-08-15 focused PHPStan audit ran 99 direct resolver and rule tests with 10,623 assertions. It covered every
  branch and line in `UnitIntegerRangeMath`, all 23 branches and 18 lines in `UnitUnionTypeHelper`, 130 of 135 branches
  in angle inference, 69 of 73 in `array_sum()` inference, and 164 of 170 in binary-math inference. Five PHP 8.4 native
  `RoundingMode` cases were skipped under PHP 8.2. The remaining resolver branches are throwable guards, impossible
  calls outside registered function names and signatures, disabled canonical-angle inference, or unrepresentable direct
  union alternatives; no implementation defect was found.

The subsequent cross-cutting verification added a machine-checked inventory of PHPStan diagnostic identifiers and their
documented suppression boundaries. It validates both documentation lists, emitting implementation keys, and the set of
identifier-specific local-ignore fixtures; focused rule tests prove those ignores match real diagnostics without
freezing human-readable prose.
