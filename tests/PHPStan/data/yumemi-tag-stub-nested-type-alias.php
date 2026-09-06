<?php

namespace YumemiStubFixture;

use function jbboehr\Yumemi\unit;

acceptsMeterPair([
    'first' => unit(1.0, 'meter'),
    'second' => unit(2.0, 'meter'),
]);
acceptsMeterPair([
    'first' => unit(1.0, 'meter'),
    'second' => unit(2.0, 'second'),
]);
