# Benchmarking

Yumemi uses [PHPBench](https://phpbench.readthedocs.io/) to measure representative runtime workflows. Benchmarks live
under `benchmarks/` and use isolated UDUNITS2 registries so measurements do not depend on process-global state from
`Units::default()`.

## Running The Suite

Run the complete suite from the repository root:

```console
composer benchmark
```

PHPBench records multiple iterations and warmup revolutions, then reports aggregate timing and relative standard
deviation. A high relative standard deviation means the result should not be used to justify an optimization without
rerunning it under quieter conditions.

Run one group while investigating a subsystem:

```console
composer benchmark -- --group=parsing
composer benchmark -- --group=quantity
composer benchmark -- --group=catalog
```

The CI smoke command overrides each subject to one measured revolution and one warmup revolution:

```console
composer benchmark:smoke
```

It verifies benchmark discovery, setup, and execution. It does not establish a performance floor.

## Native Parser Comparison

The native-parser comparison is deliberately separate from the ordinary suite because it requires a compatible
`ext-yumemi` shared library. Run it against a locally built extension:

```console
make benchmark-native-parser YUMEMI_EXTENSION_PATH=/path/to/yumemi.so
```

Pass temporary PHPBench overrides through `PHPBENCH_OPTIONS`, for example:

```console
make benchmark-native-parser \
    YUMEMI_EXTENSION_PATH=/path/to/yumemi.so \
    PHPBENCH_OPTIONS='--iterations=10 --revs=200'
```

The paired subjects run both parsers with the same extension-loaded PHP configuration over simple, compound, and nested
Unicode expressions. They compare syntax-only parsing and parsing followed by catalog resolution and reduction. Both
paths bypass the process-local AST cache; the native path includes the atomic ABI check used on a real cache miss. The
parse-and-resolve subjects create a fresh resolver for every measured revolution so catalog lookups are not satisfied by
its process-local cache; the immutable catalog registry is prepared outside the timing window. Setup verifies that both
backends produce equal AST classes, exact lexemes, tree shapes, and source spans before any timing is accepted. Existing
warm parsing subjects remain the evidence for cache-hit behavior, which returns before backend selection.

## PHPStan Analysis

The PHPStan benchmark is a separate end-to-end harness because its subjects are complete analyzer processes rather than
in-process runtime operations:

```console
composer benchmark:phpstan
composer benchmark:phpstan -- --cases=50 --iterations=3 --workload=native,quantity
composer benchmark:phpstan -- --cases=200 --workload=all
```

The harness generates deterministic fixtures for extension-free and Yumemi-enabled startup, extension-free and
Yumemi-enabled scalar analysis, PHPDoc type resolution (`types`), native operators and ranges (`operators`), `abs()`
preservation (`preserving`), `min()`/`max()` inference (`extrema`), `sqrt()` inference (`roots`), binary math inference
(`binary-math`), their combined `builtins` workload, extension-free and Yumemi-enabled native helpers (`helper-baseline`
and `helpers`), combined branded inference, quantity and affine inference, optional `@yumemi-*` promotion, and a mixed
application workload. Focused workloads also isolate exact `round()` inference (`rounding`), branded `intdiv()` and
`pow()` inference (`integer-math`), angle conversion and trigonometric inference (`angles`), and native array
aggregation plus branded `range()` construction (`aggregation`). Every measured process receives a fresh PHPStan
temporary directory, preventing the result cache from skipping analysis. Repeated unit strings within one fixture are
intentional: they exercise parser and semantic caches during a realistic long-running analysis process.

The `baseline`/`bootstrap` pair compares minimal analyzer startup without and with Yumemi. The `plain`/`scalar` pair
compares the same ordinary numeric fixture without and with Yumemi, exposing adapter callbacks that decline unbranded
expressions. Compare scalar and branded workloads only as directional evidence: their source shapes are similar but not
identical. The `helper-baseline`/`helpers` pair is byte-identical and differs only in whether Yumemi is enabled, so it
isolates extension overhead for the native helper fixture. Use multiple fixture sizes to distinguish mostly fixed
startup cost from work that scales with analyzed declarations. The reported wall times are local diagnostic
measurements, not cross-machine performance guarantees or CI thresholds. The default run uses the representative
workloads; `--workload=all` additionally runs the focused type, operator, preserving-function, extrema, root,
binary-math, rounding, integer-math, angle, aggregation, combined-built-in, and controlled helper subjects. Each
workload is deliberately one generated source file, so PHPStan has nothing to parallelize; the harness measures stable
single-file analysis rather than project-scale parallel throughput.

## Hardware Performance Counters

On Linux, the Nix development shell builds and loads [`php-perfidious`](https://github.com/jbboehr/php-perfidious). Its
[`phpbench-perfidious`](https://github.com/jbboehr/phpbench-perfidious) adapter adds a separate PHPBench executor and
report for Linux `perf_events` counters:

```console
nix develop
composer benchmark:perf
composer benchmark:perf -- --group=comparison
```

The profile records CPU clock, retired instructions, page faults, and context switches. The adapter is installed from an
unreleased development branch that normalizes counters by revolutions and supplies the custom report. Composer locks the
exact commit.

The normal `benchmark` and `benchmark:smoke` commands do not load the adapter or require `ext-perfidious`. Hardware
counters are deliberately excluded from CI because GitHub-hosted virtualization may not expose them. Local execution may
also require a less restrictive `kernel.perf_event_paranoid` setting or `CAP_PERFMON` depending on the host and selected
events.

Counter values include PHPBench executor and dynamic method-call overhead. Use them for comparisons between equivalent
subjects on the same host, not as exact instruction counts for an isolated PHP expression.

## Comparing A Change

Store a tagged run before changing an implementation:

```console
composer benchmark -- --store --tag=before_change
```

Run the candidate implementation against that local reference:

```console
composer benchmark -- --ref=before_change
```

PHPBench stores tagged runs under `.phpbench/`, which is intentionally ignored. Results depend on the PHP version,
extensions, INI configuration, CPU scaling, system load, and operating system; machine-specific measurements are not
committed as project-wide guarantees.

## Interpreting Subjects

Cold subjects construct their registry and `Units` context inside the measured method. They represent startup and first
resolution costs. The cold compound-parse subject varies insignificant source whitespace so each measured input misses
the process-local syntax cache. The uncached warm-context parse subject varies whitespace while retaining one
initialized `Units` context, isolating parsing and resolution from registry construction. Cached warm subjects
explicitly prime the relevant syntax, resolved-expression, or lookup cache before measurement. They represent repeated
work within a long-lived runtime or PHPStan process.

Expression subjects receive preconstructed expressions when isolating reduction or normalization. Quantity subjects
reuse immutable operands. Formatting subjects distinguish construction and first lookup from repeated use of one
formatter. Catalog descriptor subjects remain separate because introspection is informational work rather than a normal
arithmetic hot path.

Native-versus-quantity subjects keep boundary construction separate from repeated arithmetic. Plain and pre-branded
native values measure the same scalar operation, while separate subjects include `unit()` validation, `Quantity`
construction, and native or exact unit conversion. Compare equivalent subjects rather than treating one result as a
summary of an entire representation.

The conversion and quantity subjects pair repeated string boundaries with equivalent pre-parsed `Expr` inputs. Compare
`benchQuantityValueIn` with `benchQuantityValueInWithParsedUnit`, and compare the two quantity-construction subjects, to
isolate parsing and context binding from conversion and object construction. `benchWarmConversionFactor` and
`benchPointQuantityValueIn` exercise the existing cached string-resolution paths, so they help distinguish a parsing
cost from evidence for a separate pairwise conversion-plan cache.

Preferred-unit subjects separate profile construction from repeated application. Compaction subjects separately measure
the first family discovery in a fresh context and repeated selection from a cached family; compare those two before
attributing compaction cost to ordinary quantity conversion.

Use measurements to identify an optimization target before changing cache ownership or expression semantics. A faster
microbenchmark is not sufficient if the corresponding operation does not materially contribute to an application or
PHPStan analysis workload.

## Recorded Investigations

These measurements were recorded during the initial runtime and PHPStan performance investigations through 2026-08-15
and moved here from the roadmap on 2026-09-06. They describe those implementations on the same local PHP 8.2 host, not a
fresh benchmark of the current revision or portable regression floors. The current internal cache budgets are recorded
in [Cache Retention](architecture.md#cache-retention). Use the controls above when repeating a comparison.

### Runtime Parsing and Persistence

- Paired helper-boundary benchmarks and local hardware-counter profiles identified repeated parsing as a concrete
  runtime cost. Before caching, repeated `Quantity::valueIn()` with a compound string target took about 17 times the
  wall time and 15 times the retired instructions of the equivalent pre-parsed target; string-based quantity
  construction took about 9 times both. Formatting, normalization, quantity parsing, point construction, and affine
  delta derivation showed the same parser-heavy behavior.
- On the same PHP 8.2 host after caching, warm compound `parse()` fell from about 55 to 0.23 microseconds, string and
  pre-parsed `Quantity::valueIn()` converged at about 4.5 and 4.2 microseconds, and string normalization converged with
  pre-parsed normalization at about 15 microseconds. Formatting fell from about 36 to 10 microseconds, point
  construction from about 49 to 2.7, affine delta derivation from about 34 to 1.9, and `parseQuantity()` from about 72
  to 33.
- Persistence validation is intentionally substantial. Representative quantity and point deserialization took about 205
  and 112 microseconds before caching and about 87 and 27 afterward. Restoration still revalidates normalized units,
  dimensions, origins, and scales; preserve those semantic seals rather than pursuing lower timings by weakening them.
- Representative rational arithmetic and decimal rendering remained below 4 microseconds, cached dimensions and
  compatibility below 0.4 microseconds, custom registry overlay construction below 0.4 milliseconds, and full-catalog
  description below 2 milliseconds. These measurements do not justify dedicated optimization work.

### PHPStan Inference and Memoization

- On the same PHP 8.2 host with 400 generated cases and isolated result caches, Yumemi-enabled startup took about 0.88
  seconds versus 0.86 without the extension, while ordinary scalar analysis took about 3.98 seconds with Yumemi versus
  3.77 without it. Focused branded workloads took about 1.2 seconds for PHPDoc type resolution, 2.28 for operators and
  ranges, 1.48 for `abs()`, 2.78 for `min()`/`max()`, 1.38 for `sqrt()`, 3.48 for the composite built-ins workload, and
  2.99 for native helpers. Combined native, quantity/point, annotation-promotion, and mixed workloads took about 4.98,
  3.49, 1.78, and 3.68 seconds respectively. The composite result is not an independent optimization target. These
  results are linear enough to reject a broad scaling defect, but identify extrema and helper analysis as the first
  candidates for deeper profiling.
- Dynamic return/expression inference and companion diagnostic rules both call the same `analyseCall()` methods for
  helpers, extrema, and roots. A focused 400-case extrema experiment safely memoized analysis by exact AST node and
  `Scope`, but moved the local median only from about 2.898 to 2.886 seconds (roughly 0.4%); the cache was therefore
  discarded. Do not apply node-level memoization to helpers or roots by analogy. Profile the helper path to identify a
  material repeated operation before adding cache state; root analysis is already comparatively cheap.
- The 2026-08-09 native-helper profiling pass measured a byte-identical 400-case pair at about 2.766 seconds without
  Yumemi and 2.989 seconds with it, placing the extension's helper-fixture cost near 222 milliseconds. Focused
  one-helper pairs attributed roughly 114 milliseconds each to `unit()` and `unit_factor()` and 124 milliseconds to
  `unit_to()`, so no helper is a singular hotspot. In a separate 20-case Xdebug profile, all helper inference and
  diagnostic entry points accounted for about 104 milliseconds of 3.61 instrumented seconds; parser calls accounted for
  49 milliseconds, argument lookup for 3.3, and finite-string extraction for 1.3. The rule and return extensions receive
  different `FiberScope` and `MutatingScope` wrappers, so an exact node-and-scope cache cannot share their analyses,
  while a node-only cache would risk stale scope-dependent types. Retain the controlled benchmark, but do not add helper
  cache state without a new profile identifying safely reusable material work.
- The 2026-08-15 PHPStan benchmark expansion added focused rounding, integer-math, angle, and aggregation workloads. At
  400 generated declarations on the same PHP 8.2 host, median isolated-process times were about 1.29 seconds for branded
  type resolution, 2.49 for `round()`, 2.69 for `intdiv()` plus `pow()`, 4.19 for angle and trigonometric functions, and
  2.79 for `array_sum()`. Measurements at 50 and 200 declarations scaled approximately linearly; a noisy first
  aggregation sample was not reproduced in seven isolated reruns. No source-level PHPStan profile is justified without a
  nonlinear or application-observed regression.

### Preferred and Compact Selection

The 2026-08-15 runtime expansion accompanied the focused PHPStan workloads described above.

- The corresponding runtime subjects measured preferred-profile construction at about 16.3 microseconds, repeated
  profile application at 12.0 microseconds, cached engineering compaction at 21.7 microseconds, and first compaction in
  a fresh context at 25.0 microseconds. The small first-use premium confirms that the catalog index and family cache are
  effective; do not add another selection cache based on these measurements.
