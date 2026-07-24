<?php

namespace jbboehr\IudexMensurarumMysteriorum\Tests\Analyzer;

use jbboehr\IudexMensurarumMysteriorum\Analyzer\UnitResolver;
use jbboehr\IudexMensurarumMysteriorum\Registry\Udunits2UnitRegistry;
use PHPUnit\Framework\TestCase;

final class UnitResolverTest extends TestCase
{
    public function testResolvesUdunits2Aliases(): void
    {
        $resolver = new UnitResolver(new Udunits2UnitRegistry());

        $this->assertSame('meter', $resolver->resolveOrFail('m')->toString());
    }

    public function testResolvesUdunits2Prefixes(): void
    {
        $resolver = new UnitResolver(new Udunits2UnitRegistry());

        $this->assertSame('1000 * meter', $resolver->resolveOrFail('kilometer')->toString());
        $this->assertSame('1/100 * meter', $resolver->resolveOrFail('centimeter')->toString());
    }

    public function testResolvesSimplePlurals(): void
    {
        $resolver = new UnitResolver(new Udunits2UnitRegistry());

        $this->assertSame('meter', $resolver->resolveOrFail('meters')->toString());
    }
}
