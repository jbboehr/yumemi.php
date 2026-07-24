<?php

namespace jbboehr\IudexMensurarumMysteriorum\Tests\PHPStan;

use PHPStan\Rules\Methods\CallMethodsRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * End-to-end: unit() construction feeds sinks with correct unit types.
 *
 * @extends RuleTestCase<CallMethodsRule>
 */
final class UnitConstructionRuleTest extends RuleTestCase
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

    public function testUnitConstructionAcceptedAsNewtonForce(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/UnitConstructionValid.php'], []);
    }

    public function testUnitConstructionFootNotAcceptedAsMeter(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/UnitConstructionScaleMismatch.php'], [
            [
                'Parameter #1 $length of method jbboehr\IudexMensurarumMysteriorum\Tests\PHPStan\Fixtures\UnitConstructionScaleMismatch::expectMeters() expects unit_float<\'meter\'>, unit_float<\'international_foot\'> given.',
                19,
                'Unit unit_float<\'international_foot\'> is not assignable to unit_float<meter> (normalized forms differ).',
            ],
        ]);
    }

    public function testUnitToFootToMeterAcceptedAsMeter(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/UnitToConversionCase.php'], []);
    }
}
