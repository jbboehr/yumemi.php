# 0.2 Compatibility Review

The 0.2 line is appropriate for the changes since `v0.1.1`. Equality, static comparison checks, Fiber bootstrap rules,
and numeric inference require migration guidance. Direct `Rational` component access also changes source syntax, even
though those storage properties were provisional. The [upgrade guide](../pages/getting-started.md#upgrade-from-01) now
covers these changes without promising blanket source compatibility.

This review compares `v0.1.1` (`5eea5b6eb80a616cc8c0fa74c0a05eda0feb5222`) with
`398e24ea279991b6e2a3e728388664412f2e6812` on `develop`. It completes the compatibility and migration slice of
[0.2 preparation](planning.md#next-release-020). Release metadata, final release verification, and publication remain
separate work under the [runbook](release-and-succession.md).

## Classifications and Caller Actions

The [compatibility policy](compatibility.md#classifying-changes) permits deliberate, documented breaks between `0.x`
minor lines. A corrected result can still require callers to change code or expectations.

| Surface                                            | Classification                                              | Caller action and evidence                                                                                                                                                                                                                                                                                                                                                                                               |
| -------------------------------------------------- | ----------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `Rational` components                              | Source change to provisional storage, plus an ownership fix | Replace public property reads with `numerator()` and `denominator()`. Both return normalized, detached GMP values. Constructor names and accepted types are unchanged. [Ownership tests](../../tests/Number/RationalOwnershipTest.php) cover inputs, outputs, shared magnitudes, and native restoration.                                                                                                                 |
| `Quantity::equals()` and `PointQuantity::equals()` | Deliberate behavior change                                  | Incompatible dimensions or contexts return `false`. Use `compareTo() === 0` when incompatibility must throw. [Quantity tests](../../tests/QuantityTest.php) and [point tests](../../tests/PointQuantityTest.php) cover both policies.                                                                                                                                                                                    |
| Native quantity equality and ordering in PHPStan   | Deliberate new diagnostic on previously accepted code       | Use value-comparison methods, or strict identity deliberately. `yumemi.nativeQuantityComparison` covers loose equality and ordering, including `<=>`. Nullable loose-equality presence checks remain permitted. [Rule tests](../../tests/PHPStan/InvalidNativeQuantityComparisonRuleTest.php) cover operators, unions, and nullable values.                                                                              |
| Expression context and `Units` cloning             | Compatibility-sensitive enforcement of context ownership    | Retain and reuse the owning context, or create values through a separate context. Foreign, mixed, and expired expression contexts fail; `Units` cannot be cloned. Structural equality and formatting remain independent of context admission. [Context tests](../../tests/ExpressionContextTest.php) preserve those distinctions.                                                                                        |
| `Units::setDefault()` in Fibers                    | Deliberate runtime restriction                              | Install the default during synchronous bootstrap. A Fiber may read it or set the already-installed value, but cannot change it. [Units tests](../../tests/UnitsTest.php) cover the guard.                                                                                                                                                                                                                                |
| Deserialization in Fibers                          | Correctness fix                                             | Each Fiber must restore custom values through its own `Units::deserialize()` call. Another Fiber's temporary context is not inherited. [Interleaving tests](../../tests/FiberDeserializationTest.php) and [scope tests](../../tests/Internal/DeserializationContextTest.php) cover isolation and cleanup.                                                                                                                |
| Native numeric and quantity-union inference        | Compatibility-sensitive corrections                         | `unit()` preserves `int` and `float` alternatives; integer division can infer either native kind. Explicit unit alternatives survive chained arithmetic, and quantity/scalar unions retain all possible units. Update declarations or narrow inputs where needed. [Division tests](../../tests/PHPStan/UnitDivisionTypeTest.php) and the [original review](project-review-2026-09-04.md) retain the regression evidence. |
| Named arguments, unpacking, and PHPDoc identity    | Correctness fixes that can change diagnostics               | Fix newly reported unit mismatches. Import the actual Yumemi quantity classes; scalar pseudo-types remain unqualified. Fixed-shape unpacking is checked, while dynamic unpacking and callable inference remain limited. [Integration tests](../../tests/PHPStan/UnitTypeNodeResolverIntegrationTest.php) and [optional-tag tests](../../tests/PHPStan/YumemiReturnTagExtensionTest.php) cover names and aliases.         |
| Negative and zero scales                           | Correctness fixes to ordering and admission                 | Review custom-scale sorting and remove assumptions based on the old asymmetric results. Reciprocal zero-scale expressions fail. [Comparison conformance](../../tests/Conformance/v1/comparisons.json) and [quantity tests](../../tests/QuantityTest.php) cover exact results and failure boundaries.                                                                                                                     |
| Prefixed formatting                                | Correctness fix with visible spelling changes               | Update output expectations where a shorter symbol would change the parsed meaning. Catalog definitions are unchanged. [Formatting tests](../../tests/Formatter/FormattingPolicyTest.php) cover collisions, punctuation, and round trips.                                                                                                                                                                                 |
| Multiplication exception context IDs               | Observable structured-metadata change                       | Cross-context `Quantity::mul()` reports ascending process-local IDs. Other quantity operations retain receiver-then-argument order. Callers inspecting the metadata must account for this distinction. [Quantity tests](../../tests/QuantityTest.php) verify both orders.                                                                                                                                                |
| Exact-output exceptions                            | Additive recovery categories                                | `NonIntegralValueException` and `NonTerminatingDecimalException` share `NonExactOutputException` and remain within `UnexpectedValueException`. Existing broad catches work. [Exception tests](../../tests/Exception/ExactOutputExceptionTest.php) also preserve overflow and earlier conversion failures.                                                                                                                |
| Native parsing and quantity operators              | Optional additions                                          | Runtime methods and the PHP parser remain available without the extension. Native parsing selects a compatible companion automatically; `YUMEMI_NATIVE_PARSER` controls fallback. PHPStan quantity operators require explicit opt-in. Final paired-version verification remains a release task.                                                                                                                          |
| Parser failures                                    | Diagnostic and admission fixes                              | Malformed UTF-8 is rejected at the first invalid byte. Numeric-domain failures become invalid-expression diagnostics in PHPStan. Expected-token prose is corrected; existing source-span and budget contracts remain. [Parser tests](../../tests/Parser/ParserSyntaxErrorTest.php) and [adapter tests](../../tests/PHPStan/UnitExpressionParserTest.php) cover these boundaries.                                         |

The additive application APIs are `Units::formatText()`, `quantityFromJson()`, `pointFromJson()`, `Quantity::rdiv()`,
`PointQuantity::differenceFrom()`, the rational component accessors, and `Rational` string casting. `Units::format()`
remains available. `difference(other: ...)` retains its old parameter name and behavior, while its preferred replacement
uses `differenceFrom(origin: ...)`.

The changes are already recorded under `Unreleased` in [CHANGELOG.md](../../CHANGELOG.md). This slice adds migration
guidance and compatibility evidence without changing the library or preparing the release metadata.

## Retained Contracts

- The runtime Composer requirements and the minimum PHPStan version are unchanged: PHP `^8.2` with GMP, and PHPStan
  2.2.5 or later for analysis. Runtime-only consumers still do not need PHPStan or `ext-yumemi`.
- `unit()`, `unit_factor()`, and `unit_to()` source and signatures are unchanged from `v0.1.1`. Their analysis results
  and delegated validation can change as described above.
- Existing PHPStan configuration keys retain their defaults. The new `yumemi.quantityOperators` default is `false`.
  Existing primary and optional-tag configuration entry points remain available.
- The generated catalog diff removes unused `prefixRegex` metadata only. Bundled names, aliases, prefix values, and
  conversion definitions are unchanged. Generated representation and removed internal adapters are outside the supported
  application API.
- Documented JSON shapes and historical native payload readers remain supported. The tagged `v0.1.0` and `v0.1.1`
  fixtures are unchanged. Native restoration still validates registry semantics; JSON readers deliberately use the
  receiving registry rather than carrying a source-context identity.
- The internal `InternalQuantity` parent and native parser ABI do not become application extension points. Applications
  retain the supported method and parser contracts through either implementation.

## Verification Evidence

### Committed Declarations

`composer check:bc` passed against the two revisions above with the existing narrow acknowledgements. Those cover the
two rational property visibility changes and the generated parser's skeleton-path constant. No new exclusion was added.
This is not a claim that the unfiltered declaration diff is empty. The checker does not cover implicit cloning, global
helpers, runtime behavior, inference, or persistence, so those surfaces were reviewed separately.

### Runtime Comparison

Twenty small cases compared exported `v0.1.1` source and catalog data with the candidate in separate PHP 8.2.32
processes, using the same installed dependencies and forcing `YUMEMI_NATIVE_PARSER=0`. Reflection confirmed that each
process loaded the selected revision's classes. Expired-context cases collected reference cycles before invoking the
expression. These are controlled source comparisons, not clean consumer installations or a PHP-version matrix.

Representative results:

| Case                                                            | `v0.1.1`                               | Candidate                                                                 |
| --------------------------------------------------------------- | -------------------------------------- | ------------------------------------------------------------------------- |
| Read the numerator property of `Rational(6, 8)`                 | GMP value `3`                          | Private-property `Error`; component methods return GMP values `3` and `4` |
| Set bit 3 on a caller-owned GMP input initially equal to `2`    | Existing rational changes to `10`      | Existing rational stays `2`                                               |
| Equality of meter and second quantities                         | `IncompatibleUnitException`            | `false`                                                                   |
| Equality across independent contexts                            | `IncompatibleQuantityContextException` | `false`                                                                   |
| `compareTo() === 0` across incompatible dimensions              | `IncompatibleUnitException`            | Same exception category                                                   |
| Interpret a foreign expression                                  | Accepted in the probe                  | `IncompatibleExpressionContextException`                                  |
| Derive a meter expression's dimension after its context expires | Accepted in the probe                  | `IncompatibleExpressionContextException`                                  |
| Clone a context; replace the default inside a Fiber             | Both accepted                          | `Error`; `LogicException`                                                 |
| `unit(4, 'meter') / 2`; `fdiv(unit(4, 'meter'), 2)`             | Integer `2`; float `2.0`               | Same values and native kinds                                              |
| Compare one reverse meter with one meter                        | `1`                                    | `-1`                                                                      |
| Compare a zero-scale quantity with zero meters                  | Division-by-zero failure               | `0`                                                                       |
| Construct a reciprocal zero-scale quantity                      | Accepted                               | Division-by-zero failure                                                  |
| Symbol formatting of `millipercent`                             | `m%`                                   | `millipercent`                                                            |
| Difference between 100 and 0 Celsius through the old alias      | `100 * delta_celsius`                  | Same result; also returned by `differenceFrom()`                          |
| Nonintegral or nonterminating exact output                      | `UnexpectedValueException`             | Specific subclasses, both still catchable as `UnexpectedValueException`   |

### Documentation and Regression Checks

The upgrade examples are part of the existing Akashi corpus. A separate full PHPStan CLI check at level 8 accepts the
three new examples and reports exactly `yumemi.nativeQuantityComparison` when a loose object comparison is added.
Dedicated division inference tests and the runtime comparison above verify the documented native kinds.

`DocumentationPhpStanExamplesTest` uses a function-call rule, so it does not exercise every registered diagnostic. The
separate CLI check covers this gap for the new examples. Broader documentation-rule coverage is recorded as a new
[verification follow-up](planning.md#verification-roadmap). Existing examples, directives, and diagnostic expectations
are preserved.

Validation on 2026-09-06:

- `composer test -- tests/Documentation` passed: 66 tests and 1,953 assertions.
- `composer check:full` passed: 2,434 tests, 28,818 assertions, and five expected skips, plus formatting, static
  analysis, documentation build and generated-link validation, and archive consumer checks.
- `nix fmt -- --walk filesystem docs/pages/getting-started.md docs/development/planning.md docs/development/release-0.2-compatibility.md`
  formatted the three changed documents. `git diff --check` passed.
- Local Markdown validation checked 101 links in or targeting the changed documents, including 13 inbound links. All 61
  rendered `h2`/`h3` headings across nine chapters matched the sidebar metadata. Browser titles and chapter navigation
  resolved. Existing public headings and fenced examples were preserved, with exactly three PHP examples added.

## Remaining Release Work

Refresh the changelog and installation/status metadata, identify the tested native companion version, and run the
complete release gate on the final committed candidate. That includes the Nix/PHP matrix, dependency audit, API
comparison, historical persistence, archive inspection, and CI for the exact release commit. The signature-tool install
reported abandoned development packages; review those notices during the dependency audit.

No release was tagged or published in this slice. External publication checks, new 0.2 persistence fixtures, and removal
of temporary API acknowledgements must wait for the corresponding release steps. Performance measurements were not
repeated for this documentation slice, and existing benchmark records should not be presented as new 0.2 measurements.
