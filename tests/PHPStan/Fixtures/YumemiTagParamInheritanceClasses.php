<?php

namespace jbboehr\Yumemi\Tests\PHPStan\Fixtures;

/**
 * Fixture types for @yumemi-param inheritance: an annotated base method and interface, each with a
 * doc-less override/implementation that inherits the tag. `require`d into the test process (native
 * reflection) so the analyser resolves them without an autoload-misconfiguration warning.
 */
class InhParent
{
    /**
     * @param int $length
     *
     * @yumemi-param unit_int<'meter'> $length
     */
    public function expectMeters(int $length): int
    {
        return $length;
    }
}

class InhChild extends InhParent
{
    // Overrides without its own doc comment; inherits @yumemi-param from InhParent.
    public function expectMeters(int $length): int
    {
        return $length * 2;
    }
}

interface InhHasMeters
{
    /**
     * @param int $length
     *
     * @yumemi-param unit_int<'meter'> $length
     */
    public function expectMeters(int $length): int;
}

final class InhImpl implements InhHasMeters
{
    // Implements without its own doc comment; inherits @yumemi-param from InhHasMeters.
    public function expectMeters(int $length): int
    {
        return $length;
    }
}
