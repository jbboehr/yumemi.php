# Rational Backend Evaluation

**Date:** 2026-08-15

## Question

Could [`brick/math`](https://github.com/brick/math) materially simplify Yumemi's exact arithmetic, and could its
`BigInteger` support remove the mandatory GMP dependency without weakening the public `Rational` contract?

This was a disposable comparison rather than a proposed dependency change. Yumemi's public behavior, conformance cases,
and `v0.1.0` persistence fixtures remained fixed throughout the evaluation. No production dependency or source change
was made.

## Existing Contract

Yumemi's exact-number behavior is broader than normalized fractional arithmetic:

- `Rational` exposes public GMP numerator and denominator values and accepts GMP constructor arguments;
- native serialization from `v0.1.0` contains serialized GMP objects and must remain readable;
- decimal parsing deliberately accepts one bounded grammar rather than every representation understood by a general
  numeric library;
- `toInt()` truncates toward zero, while `toIntExact()` requires an integral value;
- fixed-scale and significant-digit output use PHP's complete `RoundingMode` policy;
- binary64 output is correctly rounded and either fails on overflow or underflow, or follows an explicit
  `FloatRangePolicy`;
- exact roots accept degrees through 10,000 and reject irrational results; and
- exact finite binary64 decoding, JSON shapes, Yumemi exception categories, and decimal-exponent limits are established
  behavior.

GMP also appears outside `Rational`. Nine authored source files contain 249 GMP type or function references. Most are in
`Rational` and PHPStan integer-range arithmetic, with smaller dependencies in binary64 decoding, AST conversion,
exponent validation, affine conversion, and engineering-prefix compaction. Removing `ext-gmp` is therefore a
cross-cutting compatibility change rather than an isolated class substitution.

## Brick Capabilities

The spike used `brick/math` 0.19.1, which requires PHP 8.2 but no arithmetic extension. Its `BigInteger` implementation
automatically selects GMP when available, then BCMath, then a pure-PHP calculator. A `php -n` process with neither GMP
nor BCMath successfully performed exact `BigRational` arithmetic, confirming that the native fallback is functional.

| Yumemi requirement                        | Brick support                                                    | Remaining Yumemi work                                                      |
| ----------------------------------------- | ---------------------------------------------------------------- | -------------------------------------------------------------------------- |
| Reduced fractions and arithmetic          | Direct `BigRational` support                                     | Preserve Yumemi constructors and exception categories                      |
| Integer powers                            | Direct `BigRational` support                                     | Preserve Yumemi's exponent bound                                           |
| Exact arbitrary-degree roots              | `BigInteger::nthRoot()` supplies the primitive                   | Root numerator and denominator separately and translate non-exact failures |
| Fixed-scale decimal rounding              | `BigRational::toScale()` maps all native PHP rounding modes      | Preserve Yumemi argument validation and exceptions                         |
| Minimal terminating decimal               | `toBigDecimal()` handles reduced terminating fractions           | Preserve Yumemi failure category and text-independent contract             |
| Significant-digit plain/scientific output | No corresponding `BigRational` operation                         | Retain Yumemi's rounded-coefficient and rendering implementation           |
| Truncating and exact native integers      | Brick's `BigRational::toInt()` requires exactness                | Retain separate Yumemi truncating and exact policies                       |
| Strict binary64 range policy              | Brick returns infinity or signed zero on range loss              | Retain Yumemi's correctly rounded conversion and `FloatRangePolicy`        |
| Exact finite binary64 decoding            | No replacement for Yumemi's decoder                              | Retain `BinaryFloat` semantics                                             |
| JSON                                      | Brick serializes a rational as one string                        | Retain Yumemi's numerator/denominator object shape                         |
| Native serialization                      | Brick serializes `BigInteger` objects containing decimal strings | Version Yumemi's writer and preserve a reader for released GMP payloads    |

A deterministic differential corpus compared 5,000 bounded rational pairs through construction, arithmetic, division,
integer powers, comparison, all eight fixed-scale rounding modes, and ordinary finite binary64 output. Additional cases
covered terminating decimals and exact roots. All 85,453 observable comparisons agreed. This establishes useful
primitive compatibility; it does not erase the policy differences above.

## Performance

The microbenchmark used PHP 8.2.32 on an AMD Ryzen 9 9950X3D with CLI OPcache disabled. Each backend ran in a separate
process. The table reports the median nanoseconds per operation across nine samples; checksums were compared before the
results were accepted.

| Operation                  | Yumemi/direct GMP | Brick/GMP | Brick/BCMath | Brick/native PHP |
| -------------------------- | ----------------: | --------: | -----------: | ---------------: |
| Construct reduced fraction |               591 |     2,278 |        4,747 |            4,610 |
| Parse decimal              |             2,056 |     2,888 |        5,314 |            5,022 |
| Add                        |               901 |     4,934 |       10,028 |            9,961 |
| Multiply                   |               720 |     4,411 |        9,529 |            9,944 |
| Divide                     |               732 |     3,524 |        7,530 |            7,482 |
| Seventh power              |               982 |       873 |        1,186 |            1,698 |
| Exact fifth root           |             1,522 |     4,258 |       35,204 |        1,000,690 |
| Compare                    |               247 |     2,783 |        2,575 |            2,393 |
| Fixed decimal              |             1,196 |     3,353 |        3,624 |            2,963 |
| Exact decimal              |             3,399 |     2,791 |        3,708 |            4,761 |
| Binary64 output            |             1,717 |     4,233 |        4,691 |            6,244 |

Brick occasionally wins where its operation can avoid work already known to be exact, notably integer power and the
tested terminating decimal. Common construction and arithmetic are roughly four to eleven times slower even when Brick
uses GMP, primarily because its immutable objects move values through decimal strings and calculator adapters. The
portable fallbacks are slower again, with arbitrary-degree roots representing the largest observed difference. Peak
allocated process memory was approximately 4 MiB for Yumemi and 6 MiB for each Brick backend, although that coarse
allocator figure is only directional.

These are focused microbenchmarks, not portable performance guarantees. They are sufficient to reject the premise that
Brick would preserve the current direct-GMP cost profile. A future backend change would still require end-to-end
catalog, parsing, conversion, numeric-output, and PHPStan benchmarks against a complete implementation.

## Maintenance Assessment

Delegating normalized arithmetic, powers, fixed-scale rounding, and terminating-decimal detection would remove some
local implementation code. It would not remove significant-digit rendering, strict binary64 conversion, exact float
decoding, bounded decimal parsing, exception translation, persistence migration, or Yumemi's public facade. The current
`Rational` class is 675 lines, but a meaningful portion encodes those project-specific policies rather than generic big
integer arithmetic.

A Yumemi-defined GMP-or-Brick backend interface is not recommended. It would require two execution paths,
backend-neutral value identity, cross-backend operand rules, duplicate testing, and new persistence decisions. If
portability becomes important enough to justify the cost, Brick already provides the useful abstraction: one
`BigInteger` representation with automatic GMP, BCMath, or native calculation underneath it.

## Decision

Retain the direct GMP implementation and mandatory `ext-gmp` dependency for now. Do not add `brick/math`, do not wrap
`BigRational`, and do not introduce a selectable arithmetic backend merely for architectural flexibility.

Reconsider this decision only if supported users demonstrate that installing GMP is a material adoption or deployment
barrier. That reconsideration should be an explicit `0.2` compatibility project rather than an internal refactor:

1. use Brick `BigInteger` as the single internal integer representation and let Brick select its calculator;
2. keep Yumemi's `Rational` facade, exactness rules, rounding APIs, JSON shape, and exception categories;
3. replace GMP-typed public inputs and properties with a deliberately designed backend-neutral surface;
4. version new native-serialization output and preserve migration of every released `v0.1` payload, including operation
   without GMP if the old embedded hexadecimal values can be recovered safely;
5. make GMP an optional accelerator rather than exposing a user-selected backend; and
6. rerun conformance, release-persistence, supported-PHP, consumer, mutation, and representative end-to-end performance
   checks with and without GMP.

The portability benefit is real. The present evidence does not show enough user value or net maintenance reduction to
pay for the compatibility break and runtime cost now.
