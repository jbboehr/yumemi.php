<?php

namespace jbboehr\Yumemi\Tests\PHPStan\Fixtures;

use function jbboehr\Yumemi\unit;

// Both receivers pass a wrong-unit branded argument (international_foot vs meter). The fast-path
// guard must not skip these: the tag is inherited from an ancestor, not in the method's own comment.
// The inherited types live in Fixtures/YumemiTagParamInheritanceClasses.php (required by the test).
(new InhChild())->expectMeters(unit(3, 'foot'));
(new InhImpl())->expectMeters(unit(3, 'foot'));
