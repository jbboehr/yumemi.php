# Operator Overloading Extension Plan

Snapshot date: 2026-08-29

PHP does not expose operator overloading to userland classes, but internal classes can participate in object operators
through Zend object handlers. In particular, `zend_object_handlers` has a `do_operation` slot that an extension can
implement for object arithmetic.

Yumemi can use this as an optional extension layer while keeping the main runtime library pure PHP.

## Current Status

The method-only library seam and the separate `php-yumemi` native handler are implemented. `Quantity` extends an empty,
internal `InternalQuantity` PHP fallback when `ext-yumemi` is absent; when the extension is loaded first, Composer uses
the native base and operators delegate to the existing methods, including power and reverse division. An opt-in
integration suite verifies the real extension against this library. PHPStan models the same operator surface only when
`yumemi-operators.neon` is explicitly loaded; method-call inference remains available through the primary configuration.

## Goal

Allow this when an optional extension is installed:

```php
$units = Units::default();

$distance = $units->quantity(10, 'meter');
$time = $units->quantity(2, 'second');

$speed = $distance / $time;
$total = $distance + $units->quantity(5, 'meter');
```

The method API remains canonical and always available:

```php
$speed = $distance->div($time);
$total = $distance->add($units->quantity(5, 'meter'));
```

Operators are sugar. They should not be required for correctness, static analysis, or basic package use.

## Class Shape

The library defines an internal hook base class:

```php
namespace jbboehr\Yumemi;

/** @internal */
abstract class InternalQuantity
{
}
```

When the extension is not loaded, Composer autoloads the PHP fallback version of `InternalQuantity`.

When the extension is loaded, the extension registers `InternalQuantity` first as an internal abstract class with custom
object handlers. The fallback file is never autoloaded because the class already exists.

The real runtime class remains pure PHP:

```php
namespace jbboehr\Yumemi;

final class Quantity extends InternalQuantity
{
    public function add(self $other): self {}

    public function sub(self $other): self {}

    public function mul(self|int|Rational $other): self {}

    public function div(self|int|Rational $other): self {}

    public function rdiv(int|Rational $numerator): self {}

    public function pow(int $power): self {}
}
```

The extension's object handler delegates operators to those methods:

| PHP operator | Zend opcode | Quantity method |
| ------------ | ----------- | --------------- |
| `+`          | `ZEND_ADD`  | `add()`         |
| `-`          | `ZEND_SUB`  | `sub()`         |
| `*`          | `ZEND_MUL`  | `mul()`         |
| `/`          | `ZEND_DIV`  | `div()`         |
| `**`         | `ZEND_POW`  | `pow()`         |
| unary `+`    | `ZEND_MUL`  | `mul(1)`        |
| unary `-`    | `ZEND_MUL`  | `mul(-1)`       |

For scalar-left division, `ZEND_DIV` delegates to `rdiv()` on the right-hand quantity instead of `div()`. Zend lowers
unary signs through multiplication on the supported PHP versions, so the handler reaches the same `mul()` method without
a separate unary callback.

This keeps one semantic implementation: the PHP methods. The C extension should not duplicate unit arithmetic rules.

## Why A Base Class

The base-class strategy has better ergonomics than replacing `Quantity` with an internal class:

- `Quantity` stays normal PHP source.
- Public API docs remain accurate without the extension.
- Composer autoloading stays conventional.
- Runtime semantics stay in PHP.
- The `Quantity` seam only supplies object handlers; the extension's later syntax-only parser remains a separate
  optional adapter.
- The extension can be optional without creating two unrelated `Quantity` implementations.

The extension's committed PHPT suite verifies that a userland subclass inherits the internal allocation and handler
behavior on supported PHP 8.2 through 8.5 NTS builds. This library's extension integration suite separately exercises
the real `Quantity` class and its Composer autoload boundary.

## Operator-Handler Responsibilities

The operator handler should do as little as possible:

- Register `jbboehr\Yumemi\InternalQuantity`.
- Install a custom object handlers table for that class.
- Implement `do_operation`.
- Allocate objects in a way that still supports normal userland subclass properties.
- Delegate arithmetic to existing userland methods.

Non-goals:

- Reimplement `Quantity` in C.
- Reimplement `Rational` in C.
- Reimplement unit parsing or registry lookup in C.
- Make operators work without the extension.
- Support every PHP operator.
- Install a `compare` handler or make PHP object comparison unit-aware. Zend exposes one general object-comparison
  relation to explicit operators and implicit consumers such as regular array sorting. Delegating that relation to
  `compareTo()` would therefore make unrelated consumers convert compatible quantities and throw for incompatible
  dimensions or registry contexts. The named comparison methods remain the deliberate boundary.

The later native lexer/parser does not change these operator boundaries. It returns a neutral syntax tree through an
internal, versioned ABI and performs no unit lookup, normalization, conversion, registry access, or `Quantity`
construction. Yumemi selects it only when its atomic compatibility check accepts the expected ABI, adapts its results
into the existing PHP AST and exception contracts, and otherwise retains the generated PHP lexer/parser automatically.

## Handler Behavior

`do_operation` should handle:

- `ZEND_ADD`
- `ZEND_SUB`
- `ZEND_MUL`
- `ZEND_DIV`
- `ZEND_POW`

Everything else should fail with normal PHP behavior.

Recommended dispatch:

```text
ZEND_ADD -> call $left->add($right)
ZEND_SUB -> call $left->sub($right)
ZEND_MUL -> call $left->mul($right)
ZEND_DIV -> call $left->div($right)
ZEND_POW -> call $left->pow($right)
```

The implemented operand policy is:

- `Quantity + Quantity` and `Quantity - Quantity` delegate to the left quantity.
- `Quantity * Quantity` and `Quantity / Quantity` delegate to the quantity method.
- `Quantity * int|Rational` and `Quantity / int|Rational` are supported.
- `int|Rational * Quantity` is supported because multiplication is commutative.
- `int|Rational / Quantity` delegates to `Quantity::rdiv()` and produces a reciprocal unit.
- Scalar-left addition, subtraction, and exponentiation fail with normal unsupported-operand `TypeError` behavior.
- Exceptions thrown by delegated methods propagate unchanged.
- References and temporary operands are covered by the extension PHPTs and this library's integration suite. Zend may
  select either quantity as the multiplication handler receiver for some temporary/variable forms. For accepted
  operands, both quantities necessarily share one `Units` object, `Quantity` is final, exact magnitude multiplication is
  commutative, and `ExprReducer` canonicalizes symbolic and resolved factors. The result therefore has the same concrete
  type, value, symbolic unit, formatted unit, normalized form, and context identity whichever quantity receives `mul()`.
  For rejected cross-context operands, `Quantity::mul()` orders the two process-local context IDs canonically, so the
  exception class, message, and structured metadata also remain independent of handler receiver selection.

## PHP Fallback

The PHP fallback class is intentionally empty:

```php
namespace jbboehr\Yumemi;

/** @internal */
abstract class InternalQuantity
{
}
```

It should not try to emulate operators. It cannot.

Without the extension:

```php
$a + $b; // normal PHP unsupported operand TypeError
```

With the extension:

```php
$a + $b; // delegates to $a->add($b)
```

This means examples and docs must clearly mark operator syntax as extension-only.

## Composer And Autoloading

Composer can autoload the fallback class through ordinary PSR-4:

```text
src/InternalQuantity.php
src/Quantity.php
```

If the extension is loaded before Composer autoloading, `InternalQuantity` already exists and Composer will not include
the fallback file for normal class lookup.

Important constraints:

- Do not directly `require src/InternalQuantity.php` when the extension may be loaded.
- Keep both normal and authoritative Composer autoload coverage.
- Keep extension class name exactly identical to the fallback FQCN.

Potential Composer suggestion:

```json
{
  "suggest": {
    "ext-yumemi": "Enables optional operator overloading for Quantity objects."
  }
}
```

Do not require the extension.

## PHPStan Integration

The PHPStan extension understands both forms:

```php
$speed = $distance->div($time);
$speed = $distance / $time;
```

The existing operator type-specifying extension performs the inference. Invalid combinations produce PHPStan's standard
`binaryOp.invalid` identifier, while `yumemi.quantityOperators` remains disabled by default so ordinary analysis matches
runtime PHP without `ext-yumemi`.

Static-analysis policy should mirror runtime behavior:

- `+` and `-` on `Quantity` use the same dimension-compatible, converting rules as `add()` and `sub()`.
- `*` and `/` use the same rules as `mul()` and `div()`.
- `**` uses the same integer-power rules as `pow()`.
- Scalar-left `/` uses the same reciprocal-unit rules as `rdiv()`.
- Native `unit_int` / `unit_float` operators remain a separate static-only surface and cannot perform runtime
  conversion; their `+` / `-` policy remains exact-unit unless deliberately configured otherwise.

Operators should not be added to examples until PHPStan can check them. Otherwise users get nice syntax but lose the
project's main value proposition.

## Spike Checklist

Build the smallest possible extension before designing the full package:

1. Register internal abstract `InternalQuantity`.
2. Set a custom `create_object` and handlers table.
3. Implement `do_operation` with obvious debug behavior.
4. Define a PHP `Quantity extends InternalQuantity` with public properties.
5. Confirm `new Quantity() + new Quantity()` reaches `do_operation`.
6. Confirm PHP-declared properties work normally.
7. Confirm PHP methods on the subclass can be called from the handler.
8. Confirm returned objects and exceptions behave correctly.
9. Confirm clone/debug/GC/property behavior is not broken.
10. Test PHP 8.2, 8.3, 8.4, and 8.5 if available.
11. Test Composer normal autoload.
12. Test Composer optimized autoload.
13. Test without the extension loaded.

Only after this spike passes should we decide package shape.

## Likely Package Shape

Main package:

```text
jbboehr/yumemi
```

Optional extension package:

```text
ext-yumemi
```

Possible source layout if kept in one repository:

```text
src/
  InternalQuantity.php
  Quantity.php
ext/
  config.m4
  php_yumemi.h
  yumemi.c
  yumemi_quantity.c
  tests/
```

Possible separate repository:

```text
jbboehr/yumemi-ext
```

Recommendation: start in a separate spike directory or separate repository. Merge into the main repository only after
the inheritance/handler proof works.

## Test Plan

Pure PHP tests:

- Existing method-based quantity tests continue to pass without the extension.
- Operator examples are skipped unless the extension is loaded.
- Fallback `InternalQuantity` loads normally.

Extension tests:

- PHPT tests for direct operator behavior.
- PHPT tests for unsupported operands.
- PHPT tests for exceptions thrown by delegated methods.
- PHPT tests for Composer autoload interaction if practical.

Integration tests:

- Run the normal PHPUnit suite without the extension.
- Run additional operator tests with the extension.
- Run PHPStan tests for method calls and operators once the PHPStan layer exists.

The opt-in extension suite accepts an explicit module path so the pure-PHP package does not compile or discover the
extension implicitly:

```shell
make test-extension YUMEMI_EXTENSION_PATH=/path/to/yumemi.so
```

CI:

- Keep extension CI separate from the pure PHP baseline.
- Do not make the pure PHP package require extension compilation.
- Pin the extension's `develop` branch in the Nix flake and run the opt-in integration suite against PHP 8.2 through 8.5
  as separate checks. The ordinary PHPUnit matrix remains extension-free.

## Risks

### Handler Inheritance

The whole design depends on `Quantity extends InternalQuantity` receiving the internal base class handlers. The
extension PHPT matrix covers inheritance across PHP 8.2 through 8.5, and the opt-in integration suite covers the real
`Quantity` class when a matching module is available.

If this fails, fallback options are:

- make `Quantity` itself an internal class when the extension is loaded
- use a separate internal wrapper class
- abandon operator overloading

### Object Allocation

The internal base class must allocate objects in a way compatible with userland subclasses and declared properties.
Using standard object behavior wherever possible is important.

### Delegation Recursion

The PHP methods must not use the overloaded operators internally:

```php
public function add(self $other): self
{
    return $this + $other; // bad
}
```

The methods remain the primitive operations. Operators delegate to methods, never the reverse.

### Scalar-Left Operations

Scalar-left multiplication and division require Zend to select the right quantity's handler. The committed PHPT and
real-library integration suites cover literal, variable, `Rational`, compound-assignment, invalid-operand, and exception
paths. Scalar-left subtraction remains deliberately unsupported because the method API has no useful corresponding
quantity operation.

### Installation Friction

PHP extensions are harder to install than Composer packages. The extension must remain optional.

### Semantic Drift

If any arithmetic logic moves into C, it can drift from the PHP implementation. Avoid that unless profiling proves it is
necessary.

### Debuggability

Operator syntax can hide meaningful errors. Exception messages from delegated methods need to stay clear.

## Priority

This is a good experiment, but it should not block the core project.

The feasibility spike, separate extension repository, mechanical handler, method-only library seam, end-to-end operator
integration, exponentiation, scalar-left division, opt-in PHPStan operator inference, and separate Nix integration
matrix are complete. The extension's release policy now fixes the initial support envelope, provisional seams, version
coordination, release-note ownership, and tagged-release gate. The remaining release work is operational: verify and
publish the first immutable PIE tag, then prove a clean install with its named compatible Yumemi release.

The spike is now unblocked, but it remains a side quest. The main product value is still static dimensional analysis,
and the extension must not become a dependency of the pure-PHP package.

## Strategic Conclusion

The `InternalQuantity` base-class plan is the selected optional operator-overloading architecture:

- pure PHP remains the source of truth
- Composer users are not forced to compile anything
- extension users get natural arithmetic syntax while comparison remains method-based
- PHPStan supports the extension's opt-in arithmetic surface, including unary signs, while rejecting native object
  comparison as a substitute for the named methods

The handler and real `Quantity` integration are now empirical, committed tests rather than an architectural assumption.
The integration matrix covers unary signs, named variables, reversed source order, helper-return and expression
temporaries, compound assignment, deliberately non-alphabetical symbolic factors, shared-registry/different-context
failures, and different-registry failures. Receiver reordering is therefore a resolved semantic question rather than a
release blocker. Release packaging is now qualified without making the extension a pure-PHP package dependency; only the
first tag, Packagist indexing of that tag, and clean paired-install verification remain.
