# Project review: 2026-09-04

This report records ten issues found while reviewing commit `2a67a10d88c57389d82f0606528bda1d7e1b6a18`
(`Update php-yumemi integration`). It is intended for maintainers planning correctness fixes and test improvements. All
findings were open at that snapshot. Reproduction outputs describe that snapshot; subsequent fix status is recorded
separately under the affected finding.

The review covered runtime arithmetic, exact numbers, parsing, registry contexts, serialization, formatting, PHPStan
inference and diagnostics, tests, documentation, and development and packaging checks. Focused experiments used PHP
8.2.32 and PHPStan 2.2.5 with the installed dependencies. The ordinary verification gates passed despite these findings.

Priorities indicate suggested maintenance order. P1 means a high-priority correctness defect. P2 means a defect to
address in normal maintenance. They are not security severity ratings.

| Issue                                                    | Priority | Finding                                                             |
| -------------------------------------------------------- | -------- | ------------------------------------------------------------------- |
| [1](#issue-1-mixed-quantity-and-scalar-operands)         | P1       | Mixed quantity/scalar operands can infer the wrong dimension.       |
| [2](#issue-2-symbol-formatting-collisions)               | P2       | Symbol formatting can change a unit's meaning when reparsed.        |
| [3](#issue-3-mutable-gmp-aliases-in-rational-values)     | P2       | Rational values retain and expose mutable GMP objects.              |
| [4](#issue-4-integer-alternatives-lost-by-unit)          | P2       | `unit()` loses the integer alternative of `int\|float`.             |
| [5](#issue-5-native-division-inferred-as-float-only)     | P2       | Native division incorrectly promises a float.                       |
| [6](#issue-6-diagnostics-depend-on-named-argument-order) | P2       | Reordering named arguments suppresses diagnostics.                  |
| [7](#issue-7-type-resolution-ignores-namespace-identity) | P2       | Type resolution captures unrelated `Quantity` classes.              |
| [8](#issue-8-ordering-on-negative-scales)                | P2       | Negative-scale comparisons violate ordering symmetry.               |
| [9](#issue-9-deserialization-context-crosses-fibers)     | P2       | Deserialization context leaks between Fibers.                       |
| [10](#issue-10-unbounded-semantic-caches)                | P2       | Semantic caches retain arbitrary inputs for the context's lifetime. |

## Reproducing the examples

Treat each PHP example as a separate file and run it from the repository root. Load Composer's autoloader once with:

```shell
php -d auto_prepend_file=vendor/autoload.php /tmp/yumemi-review-example.php
```

For PHPStan examples, save this configuration as `review.neon` in the repository root and analyze the example file:

```neon
includes:
    - extension.neon
parameters:
    level: max
```

```shell
vendor/bin/phpstan analyse -c review.neon --no-progress --error-format=raw /tmp/yumemi-review-example.php
```

The observed outputs below describe the reviewed implementation, including its defects. They are evidence for future
regression tests, not expectations to preserve after a fix. PHPStan diagnostic line numbers depend on where the example
is saved. Examples marked as analysis-only need no runtime invocation.

These maintainer examples are outside the Akashi corpus configured by
[`DocumentationCorpus`](../../tests/Documentation/DocumentationCorpus.php). The corpus covers the root README, public
`docs/pages/` sources, and the selected builder PHPDoc. Passing the public documentation tests alone does not verify
this report's examples.

## Issue 1: Mixed quantity and scalar operands

**First fix, reviewed:** The PHPStan extension now maps each direct operand alternative for `mul()` and `div()`,
preserving known result-unit unions and returning an unbranded quantity when an operand's unit cannot be determined. It
also lets PHPStan evaluate branded quantity unions as unions, so accurate union return declarations do not produce false
`return.unusedType` diagnostics. Regression tests cover integer and rational alternatives, unknown operands, receiver
unions, and accepted and rejected return contracts. The new inference and return-contract regressions were observed
failing before the fix and passing afterward.

Verification for this fix:

- `composer test -- tests/PHPStan/QuantityReturnTypeExtensionTest.php tests/PHPStan/QuantityTypeTest.php tests/PHPStan/UnitTypeNodeResolverIntegrationTest.php`
  passed: 32 tests and 438 assertions.
- `composer check:full` passed: 2,282 tests, 26,149 assertions, and five expected PHP 8.2 skips; documentation,
  formatting, static analysis, and packaged consumer checks also passed.
- `nix flake check --keep-going -L path:<source-snapshot>` passed on `x86_64-linux`, including PHP 8.2 through 8.5. The
  clean snapshot included the new regression fixtures without staging them in the working repository.
- Independent correctness review and test hardening found no additional defect in this slice. The retained hardening
  covers multiple receiver and operand alternatives, an accurate division return contract, and benevolent union
  acceptance.

P1. [`QuantityMethodReturnTypeExtension::combine()`](../../src/PHPStan/QuantityMethodReturnTypeExtension.php) treats a
mixed union as a scalar when it is neither entirely composed of branded quantities nor exactly an unbranded quantity.
The fallback returns the receiver's unit without proving that every alternative is a scalar.

```php
<?php

use jbboehr\Yumemi\Quantity;
use jbboehr\Yumemi\Units;

/**
 * @param Quantity<'second'>|int $factor
 * @return Quantity<'meter'>
 */
function scaleDistance(Units $units, Quantity|int $factor): Quantity
{
    return $units->quantity(1, 'meter')->mul($factor);
}

$units = Units::default();
echo scaleDistance($units, $units->quantity(2, 'second'))->toString(), "\n";
```

PHPStan accepts the function's declared meter result. Runtime prints `2 * meter * second`, which has a different
dimension. Replacing `mul()` with `div()` is also accepted and prints `1/2 * meter / second`. Both inference paths share
the same fallback. This can allow application code to pass dimensional analysis while delivering a quantity with the
wrong unit to its caller.

Infer each direct union alternative and combine its result. If a valid alternative contains an unknown quantity unit,
return an unbranded quantity instead of claiming a specific unit. Only preserve the receiver's unit after establishing
that an operand is a supported scalar.

Add method-inference tests for `Quantity<'second'>|int`, `Quantity<'second'>|Rational`, and unbranded `Quantity|int`
operands. Include an application-level return-contract test like the example so an incorrect brand cannot be hidden by
assertions about internal type descriptions alone.

## Issue 2: Symbol formatting collisions

**Second fix, reviewed:** Prefixed formatting now checks the complete candidate against the effective registry. It
accepts a replacement only when it resolves to the same canonical unit and the same prefix definition and parses as one
unchanged identifier. It tries the canonical concatenation if the symbol cannot be verified, and otherwise retains the
input name. The result stays in the formatter's existing per-name cache; syntax checks use the parser's bounded cache.
This avoids expression normalization during formatting and also preserves symbolic formatting of unknown or unsupported
unit algebra.

The check is deliberately conservative: it does not expand definitions to discover additional equivalent spellings. For
example, `kgram` remains `kgram`, since `kg` and `kilogram` are exact catalog entries rather than the same prefix
decomposition. Exact input `kilogram` still formats as `kg`.

Regression tests cover the bundled collisions, ordinary prefixes and exact symbols, custom dimension and scale
collisions, canonical-name collisions, both fallbacks being shadowed, ASCII and Unicode, quantity output, repeated
formatter use, idempotence, and exact normalized equivalence. Six collision cases failed before the implementation
change and passed afterward. The v1 conformance corpus also records exact conversion factors of one between the two
bundled inputs and their fallback spellings, without changing the fixture schema.

Independent review exposed another case within this fix: `millipercent` formatted as `m%`, which reparses as meter times
percent. `terapercent` and prefixed arc-minute/arc-second symbols had the same token-boundary problem. Eight added
ASCII/Unicode round-trip cases failed before the parser check and passed afterward. Additional tests verify fallback
when a custom candidate has invalid syntax or exceeds the parser's token length limit.

Performance was measured on local PHP 8.2.32 with the expanded `FormattingAndCatalogBench` inputs. The baseline loaded
`ExprFormatter.php` from `4d20fc8` through a temporary PHPBench bootstrap; both runs used the same updated benchmark
class. Sequential runs used nine iterations, 1,000 revolutions, and 100 warmup revolutions. Times below are PHPBench's
reported modes, in microseconds per call:

| Formatting case                       | Before |  After |
| ------------------------------------- | -----: | -----: |
| Preserve, resolved expression         |  7.376 |  7.146 |
| Fresh formatter, resolved expression  | 12.944 | 12.410 |
| Fresh formatter, `kilometer`          | 31.430 | 35.492 |
| Fresh formatter, `milliinch`          | 32.107 | 36.494 |
| Fresh formatter, `millipercent`       | 31.486 | 36.894 |
| Reused formatter, resolved expression |  7.532 |  7.669 |
| Reused formatter, `kilometer`         |  2.881 |  2.819 |
| Reused formatter, `milliinch`         |  2.852 |  2.797 |
| Reused formatter, `millipercent`      |  2.879 |  2.880 |

Formatting a prefixed name with a fresh formatter costs about 4–5 additional microseconds in this measurement (13–17%).
Reused formatters showed no meaningful regression; their observed changes were within the variation in these runs. These
are local microbenchmarks with warmed registry and parser caches, not measurements of the first uncached parse,
application latency, or a cross-version performance guarantee. Reproduce the current measurement with:

```shell
composer benchmark -- benchmarks/FormattingAndCatalogBench.php \
  --filter='bench(PreservedFormatting|ColdSymbolFormatter|WarmSymbolFormatter)$' \
  --iterations=9 --revs=1000 --warmup=100
```

Verification for this fix:

- `composer test -- tests/Formatter tests/Conformance tests/Documentation` passed: 233 tests and 2,438 assertions.
- A deterministic local sweep of all bundled prefixes with `percent`, `arc_minute`, and `arc_second` passed all 240
  ASCII/Unicode checks for formatting idempotence and exact normalized equivalence after reparsing.
- Independent correctness review and test hardening identified the punctuation defect described above; all retained
  regressions passed after its correction.
- `composer check:full` passed: PHPStan, 2,301 PHPUnit tests and 26,261 assertions with five expected skips,
  documentation build/link and example checks, benchmark smoke tests, and consumer package checks.
- `nix flake check --keep-going -L` passed on `x86_64-linux`, including PHP 8.2–8.5, consumers, documentation,
  formatting, and generated-artifact checks. The matrix had 29 expected skips on PHP 8.2/8.3 and 24 on PHP 8.4/8.5. An
  earlier run found Markdown wrapping differences; `nix fmt` corrected them before the passing run.
- `composer cs:fix`, `nix fmt`, and `git diff --check` completed successfully. Other Nix platforms, mutation testing,
  Xdebug branch coverage, and the parser probator were not run for this slice.

P2. [`ExprFormatter::formatResolvedUnitName()`](../../src/Formatter/ExprFormatter.php) selects prefix and unit symbols
independently, then concatenates them. It does not establish that the complete spelling resolves to the original unit.
Exact catalog names take precedence over prefix decomposition, so the result can denote a different dimension.

```php
<?php

use jbboehr\Yumemi\Formatter\FormatOptions;
use jbboehr\Yumemi\Formatter\UnitNameStyle;
use jbboehr\Yumemi\Units;

$units = Units::default();
$options = new FormatOptions(unitNameStyle: UnitNameStyle::Symbol);

foreach (['milliinch', 'kilotonne'] as $name) {
    $symbol = $units->formatText($name, $options);
    echo $name, ' -> ', $symbol, ': ', $units->dimension($name),
        ' -> ', $units->dimension($symbol), "\n";
}
```

Observed output:

```text
milliinch -> min: length -> time
kilotonne -> kt: mass -> length / time
```

`min` resolves to minute and `kt` to knot in the bundled catalog. Custom overlays introduce the same problem, for
example when `km` is defined as `second` and `kilometer` is formatted as `km`. The stored quantity is unchanged, but
reading its formatted unit back can change its meaning. This violates the parser-compatible formatting contract in the
[runtime reference](../pages/reference/runtime.md#formatting).

Validate the complete candidate spelling against the effective registry, including its exact scale. Fall back to an
unambiguous spelling when a collision exists. Canonical spellings also need validation when overlays can shadow a
concatenated name.

Add round-trip tests for these bundled collisions and custom overlays. Compare definitional equivalence, not just
dimensional compatibility, so collisions between differently scaled units of the same dimension are also detected.

## Issue 3: Mutable GMP aliases in rational values

**Third fix, reviewed:** `Rational` now snapshots GMP inputs and keeps its components private. The explicit
`numerator()` and `denominator()` methods return detached GMP copies. Arithmetic inside `Rational` reads its storage
directly. `__serialize()` also returns copies, and restoration uses the same ownership rules as construction.
Quantities, points, and cloned rationals can share a magnitude without exposing it through these APIs.

Compatibility: constructor signatures, GMP component values, JSON shapes, and the version-1 native payload reader remain
unchanged. Direct component property reads are removed: replace `$value->numerator` with `$value->numerator()` and
`$value->denominator` with `$value->denominator()`. Public-property enumeration and reflection visibility also change.
The previous undocumented storage properties were provisional under the compatibility policy. Use `jsonSerialize()` for
an inspectable decimal-string array, or retain a component copy when working with GMP. Private-state introspection,
including reflection and object-to-array casts, is outside the value API and can bypass ordinary PHP encapsulation.

In the historical example below, the constructor-input mutation now leaves the quantity at `1`. Replace the final
`$fraction->numerator` read with `$fraction->numerator()` to run the complete example with the new API; its output is
then `1`, `1`, and `1/2`.

Roave reports both visibility reductions and removals for these two properties. The root compatibility configuration
acknowledges those four exact messages for this provisional-storage correction. A separate exact entry covers the
generated `Parser::BISON_SKELETON` path change introduced by parser integration `04f4dee`, which is internal generator
metadata. Remove these acknowledgements once a release containing the changes becomes the comparison baseline. The
configuration is excluded from release archives.

Verification for this fix:

- The original constructor and serialization regressions were observed failing against the audit baseline. The new
  accessor tests first reported missing methods, and the direct-property rejection test failed before removal of magic
  access. Isolated clone-removal experiments trigger four ownership assertion failures for each accessor; returning the
  wrong component also fails the exact-value assertion.
- `composer test -- tests/Number tests/SerializationTest.php tests/Compatibility tests/Conformance tests/QuantityCompactionTest.php tests/UnitFunctionTest.php tests/Documentation`
  passed: 516 tests and 3,393 assertions, including historical tagged-release persistence and executable documentation.
  GMP object ownership is PHP-specific, so its regressions live in the PHP suite; the language-neutral conformance
  fixtures retain their numeric results.
- `composer check:full` passed: 2,341 tests, 26,349 assertions, and five expected skips, plus static analysis,
  formatting, documentation, benchmark smoke, and packaged consumers.
- `nix flake check --keep-going -L path:<source-snapshot>` passed on `x86_64-linux`, including PHP 8.2 through 8.5,
  native-extension integration, generated artifacts, and consumers. The snapshot included the new ownership tests
  without staging them. This pass preceded the final documentation-only edit, which was checked by Composer. Other
  platforms, full mutation campaigns, and sanitizer runs were not run for this accessor adjustment.
- The historical audit example was rerun with the documented accessor migration and printed `1`, `1`, and `1/2`.
- Roave's installed property detectors reproduced the visibility and removal reports. Its XML schema and exact
  acknowledgements were checked, including rejecting altered reports. The initial committed comparison exposed the
  additional removal reports and the earlier parser metadata change; `composer check:bc` then passed against `v0.1.1`
  with the exact acknowledgements described above.
- Independent correctness review found no actionable regression in the explicit accessors and migrated callers. A
  separate test review added passing checks for fresh copies on successive accessor calls, removal of both magic
  property hooks, and rejection of both former property reads. The retained native-graph regression verifies alias
  identity before restoration, so it cannot pass merely because the new serializer already returns copies.

Performance was compared against `6cdb55d` on PHP 8.2.32, using separate baseline/current processes in alternating
order. Each process warmed each case with 2,000 calls, then measured nine batches of 10,000 calls. The table reports the
median of three process medians in microseconds per call. These are local measurements, not cross-platform bounds. The
component-read comparison uses the old public property and the new explicit accessor, each returning a GMP value.

| Operation                                      | Before (µs) | After (µs) |
| ---------------------------------------------- | ----------- | ---------- |
| Construct from a native integer                | 0.330       | 0.186      |
| Construct from GMP with default denominator    | 0.291       | 0.178      |
| Construct from two GMP inputs, denominator one | 0.269       | 0.321      |
| Construct a reduced fraction                   | 0.566       | 0.572      |
| Add fractions                                  | 0.871       | 0.879      |
| Add integers                                   | 0.600       | 0.651      |
| Multiply fractions                             | 0.726       | 0.735      |
| Read a GMP component                           | 0.035       | 0.078      |
| Native `serialize()`                           | 0.255       | 0.340      |
| Convert a quantity                             | 8.142       | 8.056      |
| Add compatible quantities                      | 19.940      | 19.280     |

The native-integer denominator fast path avoids GMP comparisons and unnecessary input copies. Fractions already obtain
fresh GMP results during normalization. Copies remain necessary when accepting caller-owned integer GMP values or
returning components; their cost grows with integer size. Conversion's zero-offset predicate now uses `isZero()` to
avoid a copied numerator. Unit compaction obtains each component once and reuses the copy. No additional stored
components or decimal-string representation were introduced. Retained benchmarks in `benchmarks/RationalBench.php` cover
integer/GMP construction, component reads, serialization, and arithmetic; run them with
`composer benchmark -- benchmarks/RationalBench.php`.

P2. [`Rational::__construct()`](../../src/Number/Rational.php) directly retains GMP arguments when the denominator is
one. Its public `readonly` numerator and denominator properties also expose mutable GMP objects. PHP's property
restriction prevents replacing the object reference, but does not prevent changing the object's contents.

```php
<?php

use jbboehr\Yumemi\Number\Rational;
use jbboehr\Yumemi\Units;

$input = gmp_init(1);
$value = new Rational($input);
$distance = Units::default()->quantity($value, 'meter');

echo $distance->valueToString(), "\n";
gmp_setbit($input, 2);
echo $distance->valueToString(), "\n";

$fraction = new Rational(1, 2);
gmp_setbit($fraction->numerator, 2);
echo $fraction->toString(), "\n";
```

Observed output is `1`, `5`, and `5/2`, on separate lines. Reusing and modifying a caller-owned GMP input changes an
existing quantity. Direct access can also violate rational normalization assumptions after construction. This is a
mutability and state-consistency defect, not evidence of native heap corruption.

Copy caller-owned GMP arguments before storing them. Protect internal numeric state from mutation through returned
handles as well. Constructor copying alone does not solve the public-property path. Any change to the exposed
representation needs review under the [compatibility policy](compatibility.md).

Test input mutation after construction, mutation of exposed numeric values, and stability of quantities that share a
rational magnitude. Preserve normalization and serialized round-trip behavior while changing ownership.

## Issue 4: Integer alternatives lost by unit()

**Fourth fix:** `unit()` brands each direct numeric alternative with the existing integer or float helper. Integer
ranges and scalar constants remain attached to their branches. The result retains the source union's strictness, and
unit-name alternatives still undergo the same validity, equivalence, and ambiguity checks. Runtime `unit()` continues to
return its input unchanged; the fix is confined to the PHPStan adapter.

The inference fixture now checks integer and float branch narrowing, mixed constants, bounded integers, existing brands,
and finite unit-name alternatives. A separate PHPStan integration fixture formats whole and fractional parcel weights;
its integer branch previously produced `function.impossibleType`. Both branches execute successfully as ordinary PHP. An
integer-only branded consumer also rejects the mixed result, checking union strictness beyond its displayed type. The
runtime conformance corpus needs no changed results for this static-analysis correction.

Verification for this fix:

- Before the implementation change, the inference fixture failed because it received only `unit_float<'meter'>` where
  both numeric brands were required. The parcel-weight integration test was also run against the isolated baseline
  implementation and failed with `function.impossibleType` on its `is_int()` branch; the current implementation passes.
- `composer test -- tests/PHPStan/UnitFunctionDynamicReturnTypeExtensionTest.php tests/PHPStan/UnitConstructionRuleTest.php tests/PHPStan/InvalidUnitCallRuleTest.php tests/PHPStan/UnitTypeNodeResolverIntegrationTest.php tests/UnitFunctionTest.php`
  passed: 99 tests and 452 assertions.
- `php -d zend.assertions=1 -d assert.exception=1 -d auto_prepend_file=vendor/autoload.php tests/PHPStan/data/unit-mixed-magnitude.php`
  passed both runtime assertions for integer and fractional parcel weights.
- `composer check:full` passed: 2,343 tests, 26,371 assertions, and five expected skips, plus analysis, formatting,
  documentation, benchmark smoke, and packaged consumers.
- Reliability review: **PASS**. Independent correctness and test reviews found no additional defect. The test review
  added the consumer-boundary check above and confirmed that it fails against the baseline's float-only inference.
- `nix flake check --keep-going -L path:/tmp/yumemi-slice4-source-ip_x69fl` passed on `x86_64-linux`, using a source
  snapshot that included the new unstaged fixtures. The PHP 8.2–8.5 matrix passed 2,343 tests per version, with 29
  expected skips on PHP 8.2–8.3 and 24 on PHP 8.4–8.5. Separate extension-integration checks passed 61 tests and 4,746
  assertions on each version. The first run failed only on Markdown wrapping in this report; formatting and the full
  rerun passed.
- Other platforms, specialist mutation/probator campaigns, and the committed-revision compatibility comparison were not
  rerun.

Performance was compared with `e89eb4c` on PHP 8.2.32 using the real `analyseCall()` path and parser with warm runtime
caches. A mocked PHPStan scope supplied fixed magnitude types and `'meter'`. Each process warmed every case with 500
calls, then measured seven batches of 3,000 calls. Three processes per version ran in alternating order; the table
reports the median process medians in microseconds per call. These local adapter measurements include scope dispatch and
parsing, and are not whole-project analysis timings.

| Magnitude type   | Before (µs) | After (µs) |
| ---------------- | ----------- | ---------- |
| Integer          | 21.797      | 21.788     |
| Float            | 20.448      | 20.558     |
| Integer constant | 20.732      | 21.142     |
| Bounded integer  | 25.868      | 26.110     |
| Integer or float | 20.978      | 27.998     |
| Mixed constants  | 21.200      | 27.714     |
| Range or float   | 21.337      | 40.882     |

Single-kind inputs remain close to the baseline. Mixed inputs require additional type construction and union
combination; the old result discarded their integer alternatives and bounds. Parsing still happens once per distinct
unit spelling, and the integer/float decision happens once per magnitude alternative. No application-runtime work or new
production declarations were added.

P2.
[`UnitFunctionDynamicReturnTypeExtension::analyseCall()`](../../src/PHPStan/UnitFunctionDynamicReturnTypeExtension.php)
uses an integer brand only when the complete input type is definitely integer. An `int|float` input enters the float
branch and loses its integer alternative.

```php
<?php

use function jbboehr\Yumemi\unit;

function recordMeasurement(int|float $value): void
{
    $measurement = unit($value, 'meter');
    var_dump(is_int($measurement));
}

recordMeasurement(1);
```

PHPStan infers `unit_float<'meter'>` and reports `function.impossibleType`: the `is_int()` call will always be false.
Runtime prints `bool(true)`. Branded native values remain ordinary PHP scalars, and `unit()` returns the supplied
magnitude unchanged, so this narrowing contradicts runtime behavior.

Brand each numeric alternative independently and retain the resulting integer/float union. Preserve any known constants
and integer ranges where applicable. Add tests that compare actual runtime scalar types with PHPStan's branch analysis
for mixed numeric inputs.

## Issue 5: Native division inferred as float-only

**Fifth fix, reviewed:** Native `/` preserves the actual integer or float result when both operand magnitudes are known.
Unknown integer operands produce both numeric brands, following PHPStan's benevolent-union treatment of ordinary native
division. Explicit operand unions remain strict through subsequent binary arithmetic. The same result calculation serves
unit/unit, unit/scalar, and scalar/unit division; float operands and `fdiv()` retain their float behavior.

The fix is confined to PHPStan. Unit algebra, runtime operations, and the runtime conformance corpus remain unchanged.
The existing handling of zero divisors is preserved: inference avoids evaluating an undefined constant quotient. Unknown
integer ranges conservatively produce unbounded integer-or-float results; this slice does not add interval division or
divisibility analysis. `integerOverflowToFloat: false` does not erase fractional division results.

Review caught a regression in chained expressions: for `unit_int<'meter'>|unit_int<'second'>`, passing
`($value / 2) * 2` to a `unit_float<'meter'>` parameter lost the expected `argument.type` diagnostic. Multiplication's
numeric overflow union made the entire result benevolent, allowing the seconds alternative to be discarded. The fix
applies the existing source-union strictness helper to every binary operator when either operand is a union. Multiplying
in either operand order and squaring the divided result now retain the incompatible-unit diagnostics; the equivalent
chains with a single known unit remain accepted.

Verification for this fix:

- PHP experiments confirmed integer `4 / 2`, fractional `5 / 2`, float `4.0 / 2`, and float `PHP_INT_MIN / -1`. A
  PHPStan comparison confirmed that bare uncertain integer division already retains both numeric kinds, while the
  baseline branded path returned only `unit_float` and changed integer constant `2` into float constant `2.0`.
- Before implementation, `composer test -- tests/PHPStan/UnitOperatorTypeSpecifyingExtensionTest.php` failed seven tests
  covering numeric-kind preservation and all three operand positions. After implementation it passed: 55 tests and 337
  assertions, including constant values, signs, the integer overflow boundary, float operands, and explicit unit-union
  rejection.
- `composer test -- tests/PHPStan/UnitDivisionTypeTest.php` passed: one fixture and 20 assertions covering constants,
  integer/float branch narrowing, finite magnitude unions, mixed numeric brands, unit cancellation, and `fdiv()`.
- The new inference fixture and the public `cutWholeMeterPiece()` example were also run against the isolated committed
  implementation. Both failed because it returned a float brand for integer quotient `2`; the current implementation
  passes both checks.
- The initial independent correctness and adversarial test reviews found no actionable defect; the later user review
  exposed the chained-arithmetic regression above. The initial test review added coverage for integer-zero divisors in
  all three operand positions, known fractional results with overflow promotion disabled, and explicit right-hand unit
  unions; the final focused run includes those checks.
- The chained-expression regression test passed against isolated `48af89d` with all three expected diagnostics, failed
  against the reviewed patch with zero diagnostics, and passed after the correction. Two existing direct multiplication
  tests also failed when strengthened to require strict unions, then passed with the correction.
- The follow-up independent code review found no further defect. The adversarial test review added two divisor-union
  chains and matching single-unit controls, and strengthened the integration test to require exact diagnostic lines and
  identifiers. The reviewed patch still fails this expanded test: it reports only the two divisor-union errors and loses
  the three numerator-union errors.
- After the correction,
  `composer test -- tests/PHPStan/UnitOperatorTypeSpecifyingExtensionTest.php tests/PHPStan/UnitDivisionTypeTest.php tests/PHPStan/UnitTypeNodeResolverIntegrationTest.php tests/PHPStan/QuantityOperatorReturnTypeExtensionTest.php tests/PHPStan/UnitScalarTransformationTypeTest.php tests/PHPStan/UnitUnionTypeHelperTest.php`
  passed: 90 tests and 830 assertions. An additional PHPStan probe confirmed that unary `+` and `-` retain both
  incompatible-unit diagnostics after division. `composer analyse` passed.
- The first post-review full Composer and Nix runs caught a field access on `mixed` in the integration test's decoded
  JSON. An explicit array assertion fixed the test; its focused rerun passed with one test and 14 assertions before both
  full gates were rerun successfully.
- Final `composer check:full` passed: 2,351 tests, 26,544 assertions, and five expected skips, plus analysis,
  formatting, documentation, benchmark smoke, and packaged consumers.
- `nix flake check --keep-going -L path:/tmp/yumemi-slice5-review-final-cfugfy80` passed on `x86_64-linux`, using a
  source snapshot that included the new unstaged fixtures. The PHP 8.2–8.5 matrix passed 2,351 tests per version, with
  29 expected skips on PHP 8.2–8.3 and 24 on PHP 8.4–8.5. Separate extension-integration checks passed 61 tests and
  4,746 assertions on each version. The skips concern optional catalog/UDUNITS2 tooling and native `RoundingMode`
  availability.
- Reliability review: **PASS**, following the review correction, independent follow-up reviews, and fresh final
  verification. Other platforms, specialist mutation/probator campaigns, and the committed-revision compatibility
  comparison were not rerun.

Performance was remeasured after the review correction against `48af89d` on PHP 8.2.32 using the real `specifyType()`
path. Operand types were constructed before timing; these scalar-operand cases exclude unit parsing. Each case warmed up
with 1,000 calls, followed by seven batches of 5,000 calls. Three processes per version ran in alternating order; the
table reports median process medians in microseconds per call. These are local adapter measurements, not whole-project
analysis timings.

| Operation                          | Before (µs) | After (µs) |
| ---------------------------------- | ----------- | ---------- |
| Integer / integer scalar           | 3.476       | 3.790      |
| Float / float scalar               | 2.281       | 2.029      |
| Integral constants / scalar        | 3.769       | 3.529      |
| Fractional constants / scalar      | 3.770       | 3.522      |
| Float constant / integer scalar    | 3.570       | 3.229      |
| Explicit numeric union / scalar    | 15.506      | 21.665     |
| Mixed unit union \* integer scalar | 21.439      | 80.818     |
| Integer multiplication (control)   | 7.313       | 7.463      |
| Float multiplication (control)     | 2.277       | 2.353      |

When neither operand is a union, division returns its sole result directly, avoiding redundant union combination. Binary
operators use the shared combination helper for union operands to preserve strictness. The mixed-unit multiplication
case starts with a strict four-alternative union of integer/float meters/seconds, representing an uncertain divided
value. It now takes about 59 microseconds more per inference call; previously it returned a benevolent union that could
discard the seconds alternative. Single-kind inputs remain close to the baseline. Application runtime code is unchanged.

P2. [`UnitOperatorTypeSpecifyingExtension::specifyMulDiv()`](../../src/PHPStan/UnitOperatorTypeSpecifyingExtension.php)
forces division into float brands, including casting a calculated constant result to float. PHP can return an integer
when dividing two integers with an integral quotient.

```php
<?php

use function jbboehr\Yumemi\unit;

$distance = unit(4, 'meter') / 2;
var_dump($distance, is_int($distance));
```

Runtime prints `int(2)` and `bool(true)`. PHPStan infers `2.0&unit_float<'meter'>` and reports the integer check as
always false. Unknown integer operands likewise require allowance for both integer and float results unless divisibility
is known.

Preserve the actual PHP result kind when constant operands are available. For unknown operands, derive a conservative
numeric result type while retaining the correct unit algebra. Keep `fdiv()` separate because its float return is part of
that native function's contract.

The existing
[`UnitOperatorTypeSpecifyingExtensionTest`](../../tests/PHPStan/UnitOperatorTypeSpecifyingExtensionTest.php) includes
`testDivCombinesUnitsAndAlwaysReturnsFloat()` and `testIntDivIntSameUnitIsFloat()`. The
[PHPStan reference](../pages/reference/phpstan.md#native-operators) also states that division produces `unit_float`.
Those expectations preserve the incorrect assumption. Investigate and correct the implementation, tests, and
documentation together, using runtime PHP as the oracle for scalar result kind.

## Issue 6: Diagnostics depend on named-argument order

**Sixth fix, reviewed:** Shared inference for `Units`, `Quantity`, and `PointQuantity` now maps explicit named arguments
to their declared positions before checking units. The same standalone diagnostics apply to positional,
declaration-order named, reordered named, and mixed positional/named calls, including unused expression statements.

The new internal `MethodCallArgumentNormalizer` delegates named-argument mapping to PHPStan's existing normalizer and
method reflection. Parameter names are taken from declarations rather than copied into a separate table. Positional
calls return their original arguments without reflection or node allocation. First-class callable expressions are not
treated as invocations; calls that cannot be normalized fall back to PHPStan's ordinary argument diagnostics.

This is a PHPStan correction. Runtime methods, unit meaning, public signatures, diagnostic identifiers, and the runtime
conformance corpus remain unchanged. Full argument-unpacking analysis is not expanded by this slice.

Verification for this fix:

- Before implementation,
  `composer test -- --filter NamedQuantityCallsRetainStandaloneDiagnostics tests/PHPStan/UnitTypeNodeResolverIntegrationTest.php`
  failed with 16 diagnostics instead of 22. Six reordered calls lost their construction or conversion diagnostic. After
  implementation, every expected diagnostic appeared. The regression test also checks exact source lines and stable
  identifiers.
- The positive inference fixture checks 23 results across factory calls, quantity and point conversions, an explicit
  optional argument, a named power, an unbranded receiver, and a dynamic target. A separate integration check verifies
  that these calls produce no diagnostics.
- `composer test -- tests/PHPStan/QuantityReturnTypeExtensionTest.php tests/PHPStan/PointQuantityReturnTypeExtensionTest.php tests/PHPStan/InvalidQuantityConstructionRuleTest.php tests/PHPStan/InvalidQuantityConversionRuleTest.php tests/PHPStan/InvalidQuantityArithmeticRuleTest.php tests/PHPStan/InvalidQuantityComparisonRuleTest.php tests/PHPStan/InvalidPointQuantityMethodRuleTest.php tests/PHPStan/UnitTypeNodeResolverIntegrationTest.php tests/PHPStan/MethodCallArgumentNormalizerTest.php`
  passed after review fixes: 39 tests and 564 assertions. `composer analyse` passed.
- A separate PHPStan probe accepted first-class quantity construction and conversion callables. Missing required
  arguments and an unknown argument name produced only the expected native `argument.missing` and `argument.unknown`
  diagnostics.
- Runtime PHP experiments confirmed identical exception classes across positional, declaration-order named, and
  reordered named calls: `UnitNotFoundException` for unknown-unit construction and `IncompatibleUnitException` for
  incompatible quantity and point conversions. Valid reordered calls returned exact `1` meter and `32.00` Fahrenheit.
- Independent adversarial tests found an introduced malformed-call regression: with a seconds-branded `$value`,
  `quantity(unknown: 'meter', value: $value)` also produced `yumemi.invalidQuantityConstruction`. The upstream
  normalizer appended the unknown argument into the missing unit's position. The committed baseline produced only
  PHPStan's missing/unknown-parameter diagnostics; the new regression test initially observed nine diagnostics instead
  of eight across its fixture. The helper now checks names against the selected method declaration before reordering;
  the regression test passes with exactly the eight native diagnostics.
- Additional tests protect twelve inference results across named arithmetic, roots, point translation, unpacking, and
  first-class callables. A direct helper test checks that positional calls retain their original argument nodes and make
  no method-reflection lookup.

Review also exposed a preexisting limitation: `quantity(...[], value: unit(1, 'second'), unit: 'meter')` can infer
`Quantity<'meter'>` and omit the construction diagnostic. An executable comparison with `b9e8446` confirmed identical
behavior before and after this slice, including acceptance by a meter-quantity parameter. This is deferred with broader
argument-unpacking support; returning early only from the new helper would not fix PHPStan's prior normalization in the
dynamic-return path. The public PHPStan limitations now mention incomplete checking for unpacked arguments and calls
through first-class callables.

Performance was compared with `b9e8446` on PHP 8.2.32 using complete PHPStan analyses. Each of three valid workloads
contained 1,000 functions and 6,000 method calls: positional setup followed by quantity/point construction and decimal
conversion calls in the selected argument order. Each version had one warmup and three measured runs per workload,
alternating version order, with isolated result caches and one analysis worker. Every run reported zero diagnostics.
Median wall times were:

| Argument order          | Baseline | Fix     | Change |
| ----------------------- | -------- | ------- | ------ |
| Positional              | 5.111 s  | 5.104 s | −0.1%  |
| Declaration-order named | 5.310 s  | 5.405 s | +1.8%  |
| Reordered named         | 5.015 s  | 5.412 s | +7.9%  |

Positional performance was effectively unchanged. Named calls add declaration lookup, name validation, and argument
mapping; reordered calls also gain unit validation that the baseline skipped. This is PHPStan analysis cost, with no
application-runtime change. These are indicative local measurements, not performance thresholds: individual samples
showed scheduling noise. The baseline loader was verified against the diagnostic regression fixture, producing the
original 16 diagnostics while the fixed version produced 22.

Final verification and reliability verdict:

- **PASS_WITH_RESIDUAL_RISK.** The independent Breaker review and Test Attacker pass were reconciled with executable
  baseline comparisons. The introduced unknown-name diagnostic regression was fixed and its reproduction passed; the
  unpacking limitation above predates this change.
- `composer check:full` passed: 2,357 tests, 26,628 assertions, and five expected skips, plus static analysis,
  formatting, documentation build/link checks, benchmark smoke tests, and archive consumer checks.
- `nix flake check --keep-going -L path:/tmp/yumemi-slice6-final-c5bl3b96` passed on `x86_64-linux`, using a complete
  source snapshot that included the new unstaged helper and fixtures. The PHP 8.2–8.5 matrix passed 2,357 tests per
  version, with 29 expected skips on PHP 8.2–8.3 and 24 on PHP 8.4–8.5. Separate extension-integration checks passed 61
  tests and 4,746 assertions per PHP version. Consumer, documentation, generated-artifact, and other normal checks
  passed.
- Both new source declarations have one unique logion; preexisting logia were unchanged. No public headings changed.
- Other platforms, specialist mutation/probator and branch-coverage campaigns, and the separate committed-revision
  compatibility gate were not run. This slice changes PHPStan behavior only; its argument-unpacking and callable
  limitations remain as described above.

P2. The diagnostic rules call inference routines with the original method-call argument order. Those routines index
`getArgs()` by position instead of resolving parameter names. Relevant paths include
[`UnitsQuantityReturnTypeExtension::inferQuantityType()`](../../src/PHPStan/UnitsQuantityReturnTypeExtension.php),
[`QuantityMethodReturnTypeExtension::convert()`](../../src/PHPStan/QuantityMethodReturnTypeExtension.php), and
[`AbstractInvalidQuantityMethodRule::processNode()`](../../src/PHPStan/AbstractInvalidQuantityMethodRule.php).

Analyze this example without calling the function:

```php
<?php

use jbboehr\Yumemi\Units;

function inspectConversions(Units $units): void
{
    $units->quantity(1, 'meter')->decimalValueIn('second', 2, \RoundingMode::HalfEven);
    $units->quantity(1, 'meter')->decimalValueIn(scale: 2, mode: \RoundingMode::HalfEven, unit: 'second');

    $units->quantity(1, 'unknown_unit');
    $units->quantity(unit: 'unknown_unit', value: 1);
}
```

The positional calls produce `yumemi.invalidQuantityConversion` and `yumemi.invalidQuantityConstruction`. Their
reordered named equivalents produce neither diagnostic in this example. Both spellings describe the same runtime
operations. An inferred error type may still affect downstream uses, but an unused call can escape the intended rule.

Normalize method arguments before shared inference and rule evaluation, or use explicit parameter-name lookup. Existing
native-helper argument handling in [`NativeUnitArgumentResolver`](../../src/PHPStan/NativeUnitArgumentResolver.php)
provides a local precedent. Keep any shared helper limited to argument mapping.

Test equivalent positional, named, and reordered named calls, including unused expression statements. Compare stable
diagnostic identifiers and inferred results across those spellings.

## Issue 7: Type resolution ignores namespace identity

Status: committed in `00e7335` after review and verification.

Object type resolution now uses PHPStan's `NameScope` and matches the fully qualified Yumemi classes. Ordinary imports,
renamed imports, namespace imports, fully qualified names, and references inside Yumemi's own namespace retain their
unit brands. Unrelated generic classes named `Quantity`, `PointQuantity`, or a qualified scalar pseudo-type remain under
PHPStan's ordinary resolution. Unqualified `unit_int`, `unit_float`, and `unit_numeric_string` remain extension-owned.

Optional `@yumemi-*` promotion now carries namespace and class-import context into unit validation and fallback
matching. Each parser traversal owns its import scope. Early validation visits PHPDoc unit nodes without reflecting user
classes, leaving surrounding generic-type checks to PHPStan after promotion. This avoids re-entering the parser while a
referenced class's source is still being parsed. Runtime behavior and the conformance corpus are unchanged.

Experimental verification:

- Before implementation, five focused regression tests failed for the expected namespace-resolution differences. Plain
  PHPStan accepted the unrelated-class fixture with zero diagnostics.
- After the resolver change, the same five tests passed with 48 assertions. A broader run exposed loss of an existing
  `@yumemi-return Quantity<'newton'>` brand because promotion previously resolved types without imports.
- A new optional-tag fixture exposed recursive class reflection during parser-level validation. The default parallel CLI
  misleadingly reported no errors, while debug mode failed. The optional-tag CLI test helper now runs in debug mode and
  verifies the process exit code. After limiting early validation to unit nodes, the focused suite passed with 49 tests
  and 443 assertions, including foreign generic containers that contain unit types.
- A separate parser lifecycle test passed with nine assertions. Removing the per-traversal promoter clone made it fail
  because an import from the first file incorrectly branded a name in the second file. The mutation was reverted.

The initial independent correctness review found no actionable defect. The separate test review added
[`yumemi-tag-namespace-boundaries.php`](../../tests/PHPStan/data/yumemi-tag-namespace-boundaries.php), covering mixed
group imports, namespace aliases, fully qualified point types, foreign quantity rejection, nested invalid units, and
PHPStan's surrounding-generic diagnostics. That test passed with 11 assertions. The test also verifies that ordinary
PHPStan diagnostics remain visible after early validation is limited to Yumemi's own unit types.

A subsequent review found that configured PHPStan aliases in optional stub annotations had lost their unit constraints.
For `MeterValue: "unit_float<'meter'>"`, `@yumemi-param MeterValue $distance` was not promoted because validation
skipped identifier nodes. A new CLI regression test accepted a seconds-branded argument in the working tree, while the
same fixture against `0374ee7` produced `argument.type`. Both direct and chained aliases failed before the review fix
and passed afterward.

Validation now inspects the syntax of configured aliases in their global scope. Definitions are parsed once, alias
cycles are detected during traversal, and class reflection remains deferred to PHPStan. The promoted annotation keeps
its original alias spelling. Existing fallback-matching rules are unchanged. Additional tests cover nested aliases,
aliases containing foreign generic classes, fully qualified quantity aliases, and array/object shape keys whose names
happen to match aliases. A field name alone must not count as a unit-bearing type.

A second independent correctness review raised a redundant-union case: a configured alias containing
`int|unit_int<'meter'>` passes syntactic validation even though PHPStan simplifies its effective type to `int`.
Experiments confirmed that the equivalent inline annotation previously emitted `yumemi.docTagType` and now does not;
neither revision rejects its plain-integer or seconds-branded callers. Stub experiments with both inline and aliased
forms produced zero diagnostics on both revisions. An alias in an ordinary source annotation caused parser re-entry and
a process crash on `0374ee7`, so that case did not provide the proposed baseline diagnostic. No additional fix was
applied: the explicit `int` alternative already allows those callers, and reproducing PHPStan's type simplification
during parser validation would expand this change substantially. The diagnostic difference remains a known limitation of
syntactic validation.

The separate test review demonstrated no additional production defect. It added three tests covering repeated aliases
inside stub array shapes, invalid unit leaves after valid ones, and preservation of structural fallback matching. All
three passed with 35 assertions. The repeated nested-stub test also passed against `0374ee7` and failed against the
immediate pre-fix snapshot, confirming that the fix restores existing constraints. The broader baseline comparison
crashed while parsing the source-level invalid-alias and fallback cases; the current implementation handled both.

Performance comparison before the configured-alias review fix, against `0374ee7`, PHP 8.2.32 and PHPStan 2.2.5 on this
host: 200 cases per workload, one warmup and three measured runs per revision, alternating execution order, with a fresh
result cache for each run. All 32 analyses completed with zero diagnostics. Values are median complete CLI times,
including startup.

| Workload                         | Base    | Working tree | Change |
| -------------------------------- | ------- | ------------ | ------ |
| Native PHPDoc brands             | 0.865 s | 0.844 s      | -2.4%  |
| Imported quantity PHPDoc         | 0.863 s | 0.880 s      | +2.0%  |
| Quantity method inference        | 2.201 s | 2.197 s      | -0.2%  |
| Optional native-unit annotations | 1.187 s | 1.158 s      | -2.4%  |

These small differences do not establish a material speedup or slowdown. The scalar-name lookup remains direct, and
runtime quantity operations are unchanged. This comparison does not measure warm result-cache reuse, renamed-import
workloads that failed on the base, or large projects with many imported user classes.

The configured-alias fix was measured separately against `0374ee7` using 200 stub-declared functions and valid
meter-branded calls. Each workload used one warmup and three measured runs per revision, alternating execution order,
with isolated result caches and PHPStan debug mode. All 24 analyses completed with zero diagnostics.

| Stub workload                   | Base    | Review fix | Change |
| ------------------------------- | ------- | ---------- | ------ |
| Direct configured alias         | 1.043 s | 1.055 s    | +1.1%  |
| Chained configured alias        | 1.034 s | 1.045 s    | +1.1%  |
| Direct unit, 100 unused aliases | 1.067 s | 1.062 s    | -0.5%  |

These medians do not show a material regression. They include complete CLI startup and do not establish performance for
large alias graphs or warm result-cache reuse.

Final repository verification:

- `COMPOSER_PROCESS_TIMEOUT=0 composer test --` with the PHPStan test files `UnitTypeNodeResolverTest.php`,
  `UnitTypeNodeResolverIntegrationTest.php`, `YumemiReturnTagExtensionTest.php`, `YumemiTagPromotionRuleTest.php`, and
  `ShouldNotHappenBoundaryTest.php` passed: 57 tests and 537 assertions.
- `COMPOSER_PROCESS_TIMEOUT=0 composer check:full` passed: 2,371 tests, 26,830 assertions, five expected skips, plus
  Composer validation, formatting, PHPStan analysis, documentation examples, mdBook build and links, benchmark smoke,
  and packaged-consumer checks.
- `nix flake check --keep-going -L path:/tmp/yumemi-slice7-source-sci_cfdo` passed on `x86_64-linux`, using a complete
  tracked-and-untracked source snapshot. Each PHP 8.2–8.5 suite ran 2,371 tests. PHP 8.2/8.3 had 29 expected skips and
  PHP 8.4/8.5 had 24. All remaining flake checks passed. Only this report's verification notes changed afterward.
- Fifteen new in-scope declarations have unique, independently generated logions. Their references and text were
  checked, and preexisting declaration logions were preserved.

Reliability verdict: `PASS_WITH_RESIDUAL_RISK`. The redundant-union diagnostic difference above remains accepted.
Circular or very deep alias graphs, callable/conditional/offset-access aliases, and renamed quantity class imports
inside stub files were not separately exercised. Other host architectures, mutation testing beyond the focused
clone-removal check, Xdebug branch coverage, the parser probator, and the committed-revision compatibility gate were not
run. The review report is outside the public documentation example corpus, as noted above.

Original finding (before this change):

P2. [`UnitTypeNodeResolverExtension::resolve()`](../../src/PHPStan/UnitTypeNodeResolverExtension.php) recognizes object
types by their short name and ignores the supplied `NameScope`. It can capture an unrelated generic class even when the
annotation uses that class's fully qualified name.

Analyze this example:

```php
<?php

namespace Inventory;

/** @template T */
class Quantity
{
}

/** @param \Inventory\Quantity<string> $quantity */
function storeInventoryQuantity(Quantity $quantity): void
{
}
```

Ordinary PHPStan accepts it. With Yumemi's extension enabled, the parameter produces `parameter.unresolvableType` and
`missingType.generics`. The resolver has treated the unrelated class as a Yumemi unit type and attempted to interpret
its generic argument as a unit literal.

The reverse problem affects renamed imports. Analyze this separate example:

```php
<?php

use jbboehr\Yumemi\Quantity as Measured;

/** @param Measured<'meter'> $quantity */
function storeMeasuredDistance(Measured $quantity): void
{
}
```

PHPStan reports `generics.notGeneric` because the alias does not enter Yumemi's object-type resolution path.

Resolve object names through `NameScope` and match the fully qualified Yumemi classes. Keep the intentionally named
scalar pseudo-types separate from class-name resolution. Test unrelated same-name classes, fully qualified names,
ordinary imports, and renamed imports. Loading Yumemi should leave another library's generic classes intact.

## Issue 8: Ordering on negative scales

Status: implemented and reviewed.

P2. [`Quantity::compareTo()`](../../src/Quantity.php) and [`PointQuantity::compareTo()`](../../src/PointQuantity.php)
previously converted the right operand into the left operand's scale and compared stored coordinates. When the left
scale has a negative factor, numerical coordinate order is the reverse of order in the positive canonical scale.

```php
<?php

use jbboehr\Yumemi\Registry\UnitRegistryBuilder;
use jbboehr\Yumemi\Units;

$units = Units::default();
$left = $units->quantity(1, '-1 * meter');
$right = $units->quantity(2, 'meter');
echo $left->compareTo($right), '/', $right->compareTo($left), "\n";

$reversed = new Units(UnitRegistryBuilder::default()->define('reverse_kelvin = -1 kelvin')->build());
$point = $reversed->point(1, 'reverse_kelvin');
$reference = $reversed->point(2, 'kelvin');
echo $point->compareTo($reference), '/', $reference->compareTo($point), "\n";
```

Before the fix, both lines printed `1/1`. Each comparison claimed that its left operand was greater, although the values
in canonical units are `-1` and `2`. This violates antisymmetry and can invalidate sorting and range checks. The named
ordering predicates delegate to these methods and inherited the defect.

The fix compares both operands through their exact maps to a positive canonical scale. The example now prints `-1/1` on
both lines. The same defect affected the bundled `degree_west` unit, whose definition has a negative factor relative to
`degree_east`.

Accepted zero-scale quantities map to canonical zero. Points follow their exact affine map, including its offset.
Comparison no longer tries to invert a zero scale, and `compareTo() === 0` agrees with `equals()` for comparable
operands. Incompatible dimensions and registry contexts still throw from ordering, while equality still returns `false`.

The shared internal `Units::compareValues()` method uses the existing resolved conversion maps. Equality delegates to
the same comparison after its compatibility check. This avoids rebuilding canonical expressions or adding another cache.

Experimental verification:

- Before the production change, 19 new ordering cases produced nine reversed-order failures and seven division-by-zero
  errors. The remaining three controls passed. The cases cover all named predicates, exact fractions, dimensionless
  units, negative affine definitions and aliases, and zero scales.
- Two additional ordering-property tests and eight portable comparison fixtures were checked against the committed value
  classes at `3955976`: seven assertions failed on reversed ordering, two zero-scale cases threw, and the positive
  affine control passed. The property tests check all pairs and increasing triples of six differently represented
  values, plus comparison after conversion.
- Independent correctness review found no introduced defect. A separate exact-arithmetic model passed 3,025 quantity
  pairs, 5,929 point pairs, 2,913 conversion-invariance checks, and three incompatibility/exception-metadata checks. The
  same model failed on the committed implementation's antisymmetry defect.
- The independent test pass added a regression test for two affine points separated by `1e-30` degrees Celsius above
  freezing. Exact ordering preserves this difference even though conversion to native floats loses it.
- A local PHP 8.2 benchmark compared the committed and changed value classes in separate processes. Each case warmed up
  for 100 calls, then measured 10,000 calls. The figures below are medians of three runs per revision, with revision
  order alternated after a whole-process warmup.

| Operation                            | Before (µs/call) | After (µs/call) |
| ------------------------------------ | ---------------: | --------------: |
| Quantity comparison, same unit       |            6.525 |           5.755 |
| Quantity comparison, meter/foot      |            8.333 |           7.215 |
| Quantity comparison, compound units  |           10.118 |           8.789 |
| Point comparison, same unit          |            4.746 |           3.974 |
| Point comparison, Celsius/Fahrenheit |            6.087 |           4.967 |
| Quantity equality, same unit         |           22.042 |           8.534 |
| Quantity equality, meter/foot        |           25.066 |          11.345 |
| Quantity equality, compound units    |           75.169 |          14.741 |
| Point equality, same unit            |           23.244 |           5.406 |
| Point equality, Celsius/Fahrenheit   |           37.849 |           6.608 |

An independent benchmark using seven runs of 200,000 calls also measured lower comparison times: `5.56` versus `6.13` µs
for same-unit quantities, `7.11` versus `7.87` µs for mixed-unit quantities, and `4.80` versus `5.90` µs for affine
points (changed versus committed implementation).

These are local warm-cache measurements, not cross-platform performance guarantees. Cold resolution and peak memory were
not measured by this comparison.

Final verification passed after both independent reviews:

- `composer test -- tests/QuantityTest.php tests/PointQuantityTest.php tests/RuntimeInvariantTest.php tests/Conformance`:
  256 tests and 1,265 assertions, including the affine precision test.
- `composer check:full`: 2,402 tests, 27,184 assertions, and five expected skips. PHPStan, formatting, documentation
  examples, the book build and generated links, benchmark smoke tests, and consumer archive checks passed.
- The `nix flake check --keep-going -L` gate passed on x86_64-linux using a complete source snapshot that included the
  new comparison fixture. Each PHP 8.2–8.5 suite ran 2,402 tests. PHP 8.2/8.3 had 29 expected skips, and PHP 8.4/8.5
  had 24. All four native-extension checks passed with 61 tests and 4,746 assertions each. Other architectures were not
  run.
- The independent exact-arithmetic model was rerun against the final production code and passed the same pair,
  conversion, and exception checks above.
- The subsequent review reported no actionable regressions after focused tests, an independent 2,125-pair comparison
  check, and `composer check:full` (2,402 tests and five skips). That review did not rerun the Nix matrix.

Reliability verdict: PASS. No additional production defects were found, and the affine precision test was retained as
test hardening. The local benchmark does not assess sustained sorting workloads, cold resolution, or memory usage.

## Issue 9: Deserialization context crosses Fibers

Status: implemented and reviewed.

P2. [`DeserializationContext::run()`](../../src/Internal/DeserializationContext.php) temporarily changes one static
variable in the original implementation. Its save/restore sequence supports synchronous nesting, but overlapping Fibers
could observe each other's context. PHP deserialization can invoke an application's `__unserialize()` callback, which
may suspend.

This example constructs trusted payloads locally and runs in a separate process:

```php
<?php

use jbboehr\Yumemi\Registry\UnitRegistry;
use jbboehr\Yumemi\Units;

class PauseOnRestore
{
    public function __serialize(): array
    {
        return [];
    }

    public function __unserialize(array $data): void
    {
        Fiber::suspend();
    }
}

$first = new Units(UnitRegistry::bundled());
$second = new Units(UnitRegistry::bundled());
$firstPayload = serialize([new PauseOnRestore(), $first->quantity(1, 'meter')]);
$secondPayload = serialize([new PauseOnRestore(), $second->quantity(2, 'meter')]);

$firstFiber = new Fiber(fn () => $first->deserialize($firstPayload));
$secondFiber = new Fiber(fn () => $second->deserialize($secondPayload));
$firstFiber->start();
$secondFiber->start();
$firstFiber->resume();

$restored = $firstFiber->getReturn()[1];
var_dump($restored->units() === $first, $restored->units() === $second);

try {
    $secondFiber->resume();
} catch (Throwable $exception) {
    echo $exception->getMessage(), "\n";
}
```

Observed output before the fix:

```text
bool(false)
bool(true)
A custom-context Quantity must be restored with Units::deserialize().
```

The first quantity was restored into the second context. Equivalent registry definitions allowed its semantic validation
to succeed, but the object belonged to the wrong `Units` instance. The second restoration then failed because the first
call restored the shared slot to its earlier value. Both calls used the documented custom-context restoration API.

The fix gives each Fiber its own temporary context, with a separate main-execution slot. A weak map records Fiber
identities without keeping abandoned Fibers alive. Nested scopes restore their preceding context in `finally`, and
outermost scopes remove their map entry on exit. The main execution path does not allocate a map. Newly started Fibers
do not inherit the context of the Fiber or main execution that started them.

The example now prints `bool(true)` and `bool(false)`, and both restorations finish. Raw custom-value restoration in the
main execution context also rejects a payload while another Fiber has a compatible context suspended. Default quantities
and points still restore into `Units::default()`. The bootstrap policy for `Units::setDefault()` is unchanged.

Experimental verification:

- Before the fix, the ten new tests produced seven expected failures: incorrect context identity for both completion
  orders, inherited context in child Fibers, interference after an exception, incorrectly bound native values, and raw
  restoration borrowing a suspended Fiber's context. Three existing-behavior controls passed.
- After the fix and independent test hardening,
  `composer test -- tests/Internal/DeserializationContextTest.php tests/FiberDeserializationTest.php tests/SerializationTest.php tests/UnitsTest.php tests/Compatibility`
  passed 170 tests and 1,091 assertions. Tests cover quantities, points, mixed default/custom graphs, nested failure,
  and collection of contexts after completed or abandoned Fibers. PHPStan also passed.
- Native payload versions and semantic validation are unchanged. The PHP-specific serialization and tagged-release
  compatibility tests cover this scheduling change. The language-neutral conformance corpus has no affected case.

Local PHP 8.2 performance measurements compared `041eaca` with the changed scope manager in separate processes. Each
payload case used 100 warmup calls and 10,000 measured restores. Scope-only cases measured 200,000 calls. The table
shows medians of five runs per implementation, alternating revision order after one whole-process warmup.

| Operation                             | Before (µs/call) | After (µs/call) |
| ------------------------------------- | ---------------: | --------------: |
| Main execution, scalar restoration    |            0.355 |           0.384 |
| Fiber, scalar restoration             |            0.354 |           0.479 |
| Main execution, quantity restoration  |          124.589 |         124.561 |
| Fiber, quantity restoration           |          125.975 |         126.077 |
| Main execution, point restoration     |           52.455 |          51.014 |
| Fiber, point restoration              |           52.148 |          51.253 |
| Main execution, scope entry/read/exit |            0.142 |           0.186 |
| Fiber, scope entry/read/exit          |            0.142 |           0.243 |

The small scalar case exposes the added bookkeeping: about 0.03 µs per main-execution restore and 0.13 µs per Fiber
restore. Representative quantity and point restoration stayed within 3% of baseline. These local warm-cache timings do
not establish a cross-platform performance guarantee or measure peak native memory.

Independent reliability review found no actionable production defects. Two additional tests protect an active sibling
and main scope when another Fiber is abandoned, and verify context collection and exception identity after cancellation
with `Fiber::throw()`. A scoped mutation run against `DeserializationContext` generated ten variants: nine were caught
by tests and one timed out. The timeout is not evidence that the tests detected its semantic defect.

Final verification after independent review:

- `composer check:full` passed with 2,414 tests, 27,250 assertions, and five expected skips. PHPStan, formatting,
  documentation examples, the book build and generated links, benchmark smoke tests, and consumer archive checks passed.
- The `nix flake check --keep-going -L` gate passed on x86_64-linux using a complete source snapshot that included both
  new test files. Each PHP 8.2–8.5 suite ran 2,414 tests. PHP 8.2/8.3 had 29 expected skips, and PHP 8.4/8.5 had 24. All
  four native-extension checks passed with 61 tests and 4,746 assertions each. Other architectures were not run.
- The report's example was executed again and produced the corrected output above. Existing logions were preserved, and
  the new declaration's reference was verified as unique. Documentation formatting and `git diff --check` passed.
- The subsequent review found no actionable defects after the same focused and full Composer checks and the x86_64-linux
  Nix gate, including both new test files. Additional checks confirmed nested-scope cleanup after Fiber completion and
  abandonment.

Reliability verdict: PASS. No additional production defects were found. The two cancellation and abandonment tests were
retained. The mutation timeout remains unclassified; no long randomized scheduling campaign or peak-memory profile was
run.

## Issue 10: Unbounded semantic caches

P2. [`UnitConversionResolver::resolve()`](../../src/Analyzer/UnitConversionResolver.php) stores each successful input
string in an array with no eviction. [`Units::parseQuantity()`](../../src/Units.php) passes the complete measurement
string through this resolver, so changing magnitudes create distinct retained entries. Releasing returned quantities
does not release those entries while the `Units` context remains alive.

The following small inspection demonstrates retention without a resource-stress workload. Reflection is used only to
observe an internal cache during diagnosis.

```php
<?php

use jbboehr\Yumemi\Registry\UnitRegistry;
use jbboehr\Yumemi\Units;

$units = new Units(UnitRegistry::bundled());
$inputs = ['2 meter', '3 meter', '4 meter'];
foreach ($inputs as $input) {
    $quantity = $units->parseQuantity($input);
    unset($quantity);
}

$resolver = (new ReflectionProperty($units, 'unitConversionResolver'))->getValue($units);
$cache = (new ReflectionProperty($resolver, 'stringCache'))->getValue($resolver);
echo json_encode(array_values(array_intersect($inputs, array_keys($cache)))), "\n";
```

Observed output is `["2 meter","3 meter","4 meter"]`. Source inspection establishes that these entries have no eviction
path. [`UnitNameResolver`](../../src/Analyzer/UnitNameResolver.php) and
[`UnitResolver`](../../src/Analyzer/UnitResolver.php) also retain arbitrary lookup misses. Bounded AST and
parsed-expression caches therefore do not bound the complete context's retained input state.

An additional bounded lifecycle experiment began with a fresh context and measured its string-cache entry count after
three batches: `['2 meter', '3 meter', '4 meter']`, the same batch again, and `['5 meter', '6 meter', '7 meter']`. Each
returned quantity was unset, and `gc_collect_cycles()` ran after each batch. Entry counts were `0 → 3 → 3 → 6`: repeated
inputs reused entries, while distinct inputs remained retained. Parsing `missing_length` and `missing_duration`,
catching their exceptions, and inspecting both name-lookup caches found those keys retained with `null` values. After
releasing the context and inspection references, a `WeakReference` to the context returned `null`. The demonstrated
retention therefore lasts while the context is reachable; it does not prevent the whole context from being collected.

Long-lived workers processing varied measurement strings can accumulate memory for the lifetime of the context. This
review measured retained entry counts, not a byte-growth rate or an exhaustion threshold. It did not identify a native
memory-corruption defect.

Bound caches keyed by arbitrary expressions and names, including negative lookups. Reuse
[`BoundedLruCache`](../../src/Internal/BoundedLruCache.php) where its contract fits, and distinguish finite catalog
indexes from caches populated by open-ended inputs. Assess entry weights against retained values as well as input text.
Add a bounded workload test that verifies eviction through the public parsing/conversion path and confirms that results
remain stable after eviction.

## Test and maintenance improvements

The shared runtime semantic core, versioned conformance cases, public example verification, consumer installs, and
supported-PHP matrix provide useful foundations. The findings identify gaps in what the tests assert and which
combinations they exercise.

- Compare inferred scalar types against runtime PHP for supported native operations. Issue 5 shows how implementation,
  tests, and prose can agree on an incorrect result kind.
- Exercise complete union alternatives and parameter-name mapping at the integration boundary. Include unused calls when
  testing diagnostics, so downstream error propagation cannot conceal a missing rule diagnostic.
- Extend behavioral properties to ordering, formatter round trips, numeric ownership, and interleaved restoration.
  Preserve public observations in tests instead of relying only on internal type descriptions or cache structure.
- Consolidate small argument-mapping and union-handling policies where they have identical contracts. The existing
  [PHPStan repetition audit](phpstan-repetition-audit.md) explains why operation-specific semantics and diagnostic
  ownership should remain explicit.

Each fix should begin with a failing regression for the documented behavior. Follow the
[semantic invariants](invariants.md), [compatibility policy](compatibility.md), and
[conformance corpus](../../tests/Conformance/README.md) when deciding expected results. User-visible fixes need a
changelog entry. Mutability changes and corrections to explicitly documented inference or formatting behavior need
particular compatibility review.

## Verification and limits

### Per-finding experiments

The findings were experimentally rechecked on 2026-09-04 against the same commit and installed dependencies, using PHP
8.2.32 and PHPStan 2.2.5 on 64-bit Linux without `ext-yumemi` loaded. The table distinguishes observed behavior from
conclusions based on source inspection. The commands under [Reproducing the examples](#reproducing-the-examples) apply
to the examples above; analysis used level `max`, the project's `extension.neon`, and a separate temporary PHPStan
cache. The unrelated-class control omitted `extension.neon`.

| Issue | Verification                                 | Result and control                                                                                                                                                                                                                                                |
| ----- | -------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1     | Runtime and PHPStan                          | Both mixed-union `mul()` and `div()` examples produced the wrong declared dimension with zero diagnostics. Removing `\|int` from the parameter and its PHPDoc produced `return.type`; passing scalar `2` to the original example produced `2 * meter`.            |
| 2     | Runtime formatting and reparsing             | Both bundled collisions changed dimensions. A custom `km = second` overlay also changed formatted `kilometer` from length to time. `UnitNameStyle::Preserve` kept the original dimensions for both bundled examples.                                              |
| 3     | Runtime mutation                             | The displayed quantity changed from `1` to `5` through the retained input; direct numerator mutation produced `5/2`. As a control, mutating the input to `new Rational($input, 2)` left that normalized fraction at `1/2`.                                        |
| 4     | Runtime and PHPStan                          | The integer invocation returned `bool(true)` despite `function.impossibleType`. A separate `unit(1.0, 'meter')` invocation retained a float, confirming that runtime preserves both input kinds.                                                                  |
| 5     | Runtime and PHPStan                          | Integral division returned `int(2)` despite float-only inference and `function.impossibleType`. Controls with `unit(3, 'meter') / 2` and `fdiv(4, 2)` returned floats.                                                                                            |
| 6     | PHPStan                                      | Exactly two diagnostics appeared, on the positional calls: `yumemi.invalidQuantityConversion` and `yumemi.invalidQuantityConstruction`. The corresponding reordered named calls produced none. The function was not invoked at runtime.                           |
| 7     | PHPStan, extension enabled and disabled      | The unrelated class produced `missingType.generics` and `parameter.unresolvableType` only with Yumemi enabled; the disabled control passed. The renamed Yumemi import produced `generics.notGeneric`.                                                             |
| 8     | Runtime comparison                           | Both negative-scale examples returned `1/1`. Converting the left quantity to `meter` and the left point to `kelvin` before comparison changed both pairs to `-1/1`, establishing dependence on the representation of the same values.                             |
| 9     | Runtime Fiber scheduling                     | The interleaved example restored the first quantity into the second context, then failed the second restoration. Starting and completing each Fiber sequentially restored both quantities into their own contexts. Payloads were locally constructed and trusted. |
| 10    | Bounded runtime inspection and source review | Entry counts were `0 → 3 → 3 → 6`; two failed name lookups remained cached as `null`. Releasing the context allowed collection. The absence of an eviction path comes from source inspection; no resource-exhaustion workload was run.                            |

All eleven PHP fences were extracted into separate files. Eight execute the runtime cases; three contain analysis-only
declarations and were loaded without calling them. Every file loaded successfully, runtime output matched the report,
and the six PHPStan examples produced the expected identifiers. The additional controls above ran separately. These are
snapshot experiments, not committed regression tests or evidence that the suggested fixes work. Wider union shapes,
other scheduling orders, and other supported PHP versions were not exhaustively checked by these reproductions.

### Repository gates

The original review ran these repository gates:

| Command                                                                                                | Result                                                                                                                                                                                              |
| ------------------------------------------------------------------------------------------------------ | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `composer check:full`                                                                                  | Passed. Includes Composer validation, consumer lock checks, whitespace, PHP formatting, PHPStan, PHPUnit, documentation build/link validation, benchmark smoke checks, and packaged consumer tests. |
| `nix flake check --keep-going -L`                                                                      | Passed on `x86_64-linux`, including the PHP 8.2 through 8.5 matrix and optional extension integration checks.                                                                                       |
| `composer test -- tests/PHPStan/UnitPreservingFunctionTypeResolverExtensionTest.php --display-skipped` | Passed with five expected skips on PHP 8.2. All five require native PHP 8.4 rounding modes.                                                                                                         |
| `git diff --check`                                                                                     | Passed.                                                                                                                                                                                             |

The full local suite reported 2,278 tests, 26,111 assertions, and the five rounding-mode skips. The focused rounding
suite reported 12 tests and 28 assertions. PHP and PHPStan experiments supplied the observed results in the findings.
The formatter and cache examples also distinguish inspected implementation behavior from unmeasured operational impact.

During the documentation-only experimental verification update, `composer check:full` passed again with the same test
and assertion counts. The report's 38 relative links and their Markdown anchors were checked separately. The file was
formatted with `treefmt --no-cache docs/development/project-review-2026-09-04.md` and checked with the same command plus
`--fail-on-change`. Because the new report is untracked, its whitespace was checked separately from `git diff --check`.
Its examples remain outside the continuous Akashi corpus. The Nix gate was not rerun for this documentation-only update.

Mutation testing, branch coverage, the probator, and native sanitizers were not run. Nix omitted incompatible host
systems, so this review did not verify its Darwin or `aarch64-linux` checks. Optional native-extension integration tests
passing does not establish native memory safety. The review's memory findings concern retention and mutability. This
report is a snapshot of identified defects and test gaps, not proof that other defects are absent.
