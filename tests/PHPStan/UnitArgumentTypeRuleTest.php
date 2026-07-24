<?php

namespace jbboehr\IudexMensurarumMysteriorum\Tests\PHPStan;

use PHPStan\Rules\Methods\CallMethodsRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * PHPStan RuleTestCase-style check: core method call argument rule + IMM unit accepts().
 *
 * Kept separate from the CLI integration smoke tests; this asserts exact message + line.
 *
 * @extends RuleTestCase<CallMethodsRule>
 */
final class UnitArgumentTypeRuleTest extends RuleTestCase
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

    public function testFootIsNotAssignableToMeterParameter(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/UnitFootNotMeterCase.php'], [
            [
                'Parameter #1 $length of method jbboehr\IudexMensurarumMysteriorum\Tests\PHPStan\Fixtures\UnitFootNotMeterCase::expectMetersOnly() expects unit_float<\'meter\'>, unit_float<\'international_foot\'> given.',
                23,
                'Unit unit_float<\'international_foot\'> is not assignable to unit_float<meter> (normalized forms differ).',
            ],
        ]);
    }
}
