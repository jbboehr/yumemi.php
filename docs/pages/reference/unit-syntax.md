# Unit Syntax Reference

<figure class="logion" data-logion="SFA 92:41">
<div class="logion-text">
<blockquote>
<p>Within the blue cupola, bronze bees circled an empty place in the mosaic where no star had been set, and their wings sounded like rain upon a sealed roof. The learned accused the vacancy, but the swarm entered it with their labor and made no image. Thus was the unfigured place guarded from invention. Let the unfinished heaven remain open above the choir.</p>
</blockquote>
<p class="logion-citation">— <cite>Scholia of the Fifth Archive 92:41</cite></p>
</div>
<img src="../images/logia/SFA-92_41.webp" alt="Bronze bees circling an unfilled star-shaped place in a blue cupola mosaic" width="960" height="540" loading="eager" fetchpriority="high">
</figure>

Unit strings can be simple names such as `meter`, products such as `kilogram * meter / second^2`, or exact constants
such as `100 centimeter`. Yumemi uses the same parser and catalog resolver at runtime and in its PHPStan extension, so
these strings have the same meaning in `Units::parse()`, `Quantity`, `unit_int<'...'>`, `unit_float<'...'>`, and
`unit_numeric_string<'...'>`.

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

> **Precedence warning:** Exponentiation binds more tightly than multiplication and division. Adjacency, `*`, `.`, `·`,
> and `/` otherwise share precedence and associate left, matching UDUNITS2. Therefore `meter / foot second` means
> `(meter / foot) * second`, not `meter / (foot * second)`.

Whitespace is ignored except that adjacent simple expressions imply multiplication. Use parentheses around a compound
denominator:

```text
centimeter / (foot * second)
```

Decimal and scientific constants are parsed exactly as rational numbers. They are not converted through binary floating
point.

For example:

```php
<?php

use jbboehr\Yumemi\Units;

$units = Units::default();

assert($units->parse('meter · second⁻²')->toString() === 'meter * second ^ -2');
assert($units->parse('1000')->toString() === '1000');
assert($units->parse('1.25 meter')->toString() === '5/4 * meter');
assert($units->parse('meter / second kilogram')->equals($units->parse('meter / second * kilogram')));
assert($units->dimension('(meter / second)^2')->toString() === 'length ^ 2 / time ^ 2');
```

## Temperatures And Offset Units

Temperature scales such as Celsius have an offset as well as a scale. Convert them with `convert()`, `convertFloat()`,
or `unit_to()`. Use `Units::point()` when an exact value must retain its coordinate scale.

The parser form `identifier @ number` defines an affine coordinate origin. For example, `kelvin @ 273.15` maps zero in
the new coordinate system to exactly `273.15 kelvin`. `areCompatible()` and `dimension()` inspect the resulting
dimension. `conversionFactor()` succeeds only when the conversion has no offset and otherwise throws
`NonMultiplicativeConversionException`. Custom registry definitions may use the same `@` form.

Affine units are not part of ordinary multiplicative expression or quantity algebra. Use their generated difference
units, such as `delta_celsius`, `delta_fahrenheit`, `Δ°C`, or `Δ°F`, when a temperature interval participates in
products, quotients, or powers. Yumemi never rewrites `celsius / second` implicitly; write `delta_celsius / second`
explicitly. See [Affine Conversion](runtime.md#affine-conversion) for executable conversion and point operations.

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

## Semantic Capabilities

The parser recognizes a few UDUNITS2 forms that ordinary multiplicative expressions deliberately do not implement:

- addition and subtraction inside unit expressions, such as `meter + second`;
- affine-offset syntax using `@` outside explicit conversion boundaries;
- non-integer powers, such as `meter^0.5`;
- logarithmic unit definitions.

Unsupported syntax written through an expression API throws `UnsupportedSyntaxException`. The
[runtime reference](runtime.md#affine-conversion) defines where standalone affine units can execute, and
[Catalog Semantic Support](catalog.md#catalog-semantic-support) defines the introspection model for affine, logarithmic,
and unsupported expressions.

Synthesized `delta_*` and `Δ` names are ordinary multiplicative catalog entries, not special parser syntax.

`Quantity::pow()` and PHPStan's unit exponent inference likewise accept only integer powers. `Quantity::root()` is a
separate exact operation for positive integer degrees; it does not add fractional-power syntax to the parser. Rational
exponents and explicit approximate powers remain deferred, and a `float` exponent will not be silently accepted. Integer
exponents are limited to the inclusive range `-10000` through `10000`, including powers formed by reducing nested
expressions; root degrees are limited to `1` through `10000`.

## Resource Limits

Every unit-expression entry point uses the same fixed parser budget:

| Resource                        | `$limit` key    |     Maximum |
| ------------------------------- | --------------- | ----------: |
| Complete input                  | `input-bytes`   | 4,096 bytes |
| Non-whitespace lexical tokens   | `token-count`   |         256 |
| Parenthesis nesting             | `nesting-depth` |          64 |
| One identifier or numeric token | `token-bytes`   | 1,024 bytes |

The `$limit` keys in the table are the stable discriminator values emitted by Yumemi.

Source bytes include whitespace and multibyte UTF-8 encoding. Operators and parentheses count as tokens. The token limit
also bounds the size and depth of the resulting expression tree.

The shared parser reports an exceeded limit with `Parser\ExpressionLimitExceededException`, which extends PHP's
`LengthException` and implements Yumemi's common `ExceptionInterface`. Its `limit`, `maximum`, and `observed` properties
identify the failed budget. A token-level failure also provides a `span`; an input-length failure occurs before
tokenization and has no source span. The span is available through both the `span` property and `getSpan()`. Direct
parser-backed APIs such as `Units::parse()` throw this exception. The native `unit()`, `unit_factor()`, and `unit_to()`
helpers preserve their `InvalidArgumentException` boundary and retain the limit exception as the previous exception.

When a limit is exceeded inside a catalog or custom definition while resolving a parsed outer expression, the outer
exception's span identifies the unit name supplied by the caller. The definition-local limit failure remains available
as the previous exception.

These limits apply to bundled and custom unit definitions as well as direct runtime and PHPStan parsing. They are
defense in depth, not a sandbox. An application accepting unit expressions from an external boundary should still apply
the smaller input and complexity policy appropriate to that boundary.

## Errors And Source Locations

Malformed syntax throws `Parser\ParseException`. When available, its `SourceSpan` is a zero-based, half-open byte range
in the decoded unit expression. The exception message renders a one-based line and column plus a bounded caret excerpt.
Malformed numeric text such as `1.2.3` is reported as syntax, and the source span covers the complete malformed token.
Input must be valid UTF-8: a malformed byte sequence produces the same exception with a span marking its first invalid
byte, before either parser backend or the successful-expression cache sees the input.

Unknown names throw `UnitNotFoundException`. Parsed but unsupported constructs throw `UnsupportedSyntaxException` or a
more specific semantic exception. These runtime exceptions expose an optional `span` property using the same zero-based,
half-open byte convention. A direct failure identifies the offending name or construct. When resolution descends through
an alias or stored catalog definition, the span remains attached to the outer identifier written by the caller rather
than referring to source text that the caller did not provide.

The PHPStan extension uses the same parser and resolver, and its parse-result objects expose the same range through
`errorSpan()`. Its handling of constant and dynamic strings is documented in [Limitations](phpstan.md#limitations).
