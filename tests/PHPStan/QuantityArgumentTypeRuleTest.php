<?php

namespace jbboehr\Yumemi\Tests\PHPStan;

use PHPStan\Rules\Methods\CallMethodsRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * Assignment/parameter unit checking for Quantity<'...'> comes free via QuantityType::accepts()
 * plus PHPStan's core method-call rule — no dedicated rule needed.
 *
 * @extends RuleTestCase<CallMethodsRule>
 */
final class QuantityArgumentTypeRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        // Core rule under test; not part of PHPStan's public API.
        return self::getContainer()->getByType(CallMethodsRule::class); // @phpstan-ignore phpstanApi.classConstant
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../../extension.neon',
        ];
    }

    public function testFootQuantityIsNotAssignableToMeterParameter(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/QuantityUnitMismatch.php'], [
            [
                'Parameter #1 $length of method jbboehr\Yumemi\Tests\PHPStan\Fixtures\QuantityUnitMismatch::expectMeters() expects Quantity<\'meter\'>, Quantity<\'international_foot\'> given.',
                21,
                'Unit Quantity<\'international_foot\'> is not assignable to Quantity<\'meter\'> (normalized forms differ).',
            ],
        ]);
    }
}
