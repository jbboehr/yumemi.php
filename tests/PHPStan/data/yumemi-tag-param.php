<?php

namespace jbboehr\Yumemi\Tests\PHPStan\Fixtures;

use function jbboehr\Yumemi\unit;

$obj = new YumemiTagParamClass();

// Branded argument with the wrong unit → reported (international_foot vs meter).
expectsMeters(unit(3, 'foot'));
$obj->expectMeters(unit(3, 'foot'));

// Branded argument with the matching unit → accepted.
expectsMeters(unit(3, 'meter'));
$obj->expectMeters(unit(3, 'meter'));

// Bare native argument → graceful escape hatch, not checked.
expectsMeters(3);
$obj->expectMeters(3);

// Named argument, wrong unit → reported.
expectsMeters(length: unit(3, 'foot'));
