<?php

namespace jbboehr\Yumemi\Tests\PHPStan;

use jbboehr\Yumemi\PHPStan\InvalidUnitCallRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * Standalone diagnostics for invalid unit() / unit_to() calls (review finding #2).
 *
 * @extends RuleTestCase<InvalidUnitCallRule>
 */
final class InvalidUnitCallRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(InvalidUnitCallRule::class);
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../../extension.neon',
        ];
    }

    public function testInvalidCallsAreReported(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/InvalidUnitCalls.php'], [
            [
                'Unit not found: not_a_real_unit_xyz.',
                9,
            ],
            [
                'Unit not found: not_a_real_unit_xyz.',
                12,
            ],
            [
                'Unit not found: not_a_real_unit_xyz.',
                15,
            ],
            [
                'Cannot convert with unit_to(): units meter and second are not dimensionally compatible.',
                18,
            ],
            [
                'unit_to() value unit international_foot does not match from unit meter (normalized forms differ).',
                21,
            ],
        ]);
    }
}
