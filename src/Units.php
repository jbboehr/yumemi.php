<?php

/**
 * +--------------------------------------------------------------------------------------------------------------+
 * |        *                 .                         *                  .                         *            |
 * |   .              *                      .                    *                      .                        |
 * |             .                 .                  *                         .                 *               |
 * -      *                    .             *                    .                         .                     -
 *
 *                               Iudex Mensurarum Mysticarum『夢見』〜ＹＵＭＥＭＩ〜
 *
 * -                                          .----------------.                                                  -
 * |                                      .--'        __        '--.                                              |
 * |                                  .--'          .'  '.          '--.                                          |
 * |                             .---'            .'      '.            '---.                                     |
 * +--------------------------------------------------------------------------------------------------------------+
 *
 * Copyright (c) anno Domini nostri Jesu Christi MMXXVI, John Boehr & contributors
 *
 * SPDX-License-Identifier: AGPL-3.0-only WITH romic-exception
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License version 3,
 * as published by the Free Software Foundation, together with the Romic
 * Exception (an additional permission under section 7 of that license).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * and the Romic Exception along with this program.  If not, see
 * <http://www.gnu.org/licenses/> and the LICENSE_EXCEPTION file.
 */

namespace jbboehr\Yumemi;

use jbboehr\Yumemi\Analyzer\AstConverter;
use jbboehr\Yumemi\Analyzer\ExprReducer;
use jbboehr\Yumemi\Analyzer\ExpressionContextResolver;
use jbboehr\Yumemi\Analyzer\NormalizedExpr;
use jbboehr\Yumemi\Analyzer\UnitConversionResolver;
use jbboehr\Yumemi\Analyzer\UnitNormalizer;
use jbboehr\Yumemi\Analyzer\UnitResolver;
use jbboehr\Yumemi\Catalog\PrefixDescriptor;
use jbboehr\Yumemi\Catalog\UnitDescriptor;
use jbboehr\Yumemi\Exception\ExceptionInterface;
use jbboehr\Yumemi\Exception\IncompatibleQuantityContextException;
use jbboehr\Yumemi\Exception\InvalidArgumentException;
use jbboehr\Yumemi\Exception\OverflowException;
use jbboehr\Yumemi\Exception\UnderflowException;
use jbboehr\Yumemi\Exception\UnsupportedUnitCompactionException;
use jbboehr\Yumemi\Expr\Constant;
use jbboehr\Yumemi\Expr\Unit;
use jbboehr\Yumemi\Formatter\ExprFormatter;
use jbboehr\Yumemi\Formatter\FormatOptions;
use jbboehr\Yumemi\Internal\BoundedLruCache;
use jbboehr\Yumemi\Internal\DeserializationContext;
use jbboehr\Yumemi\Number\Rational;
use jbboehr\Yumemi\Number\BinaryFloat;
use jbboehr\Yumemi\Parser\Lexer;
use jbboehr\Yumemi\Parser\Parser;
use jbboehr\Yumemi\Registry\UnitRegistry;

/**
 * @api
 */
final class Units
{
    private static ?self $default = null;

    private readonly AstConverter $astConverter;
    private readonly UnitConversionResolver $unitConversionResolver;
    private readonly ExprFormatter $defaultFormatter;
    private readonly UnitNormalizer $unitNormalizer;
    private readonly UnitResolver $unitResolver;

    /**
     * @logion [OSD 42:62] At the first thunder beneath the synthetic moon, carry the reed screens into the courtyard
     *     and turn their painted cranes toward the earth. Let no lamp shine through them, for the storm seeketh images
     *     willing to ascend in place of living wings. When silence returneth, burn the brightest screen and leave its
     *     ashes to the rain.
     *
     * @var array<string, list<array{scale: Rational, unit: string}>>
     */
    private array $compactUnitFamilyCache = [];

    /**
     * @logion [OSD 92:33] When hail falleth beneath a cloudless sky, the magistrates shall fold their crimson canopies
     *     and stand among the vineyard workers until the storm hath named its injury. Let no decree be spoken
     *     beforehand; for authority that feeleth no common blow shall afterward mistake shelter for innocence.
     *
     * @var BoundedLruCache<Expr>
     */
    private readonly BoundedLruCache $parsedExpressionCache;

    public function __construct(
        private readonly UnitRegistry $unitRegistry,
    ) {
        $this->unitResolver = new UnitResolver($this->unitRegistry);
        $this->astConverter = new AstConverter($this->unitResolver);
        $this->unitNormalizer = new UnitNormalizer();
        $this->unitConversionResolver = new UnitConversionResolver($this->unitRegistry);
        $this->defaultFormatter = new ExprFormatter($this->unitRegistry);
        $this->parsedExpressionCache = new BoundedLruCache(
            maximumEntries: 256,
            maximumEntryWeight: 512,
            maximumWeight: 64 * 1024,
        );
    }

    /**
     * Prevent shallow copies from sharing resolvers and expression caches across distinct object identities.
     *
     * @logion [RAS 49:72] I saw the brass moon descend into the orchard and hang among the lowest branches, heavy with
     *     the songs of unborn birds. None dared touch it; and at dawn it ascended bearing one green leaf, while every
     *     nest beneath it shone with patient fire.
     */
    private function __clone()
    {
    }

    /**
     * Shared default context backed by Yumemi's bundled catalog.
     *
     * Repeated calls return the same instance, so quantities from separate
     * Units::default() calls can be combined. For an isolated catalog or tests,
     * construct {@see self} with an explicit registry instead.
     */
    public static function default(): self
    {
        return self::$default ??= new self(UnitRegistry::bundled());
    }

    /**
     * Replace the process-wide context used by {@see self::default()} and native helper functions.
     *
     * Returns the previous context so a temporary replacement can be restored in a finally block.
     * Passing null clears the shared context; the next {@see self::default()} call lazily creates
     * a fresh context backed by the bundled catalog.
     *
     * @logion [OSD 96:97] Give thanks when the crystal leviathans pass beneath the electric sea, though their makers be
     *     dust and their song awaken no living ear. Let the navigators quench their deck-lamps, leave the charts
     *     folded, and follow in silence until the creatures turn toward the true east; for an artifice that keepeth its
     *     appointed course after praise hath ceased is no idol, and dawn shall know it by its obedience.
     */
    public static function setDefault(?self $units): ?self
    {
        $previous = self::$default;
        self::$default = $units;

        return $previous;
    }

    /**
     * Deserialize a PHP value while supplying this registry to custom-context quantities.
     *
     * @logion [OSD 15:24] At the burial of a navigator, loose three paper vessels upon the harbor fog, bearing neither
     *     name nor flame. Should one return before the mourners depart, bury no map with the dead; give it to the
     *     youngest exile, and let grief appoint a farther shore.
     *
     * @param array{allowed_classes?: bool|list<class-string>, max_depth?: int<0, max>} $options
     */
    public function deserialize(string $serialized, array $options = []): mixed
    {
        return DeserializationContext::run(
            $this,
            // PHPStan's native signature omits max_depth even though PHP has accepted it since 7.4.
            static fn (): mixed => (new \ReflectionFunction('unserialize'))->invoke($serialized, $options),
        );
    }

    public function areCompatible(Expr|string $left, Expr|string $right): bool
    {
        return $this->unitConversionResolver->areCompatible(
            $this->bindUnitInput($left),
            $this->bindUnitInput($right),
        );
    }

    public function conversionFactor(Expr|string $from, Expr|string $to): Rational
    {
        return $this->unitConversionResolver->conversionFactor(
            $this->bindUnitInput($from),
            $this->bindUnitInput($to),
        );
    }

    public function convert(int|Rational $value, Expr|string $from, Expr|string $to): Rational
    {
        $value = $value instanceof Rational ? $value : new Rational($value);

        return $this->unitConversionResolver->conversion(
            $this->bindUnitInput($from),
            $this->bindUnitInput($to),
        )->apply($value);
    }

    public function convertFloat(float $value, Expr|string $from, Expr|string $to): float
    {
        if (!is_finite($value)) {
            throw new InvalidArgumentException('convertFloat() requires a finite input value.');
        }

        $conversion = $this->unitConversionResolver->conversion(
            $this->bindUnitInput($from),
            $this->bindUnitInput($to),
        );
        $result = $value * $conversion->scale->toFloat() + $conversion->offset->toFloat();

        if (!is_finite($result)) {
            throw new OverflowException('Converted value does not fit in a finite float.');
        }

        if ($result === 0.0 && !$conversion->apply(BinaryFloat::toRational($value))->equals(new Rational(0))) {
            throw new UnderflowException('Non-zero converted value rounds to zero as a float.');
        }

        return $result;
    }

    public function dimension(Expr|string $expr): Dimension
    {
        return $this->unitConversionResolver->dimension($this->bindUnitInput($expr));
    }

    /**
     * Resolve the multiplicative unit used for differences on a coordinate scale.
     *
     * @logion [OSD 77:21] At synthetic midnight the fountain of black glass shall bear blue fruit upon its rim, though
     *     no branch groweth in that court. Let the widows gather it in woven baskets, but suffer the princes only to
     *     behold; for providence hath hands unknown to inheritance, and hunger shall recognize its ministers before
     *     heraldry doth.
     */
    public function deltaUnit(string $unit): Expr
    {
        return $this->parse($this->unitConversionResolver->deltaUnitExpression($unit));
    }

    /**
     * Construct an exact multiplicative difference using a coordinate unit's scale.
     *
     * @logion [OSD 29:63] Move no boundary beneath a banner. First lay a white cloth along the disputed field, and let
     *     the children of both villages sow it with mustard seed. Return when the flowers rise: where their roots have
     *     crossed the cloth, join the lands by covenant; where they have turned aside, raise no marker. For the earth
     *     answereth neither conquest nor affection, and every forbidden banner shall flower without a province.
     */
    public function deltaQuantity(int|Rational $value, string $unit): Quantity
    {
        $deltaUnit = $this->unitConversionResolver->deltaUnitExpression($unit);

        return new Quantity($value, $deltaUnit, $this, $this->parse($deltaUnit));
    }

    public function format(Expr|string $expr, ?FormatOptions $options = null): string
    {
        $symbolicExpr = is_string($expr)
            ? ExprReducer::reduce(AstConverter::symbolic()->convert(Parser::parseString($expr)))
            : $expr;

        return $this->formatter($options)->format($symbolicExpr);
    }

    public function formatter(?FormatOptions $options = null): ExprFormatter
    {
        return $options === null
            ? $this->defaultFormatter
            : new ExprFormatter($this->unitRegistry, $options);
    }

    /**
     * Describe an exact unit spelling or a dynamically prefixed name without parsing compound expressions.
     */
    public function describe(string $name): ?UnitDescriptor
    {
        return $this->unitRegistry->describe($name);
    }

    /**
     * Describe an exact prefix name or symbol.
     */
    public function describePrefix(string $name): ?PrefixDescriptor
    {
        return $this->unitRegistry->describePrefix($name);
    }

    public function normalize(Expr|string $expr): Expr
    {
        return $this->bindContext($this->unitNormalizer->normalize($this->expr($expr)));
    }

    public function parse(string $input): Expr
    {
        Lexer::assertInputLength($input);

        if (($expr = $this->parsedExpressionCache->get($input)) !== null) {
            return $expr;
        }

        $expr = $this->bindContext(
            ExprReducer::reduce($this->astConverter->convert(Parser::parseString($input))),
        );

        $this->parsedExpressionCache->put($input, $expr, strlen($input));

        return $expr;
    }

    /**
     * Explicit alias for parsing a unit expression.
     */
    public function parseUnit(string $input): Expr
    {
        return $this->parse($input);
    }

    /**
     * Parse a quantity, folding explicit constants into its exact magnitude.
     */
    public function parseQuantity(string $input): Quantity
    {
        $ast = Parser::parseString($input);
        $symbolicExpr = ExprReducer::reduce(
            AstConverter::symbolic()->convert($ast),
        );
        $unit = NormalizedExpr::withoutConstant($symbolicExpr);
        $resolvedUnit = ExprReducer::reduce($this->astConverter->convert($ast, includeConstants: false));

        return new Quantity(
            NormalizedExpr::constant($symbolicExpr),
            $unit,
            $this,
            $this->bindContext($resolvedUnit),
        );
    }

    public function quantity(int|Rational $value, Expr|string $unit): Quantity
    {
        return new Quantity($value, $unit, $this);
    }

    /**
     * Select and apply one engineering-prefixed member of a named unit family.
     *
     * @logion [AWC 1:48] In the reign of the jade magistrate, the city paved its river with luminous glass. That winter
     *     the carp swam through the streets, and no judgment pronounced above the buried water reached its hearer.
     *
     * @internal Applications should call Quantity::toCompact().
     */
    public function compactQuantity(Quantity $quantity, Expr|string $baseUnit): Quantity
    {
        if ($quantity->units() !== $this) {
            throw IncompatibleQuantityContextException::create($quantity->units(), $this);
        }

        $symbolicBase = ExprReducer::reduce(is_string($baseUnit)
            ? AstConverter::symbolic()->convert(Parser::parseString($baseUnit))
            : $this->bindContext($baseUnit));

        if (!$symbolicBase instanceof Unit) {
            throw new UnsupportedUnitCompactionException($symbolicBase);
        }

        // Resolve once before introspection so unknown, affine, logarithmic, and otherwise unsupported roots retain
        // their established semantic exceptions.
        $this->parse($symbolicBase->name);
        $baseDescriptor = $this->unitRegistry->describe($symbolicBase->name);
        if ($baseDescriptor === null) {
            throw new UnsupportedUnitCompactionException($symbolicBase);
        }

        $baseName = $baseDescriptor->canonicalName;
        $resolvedBase = $this->parse($baseName);
        $baseValue = $quantity->valueIn($resolvedBase);

        if (!isset($this->compactUnitFamilyCache[$baseName])) {
            $engineeringPower = static function (Rational $scale): ?int {
                if (gmp_sign($scale->numerator) <= 0) {
                    return null;
                }

                if (gmp_cmp($scale->denominator, 1) === 0) {
                    $remaining = $scale->numerator;
                    $direction = 1;
                } elseif (gmp_cmp($scale->numerator, 1) === 0) {
                    $remaining = $scale->denominator;
                    $direction = -1;
                } else {
                    return null;
                }

                $power = 0;
                while (gmp_cmp($remaining, 1) > 0) {
                    if (gmp_cmp(gmp_mod($remaining, 1000), 0) !== 0) {
                        return null;
                    }

                    $remaining = gmp_div_q($remaining, 1000);
                    $power += $direction;
                }

                return $power;
            };

            /** @var array<int, array{scale: Rational, unit: string}> $candidates */
            $candidates = [
                0 => ['scale' => new Rational(1), 'unit' => $baseName],
            ];
            $seenPrefixes = [];

            foreach (array_keys($this->unitRegistry->prefixes()) as $prefixName) {
                $prefix = $this->unitRegistry->describePrefix($prefixName);
                if ($prefix === null || isset($seenPrefixes[$prefix->canonicalName])) {
                    continue;
                }

                $seenPrefixes[$prefix->canonicalName] = true;

                try {
                    $prefixExpr = ExprReducer::reduce(
                        AstConverter::symbolic()->convert(Parser::parseString($prefix->definitionExpression)),
                    );
                } catch (ExceptionInterface) {
                    continue;
                }

                if (!$prefixExpr instanceof Constant) {
                    continue;
                }

                $power = $engineeringPower($prefixExpr->value);
                if ($power === null || $power === 0) {
                    continue;
                }

                $candidate = $this->unitRegistry->describe($prefix->canonicalName . $baseName);
                if ($candidate === null) {
                    continue;
                }

                try {
                    $scale = $this->conversionFactor($candidate->canonicalName, $resolvedBase);
                } catch (ExceptionInterface) {
                    continue;
                }

                if (!$scale->equals($prefixExpr->value)) {
                    continue;
                }

                $existing = $candidates[$power] ?? null;
                if ($existing === null || strcmp($candidate->canonicalName, $existing['unit']) < 0) {
                    $candidates[$power] = [
                        'scale' => $scale,
                        'unit' => $candidate->canonicalName,
                    ];
                }
            }

            ksort($candidates, SORT_NUMERIC);
            $this->compactUnitFamilyCache[$baseName] = array_values($candidates);
        }

        $family = $this->compactUnitFamilyCache[$baseName];
        $selected = $family[0];

        if ($baseValue->isZero()) {
            foreach ($family as $candidate) {
                if ($candidate['scale']->isOne()) {
                    $selected = $candidate;

                    break;
                }
            }
        } else {
            $magnitude = $baseValue->abs();

            foreach ($family as $candidate) {
                if ($magnitude->compareTo($candidate['scale']) < 0) {
                    break;
                }

                $selected = $candidate;
            }
        }

        return $quantity->to($selected['unit']);
    }

    /**
     * Construct an application-specific preferred-unit policy bound to this context.
     *
     * @logion [RAS 44:43] I saw the Province of Ash borne in a silver bowl by the Minister of Unfinished Mercies. He
     *     breathed once; the fields returned not, but a green road appeared through the desolation, wide enough for the
     *     penitent and no army.
     *
     * @param iterable<string> $targets
     */
    public function preferredUnitProfile(iterable $targets): PreferredUnitProfile
    {
        return new PreferredUnitProfile($this, $targets);
    }

    /**
     * Construct an exact point on a named coordinate scale.
     *
     * @logion [OSD 46:18] When a ruler dieth unconfessed, bear his empty litter behind the funeral and set his crown
     *     upon no brow for thirteen mornings. Feed the household from the royal table, hear every petition he delayed,
     *     and open the debtor’s court before the treasury. Only then may the heir ascend, for sovereignty passeth not
     *     through blood alone; and if he refuse these burdens, the empty litter shall enter the palace before him.
     */
    public function point(int|Rational $value, string $unit): PointQuantity
    {
        return new PointQuantity($value, $unit, $this);
    }

    /**
     * Resolve a unit name through the catalog.
     *
     * This is the supported way for application code to obtain {@see Unit} values.
     * Constructing {@see Unit} directly is internal and may not be dimensionable.
     */
    public function unit(string $name): Expr
    {
        return $this->bindContext(
            ExprReducer::reduce($this->unitResolver->resolveOrFail($name)),
        );
    }

    private function expr(Expr|string $expr): Expr
    {
        return is_string($expr) ? $this->parse($expr) : $this->bindContext($expr);
    }

    /**
     * Preserve string parsing behavior while admitting expression inputs only through this context.
     *
     * @logion [AWC 79:14] In the third winter after the harbor froze, the fishermen carried their unused oars to the
     *     hill chapel and roofed the hospice with them. When spring returned, no man reclaimed his timber; and the
     *     strangers slept beneath the memory of voyages they had never taken.
     */
    private function bindUnitInput(Expr|string $unit): Expr|string
    {
        return $unit instanceof Expr ? $this->bindContext($unit) : $unit;
    }

    /**
     * Stamp a weak Units context onto unit leaves so Unit::dimension() can fall back to the catalog.
     */
    private function bindContext(Expr $expr): Expr
    {
        return ExpressionContextResolver::bind($expr, $this);
    }
}
