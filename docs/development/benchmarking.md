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
