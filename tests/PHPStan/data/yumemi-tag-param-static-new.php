<?php

namespace jbboehr\Yumemi\Tests\PHPStan\Fixtures;

use function jbboehr\Yumemi\unit;

// Wrong-unit branded argument (international_foot vs meter) via a static call and a constructor.
YumemiTagParamStaticNew::staticMeters(unit(3, 'foot'));
new YumemiTagParamStaticNew(unit(3, 'foot'));

// Matching unit and bare native → accepted.
YumemiTagParamStaticNew::staticMeters(unit(3, 'meter'));
new YumemiTagParamStaticNew(3);
