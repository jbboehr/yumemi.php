<?php

namespace jbboehr\IudexMensurarumMysteriorum\Tests\Registry;

use jbboehr\IudexMensurarumMysteriorum\Analyzer\UnitResolver;
use jbboehr\IudexMensurarumMysteriorum\Exception\UnsupportedSyntaxException;
use jbboehr\IudexMensurarumMysteriorum\Registry\Udunits2UnitRegistry;
use jbboehr\IudexMensurarumMysteriorum\Units;
use PHPUnit\Framework\TestCase;
use Throwable;

/**
 * @phpstan-import-type Udunits2Catalog from \jbboehr\IudexMensurarumMysteriorum\Registry\Udunits2UnitRegistry
 */
final class Udunits2CatalogSmokeTest extends TestCase
{
    private const EXPECTED_UNSUPPORTED_BY_REASON = [
        'affine' => [
            '°C',
            'degree_Celsius',
            'celsius',
            'degree_C',
            'degrees_C',
            'degreeC',
            'degreesC',
            'deg_C',
            'degs_C',
            'degC',
            'degsC',
            '℃',
            '℉',
            'fahrenheit',
            'degree_fahrenheit',
            'degrees_fahrenheit',
            'degreeF',
            'degreesF',
            'degree_F',
            'degrees_F',
            'degF',
            'degsF',
            'deg_F',
            'degs_F',
            '°F',
        ],
        'logarithmic' => [],
    ];

    public function testLooksUpEverySupportedUdunits2CatalogEntry(): void
    {
        $catalog = self::catalog();
        $registry = new Udunits2UnitRegistry();
        $resolver = new UnitResolver($registry);
        $failures = [];
        $lookedUpCount = 0;

        foreach ($catalog['units'] as $name => $unit) {
            if ($this->unsupportedReason($catalog, $name) !== null) {
                continue;
            }

            try {
                if ($registry->record($name) === null) {
                    $failures[] = $name . ' missing catalog record';
                    continue;
                }

                if ($resolver->resolve($name) === null) {
                    $failures[] = $name . ' failed to resolve';
                    continue;
                }

                $lookedUpCount++;
            } catch (Throwable $exception) {
                $failures[] = sprintf('%s: %s: %s', $name, $exception::class, $exception->getMessage());
            }
        }

        $this->assertSame([], $failures);
        $this->assertGreaterThan(500, $lookedUpCount);
    }

    public function testSupportedUdunits2AliasesResolveToTheirTargets(): void
    {
        $catalog = self::catalog();
        $resolver = new UnitResolver(new Udunits2UnitRegistry());
        $failures = [];
        $aliasCount = 0;

        foreach ($catalog['units'] as $name => $unit) {
            if ($unit['type'] !== 'alias' || $this->unsupportedReason($catalog, $name) !== null) {
                continue;
            }

            try {
                $alias = $resolver->resolveOrFail($name);
                $target = $resolver->resolveOrFail($unit['def']);

                if ($alias->toString() !== $target->toString()) {
                    $failures[] = sprintf(
                        '%s resolved to %s instead of %s',
                        $name,
                        $alias->toString(),
                        $target->toString(),
                    );
                    continue;
                }

                $aliasCount++;
            } catch (Throwable $exception) {
                $failures[] = sprintf('%s: %s: %s', $name, $exception::class, $exception->getMessage());
            }
        }

        $this->assertSame([], $failures);
        $this->assertGreaterThan(250, $aliasCount);
    }

    public function testUdunits2PrefixesResolveWithLongestPrefixFirst(): void
    {
        $units = Units::default();

        $this->assertSame('10 * meter', $units->normalize('dekameter')->toString());
        $this->assertSame('10 * meter', $units->normalize('dameter')->toString());
        $this->assertSame('1/1000 * meter', $units->normalize('millimeter')->toString());
        $this->assertSame('1/1000000 * meter', $units->normalize('micrometer')->toString());

        $this->assertSame('10000 * kilogram', $units->normalize('dat')->toString());
    }

    public function testNormalizesEverySupportedUdunits2Definition(): void
    {
        $catalog = self::catalog();
        $units = Units::default();
        $failures = [];
        $normalizedCount = 0;
        $unsupported = [
            'affine' => [],
            'logarithmic' => [],
        ];

        foreach ($catalog['units'] as $name => $unit) {
            if (!isset($unit['def'])) {
                continue;
            }

            $unsupportedReason = $this->unsupportedReason($catalog, $name);
            if ($unsupportedReason !== null) {
                $unsupported[$unsupportedReason][] = $name;
                continue;
            }

            try {
                $units->normalize($name);
                $normalizedCount++;
            } catch (Throwable $exception) {
                $failures[] = sprintf(
                    '%s (%s): %s: %s',
                    $name,
                    $unit['def'],
                    $exception::class,
                    $exception->getMessage(),
                );
            }
        }

        $this->assertSame([], $failures);
        $this->assertSame(526, $normalizedCount);
        $this->assertSame(self::EXPECTED_UNSUPPORTED_BY_REASON, $unsupported);
    }

    public function testKnownUnsupportedUdunits2DefinitionsFailWithUnsupportedSyntax(): void
    {
        $units = Units::default();

        foreach (self::EXPECTED_UNSUPPORTED_BY_REASON['affine'] as $name) {
            try {
                $units->normalize($name);
                self::fail('Expected unsupported syntax for affine UDUNITS2 unit: ' . $name);
            } catch (UnsupportedSyntaxException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    /**
     * @phpstan-param Udunits2Catalog $catalog
     */
    private function unsupportedReason(array $catalog, string $name): ?string
    {
        return $this->unsupportedReasonInner($catalog, $name, []);
    }

    /**
     * @param array<string, true> $seen
     * @phpstan-param Udunits2Catalog $catalog
     */
    private function unsupportedReasonInner(array $catalog, string $name, array $seen): ?string
    {
        if (isset($seen[$name])) {
            return null;
        }

        $seen[$name] = true;
        $unit = $catalog['units'][$name] ?? null;

        if ($unit === null || !isset($unit['def'])) {
            return null;
        }

        if (str_contains($unit['def'], '@')) {
            return 'affine';
        }

        if (str_contains($unit['def'], 'lg(')) {
            return 'logarithmic';
        }

        if (isset($catalog['units'][$unit['def']])) {
            return $this->unsupportedReasonInner($catalog, $unit['def'], $seen);
        }

        return null;
    }

    /**
     * @phpstan-return Udunits2Catalog
     */
    private static function catalog(): array
    {
        $catalog = require __DIR__ . '/../../data/udunits2.php';

        if (!is_array($catalog)) {
            throw new \UnexpectedValueException('UDUNITS2 catalog must return an array.');
        }

        /** @phpstan-var Udunits2Catalog $catalog */
        return $catalog;
    }
}
