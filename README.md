# Iudex Mensurarum Mysteriorum

Runtime unit conversion and static dimensional analysis for PHP.

This project is in early planning. See [docs/planning.md](docs/planning.md) for the initial design notes.

## Runtime Usage

The runtime API keeps unit arithmetic and unit conversion separate. Quantity operations reduce the unit expression
that the caller chose, while `to()` and `valueIn()` explicitly convert through the unit catalog.

**String forms:** `Quantity` (and error messages) use display form via `ExprFormatter`
(e.g. `meter / second`). `Expr::toString()` is a structural/debug dump
(e.g. `meter * second ^ -1`). Equality uses structure, not either string form.

**`Units::default()`** returns a shared instance (safe to call repeatedly). Use
`new Units($registry)` when you need an isolated catalog or context.

The PHP examples in this section are executed by the test suite.

```php
<?php

require 'vendor/autoload.php';

use jbboehr\IudexMensurarumMysteriorum\Units;

$units = Units::default();

$length = $units->quantity(1488, 'inch')->to('foot');

assert($length->toString() === '124 * foot');
assert($length->valueIn('inch')->toString() === '1488');
```

Multiplication and division reduce chosen unit syntax, but do not substitute compatible unit definitions.

```php
<?php

require 'vendor/autoload.php';

use jbboehr\IudexMensurarumMysteriorum\Units;

$units = Units::default();

$distance = $units->quantity(2, 'meter / second')->mul($units->quantity(3, 'second'));

assert($distance->toString() === '6 * meter');
assert($distance->unitToString() === 'meter');
```

Compatible dimensions are not implicitly converted during addition or subtraction. Convert explicitly when that is
what you want.

```php
<?php

require 'vendor/autoload.php';

use jbboehr\IudexMensurarumMysteriorum\Units;

$units = Units::default();

$total = $units
    ->quantity(1, 'meter')
    ->add($units->quantity(100, 'centimeter')->to('meter'));

assert($total->toString() === '2 * meter');
assert($total->valueIn('centimeter')->toString() === '200');
```

Without that explicit conversion, addition and subtraction require the same reduced unit syntax.

```php
<?php

require 'vendor/autoload.php';

use jbboehr\IudexMensurarumMysteriorum\Exception\IncompatibleUnitException;
use jbboehr\IudexMensurarumMysteriorum\Units;

$units = Units::default();

try {
    $units->quantity(1, 'meter')->add($units->quantity(100, 'centimeter'));
    assert(false);
} catch (IncompatibleUnitException) {
}
```

You can still ask for converted values from a composed quantity when you need the catalog-aware result.

```php
<?php

require 'vendor/autoload.php';

use jbboehr\IudexMensurarumMysteriorum\Units;

$units = Units::default();

$rate = $units->quantity(2, 'centimeter / second')->div($units->quantity(3, 'foot'));

assert($rate->toString() === '2/3 * centimeter / (foot * second)');
assert($rate->valueIn('1 / second')->toString() === '25/1143');
```

Use `normalize()` when you want to substitute unit definitions without changing the quantity value.

```php
<?php

require 'vendor/autoload.php';

use jbboehr\IudexMensurarumMysteriorum\Units;

$units = Units::default();

$rate = $units->quantity(2, 'centimeter / second')->normalize();

assert($rate->valueToString() === '2');
assert($rate->unitToString() === '1/100 * meter / second');
assert($rate->toString() === '1/50 * meter / second');
assert($rate->valueIn('meter / second')->toString() === '1/50');
```

Use `simplify()` when you want to substitute unit definitions and fold the unit scale factor into the value.

```php
<?php

require 'vendor/autoload.php';

use jbboehr\IudexMensurarumMysteriorum\Units;

$units = Units::default();

$rate = $units->quantity(2, 'centimeter / second')->simplify();

assert($rate->valueToString() === '1/50');
assert($rate->unitToString() === 'meter / second');
assert($rate->toString() === '1/50 * meter / second');
assert($rate->valueIn('centimeter / second')->toString() === '2');
```

## License

This project is licensed under the [AGPL v3+](https://www.gnu.org/licenses/agpl-3.0) License - see
[LICENSE.md](LICENSE.md) for details.
