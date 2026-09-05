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

P2. [`Quantity::compareTo()`](../../src/Quantity.php) and [`PointQuantity::compareTo()`](../../src/PointQuantity.php)
convert the right operand into the left operand's scale and compare stored coordinates. When the left scale has a
negative factor, numerical coordinate order is the reverse of order in the positive canonical scale.

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

Both lines print `1/1`. Each comparison claims that its left operand is greater, although the values in canonical units
are `-1` and `2`. This violates antisymmetry and can invalidate sorting and range checks. The named ordering predicates
delegate to these methods and inherit the defect.

Compare through a consistently oriented canonical basis or account for scale orientation explicitly. Review zero-scale
behavior separately: equality already uses a nonzero canonical basis, while ordering needs a coherent documented policy.
Do not silently reject previously accepted scales without compatibility review.

Add properties for comparison antisymmetry, transitivity, and invariance under compatible unit conversion. Include
positive and negative scales for quantities and named coordinate units.

## Issue 9: Deserialization context crosses Fibers

P2. [`DeserializationContext::run()`](../../src/Internal/DeserializationContext.php) temporarily changes one static
variable. Its save/restore sequence supports synchronous nesting, but overlapping Fibers can observe each other's
context. PHP deserialization can invoke an application's `__unserialize()` callback, which may suspend.

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

Observed output:

```text
bool(false)
bool(true)
A custom-context Quantity must be restored with Units::deserialize().
```

The first quantity is restored into the second context. Equivalent registry definitions allow its semantic validation to
succeed, but the object belongs to the wrong `Units` instance. The second restoration then fails because the first call
restored the shared slot to its earlier value. Both calls used the documented custom-context restoration API.

Keep dynamically scoped deserialization state local to the current Fiber, with a separate main-execution context, and
preserve synchronous nested restoration. Test interleaved success, failure, and nested calls, including context cleanup.
The bootstrap-only policy for `Units::setDefault()` does not isolate this separate deserialization state.

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
