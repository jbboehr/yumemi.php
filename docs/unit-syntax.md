# Unit Syntax Reference

Yumemi uses the same unit-expression parser and catalog resolver at runtime and in its PHPStan extension. Multiplicative
unit strings therefore have the same meaning in `Units::parse()`, `Quantity`, `unit_int<'...'>`, and
`unit_float<'...'>`. Explicit conversion APIs additionally understand the affine `@` form described below.

## Supported Expressions

The semantic unit language supports:

| Form           | Examples                                     | Meaning                                            |
| -------------- | -------------------------------------------- | -------------------------------------------------- |
| Identifier     | `meter`, `international_foot`, `Pa`          | Catalog unit name, alias, symbol, or prefixed name |
| Multiplication | `m * s`, `m s`, `m.s`, `m · s`               | Product of unit expressions                        |
| Division       | `meter / second`                             | Quotient of unit expressions                       |
| Integer power  | `meter^2`, `second^-2`, `meter²`, `second⁻²` | Unit expression raised to an integer power         |
| Grouping       | `(meter / second)^2`                         | Parenthesized subexpression                        |
| Exact constant | `1000`, `1.25`, `1e3`, `1000 meter`          | Exact rational alone or scaling a unit expression  |

Whitespace is ignored except that adjacent simple expressions imply multiplication. Exponentiation binds more tightly
than multiplication and division. Adjacency, `*`, `.`, `·`, and `/` share precedence and associate left, matching
UDUNITS2: `meter / foot second` is equivalent to `(meter / foot) * second`. Parentheses are therefore required for a
compound denominator:

```text
centimeter / (foot * second)
```

Decimal and scientific constants are parsed exactly as rational numbers. They are not converted through binary floating
point.

At explicit conversion boundaries, `identifier @ number` defines an affine coordinate origin. For example,
`kelvin @ 273.15` maps zero in the new coordinate system to exactly `273.15 kelvin`. This form is accepted by
`convert()`, `convertFloat()`, `compatible()`, `dimension()`, `conversionFactor()`, `unit_to()`, and custom registry
definitions. It is not part of ordinary multiplicative expression or quantity algebra.

For example:

```php
<?php

require 'vendor/autoload.php';

use jbboehr\Yumemi\Units;

$units = Units::default();

assert($units->parse('meter · second⁻²')->toString() === 'meter * second ^ -2');
assert($units->parse('1000')->toString() === '1000');
assert($units->parse('1.25 meter')->toString() === '5/4 * meter');
assert($units->parse('meter / second kilogram')->equals($units->parse('meter / second * kilogram')));
assert($units->dimension('(meter / second)^2')->toString() === 'length ^ 2 / time ^ 2');
```

## Unit Names

Unit lookup is case-sensitive. Exact names always win before prefix decomposition. This matters for short symbols:

- `Pa` is pascal.
- `pa` is pico-are.
- `PA` is peta-ampere.

Yumemi does not add case-folded aliases or special-case catalog-valid ambiguities. A wrong-case name is rejected even
when a differently cased unit exists.

The generated UDUNITS2 catalog contains canonical names, declared aliases, symbols, explicit plurals, and unambiguous
generated plurals. Runtime lookup does not guess additional plural forms. Prefixes are tried only after exact lookup,
with longer prefix spellings considered first, and the remaining suffix must itself be an exact catalog name.

For example, `micrometer` resolves as the `micro` prefix applied to `meter`, while an exact catalog entry such as
`minute` is never decomposed merely because its first characters resemble a prefix.

## Parsing, Resolution, And Formatting

These operations intentionally answer different questions:

- `Units::parse()` parses the complete expression, resolves every identifier through the catalog, and reduces the
  resulting expression.
- `Units::parseUnit()` is an explicit alias of `Units::parse()`.
- `Units::parseQuantity()` folds all explicit constants into one exact magnitude and preserves the remaining symbolic
  unit. Catalog conversion factors are not extracted from named units.
- `Units::unit()` resolves one catalog unit name, including dynamic prefix decomposition.
- `Units::format()` parses string input symbolically and formats the supplied spelling without requiring every name to
  exist in the catalog.
- `Units::normalize()` parses and resolves string input, then substitutes derived-unit definitions.

Formatting is therefore not a unit-validation API. Use `parse()`, `parseUnit()`, `parseQuantity()`, `unit()`,
conversion, compatibility, or quantity construction when unknown names must fail.

## Unicode Syntax

The parser accepts the Unicode middle dot `·` as multiplication and superscript integers as postfix powers. Superscript
`+` and `-` signs are accepted only when followed by at least one superscript digit. ASCII and Unicode forms can be
mixed in one expression.

Supported examples include:

```text
m · kg / s²
(meter / second)⁺²
second⁻²
```

Unicode formatter output remains parser-compatible when the dimensionless style is numeric. See the
[runtime reference](runtime.md#formatting) for formatting policy.

## Unsupported Semantics

The parser recognizes a few UDUNITS2 forms that the multiplicative expression model deliberately does not implement:

- addition and subtraction inside unit expressions, such as `meter + second`;
- affine-offset syntax using `@` outside explicit conversion boundaries;
- non-integer powers, such as `meter^0.5`;
- logarithmic unit definitions.

Unsupported syntax written through an expression API throws `UnsupportedSyntaxException`. Known affine catalog entries
can be converted as standalone units, but cannot be multiplied, divided, raised to powers, prefixed, branded with
`unit()`, or stored in `Quantity`. Affine PHPStan targets from `unit_to()` remain plain `float`; a multiplicative target
such as `kelvin` retains its `unit_float<'kelvin'>` brand.

Known logarithmic catalog entries remain available for exact introspection with structured support reasons, but
evaluation throws `UnsupportedUnitException`. They therefore remain distinct from unknown names.

`Quantity::pow()` and PHPStan's unit exponent inference likewise accept only integer powers. Exact rational roots and
explicit approximate powers are deferred features; a `float` exponent will not be silently accepted.

## Errors And Source Locations

Malformed syntax throws `Parser\ParseException`. When available, its `SourceSpan` is a zero-based, half-open byte range
in the decoded unit expression. The exception message renders a one-based line and column plus a bounded caret excerpt.

Unknown names throw `UnitNotFoundException`. Parsed but unsupported constructs throw `UnsupportedSyntaxException`. Those
failures occur after parsing and currently do not carry source spans.

The PHPStan extension uses the same parser and resolver. Constant invalid unit strings fail analysis with the runtime
diagnostic text; genuinely dynamic strings cannot be validated and fall back to native PHPStan types.
