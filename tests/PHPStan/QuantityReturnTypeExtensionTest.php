<?php

namespace jbboehr\Yumemi\Tests\PHPStan;

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Type-inference coverage for the Quantity<'...'> object path via assertType() fixtures.
 */
final class QuantityReturnTypeExtensionTest extends TypeInferenceTestCase
{
    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../../extension.neon',
        ];
    }

    /**
     * @return iterable<mixed>
     */
    public static function dataFileAsserts(): iterable
    {
        yield from self::gatherAssertTypes(__DIR__ . '/data/quantity-assert.php');
    }

    /**
     * @param mixed ...$args
     */
    #[DataProvider('dataFileAsserts')]
    public function testFileAsserts(string $assertType, string $file, ...$args): void
    {
        $this->assertFileAsserts($assertType, $file, ...$args);
    }
}
