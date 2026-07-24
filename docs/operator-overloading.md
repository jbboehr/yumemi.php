# Operator Overloading Extension Plan

Snapshot date: 2026-07-24

PHP does not expose operator overloading to userland classes, but internal classes can participate in object operators
through Zend object handlers. In particular, `zend_object_handlers` has a `do_operation` slot that an extension can
implement for object arithmetic.

IMM can use this as an optional extension layer while keeping the main runtime library pure PHP.

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

## Proposed Class Shape

Define an internal hook base class:

```php
namespace jbboehr\IudexMensurarumMysteriorum;

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
namespace jbboehr\IudexMensurarumMysteriorum;

final class Quantity extends InternalQuantity
{
    public function add(self $other): self {}

    public function sub(self $other): self {}

    public function mul(self|int|Rational $other): self {}

    public function div(self|int|Rational $other): self {}
}
```

The extension's object handler delegates operators to those methods:

| PHP operator | Zend opcode | Quantity method |
| ------------ | ----------- | --------------- |
| `+`          | `ZEND_ADD`  | `add()`         |
| `-`          | `ZEND_SUB`  | `sub()`         |
| `*`          | `ZEND_MUL`  | `mul()`         |
| `/`          | `ZEND_DIV`  | `div()`         |

This keeps one semantic implementation: the PHP methods. The C extension should not duplicate unit arithmetic rules.

## Why A Base Class

The base-class strategy has better ergonomics than replacing `Quantity` with an internal class:

- `Quantity` stays normal PHP source.
- Public API docs remain accurate without the extension.
- Composer autoloading stays conventional.
- Runtime semantics stay in PHP.
- The extension only supplies object handlers.
- The extension can be optional without creating two unrelated `Quantity` implementations.

The expectation is that a userland subclass of an internal base class inherits the internal allocation/handler behavior,
similar to how userland classes can extend SPL internal classes. This should still be verified with a small spike across
supported PHP versions.

## Extension Responsibilities

The extension should do as little as possible:

- Register `jbboehr\IudexMensurarumMysteriorum\InternalQuantity`.
- Install a custom object handlers table for that class.
- Implement `do_operation`.
- Optionally implement `compare` later.
- Allocate objects in a way that still supports normal userland subclass properties.
- Delegate arithmetic to existing userland methods.

Non-goals for the first version:

- Reimplement `Quantity` in C.
- Reimplement `Rational` in C.
- Reimplement unit parsing or registry lookup in C.
- Make operators work without the extension.
- Support every PHP operator.

## Handler Behavior

`do_operation` should handle:

- `ZEND_ADD`
- `ZEND_SUB`
- `ZEND_MUL`
- `ZEND_DIV`

Everything else should fail with normal PHP behavior.

Recommended dispatch:

```text
ZEND_ADD -> call $left->add($right)
ZEND_SUB -> call $left->sub($right)
ZEND_MUL -> call $left->mul($right)
ZEND_DIV -> call $left->div($right)
```

Questions to settle in the spike:

- What happens when the quantity is on the right-hand side of a scalar operation?
- Should `2 * $quantity` work, or only `$quantity * 2`?
- Should `$quantity / 2` work, but `2 / $quantity` fail?
- Can error messages preserve the normal exception behavior from the called PHP methods?
- Are references, temporary zvals, and return-value lifetimes handled cleanly?

Recommended first semantic policy:

- Support `Quantity + Quantity`.
- Support `Quantity - Quantity`.
- Support `Quantity * Quantity`.
- Support `Quantity / Quantity`.
- Support `Quantity * int`.
- Support `Quantity / int`.
- Support `Quantity * Rational`.
- Support `Quantity / Rational`.
- Defer scalar-left operations until the basic path is stable.

## PHP Fallback

The PHP fallback class should be intentionally empty:

```php
namespace jbboehr\IudexMensurarumMysteriorum;

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
- Avoid authoritative classmap assumptions until tested.
- Add tests for normal Composer autoload and optimized Composer autoload.
- Keep extension class name exactly identical to the fallback FQCN.

Potential Composer suggestion:

```json
{
  "suggest": {
    "ext-imm": "Enables optional operator overloading for Quantity objects."
  }
}
```

Do not require the extension.

## PHPStan Integration

The PHPStan extension must understand both forms:

```php
$speed = $distance->div($time);
$speed = $distance / $time;
```

Operator support likely needs custom PHPStan rules or type inference around binary operations.

Static-analysis policy should mirror runtime behavior:

- `+` and `-` use the same rules as `add()` and `sub()`.
- `*` and `/` use the same rules as `mul()` and `div()`.
- If runtime addition remains exact-unit-only, PHPStan operator checks should also be exact-unit-only unless configured.
- If PHPStan later supports dimension-compatible addition as a relaxed mode, the method and operator forms should share
  that mode.

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
jbboehr/imm
```

Optional extension package:

```text
ext-imm
```

Possible source layout if kept in one repository:

```text
src/
  InternalQuantity.php
  Quantity.php
ext/
  config.m4
  php_imm.h
  imm.c
  imm_quantity.c
  tests/
```

Possible separate repository:

```text
jbboehr/imm-ext
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

CI:

- Keep extension CI separate from the pure PHP baseline.
- Do not make the pure PHP package require extension compilation.
- Add extension CI only after the spike stabilizes.

## Risks

### Handler Inheritance

The whole design depends on `Quantity extends InternalQuantity` receiving the internal base class handlers. This is
expected, but it must be tested across PHP versions.

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

`$quantity * 2` is natural because the quantity object is the left operand. `2 * $quantity` may or may not dispatch in
the way we need. This should be tested before promising support.

### Installation Friction

PHP extensions are harder to install than Composer packages. The extension must remain optional.

### Semantic Drift

If any arithmetic logic moves into C, it can drift from the PHP implementation. Avoid that unless profiling proves it is
necessary.

### Debuggability

Operator syntax can hide meaningful errors. Exception messages from delegated methods need to stay clear.

## Priority

This is a good experiment, but it should not block the core project.

Recommended order:

1. Finish enough runtime hardening that `Quantity::add/sub/mul/div` are stable.
2. Build the PHPStan MVP for method calls.
3. Spike `InternalQuantity` handler inheritance.
4. If the spike works, add optional operator support.
5. Extend PHPStan to understand operators.

The only reason to pull this earlier is morale or curiosity. It is a reasonable side quest, but the main product value
is still static dimensional analysis.

## Strategic Conclusion

The `InternalQuantity` base-class plan is probably the best optional operator-overloading architecture:

- pure PHP remains the source of truth
- Composer users are not forced to compile anything
- extension users get natural arithmetic syntax
- PHPStan can eventually support both method calls and operators

The first decision point is empirical: prove the handler inheritance and userland-subclass property behavior in a tiny
extension. If that works, the rest is normal extension engineering.
