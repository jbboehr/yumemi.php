<?php

namespace jbboehr\IudexMensurarumMysteriorum\PHPStan;

use jbboehr\IudexMensurarumMysteriorum\Exception\UnitNotFoundException;
use jbboehr\IudexMensurarumMysteriorum\Exception\UnsupportedSyntaxException;
use jbboehr\IudexMensurarumMysteriorum\Exception\UnsupportedUnitDimensionException;
use jbboehr\IudexMensurarumMysteriorum\Formatter\ExprFormatter;
use jbboehr\IudexMensurarumMysteriorum\Parser\ParseException;
use jbboehr\IudexMensurarumMysteriorum\Units;

/**
 * Parses unit expression strings through the IMM runtime for PHPStan.
 *
 * This is the bridge from static analysis to the shared unit engine. Later pieces
 * (custom types, PHPDoc resolvers) should call this instead of reimplementing parsing.
 */
final class UnitExpressionParser
{
    private readonly Units $units;

    public function __construct(?Units $units = null)
    {
        $this->units = $units ?? Units::default();
    }

    public function parse(string $unitString): UnitExpressionParseResult
    {
        $unitString = trim($unitString);

        if ($unitString === '') {
            return UnitExpressionParseResult::invalid('Unit expression must not be empty.');
        }

        try {
            $expr = $this->units->parse($unitString);
            $dimension = $this->units->dimension($expr);

            return UnitExpressionParseResult::ok(new UnitExpression(
                $expr,
                ExprFormatter::format($expr),
                $dimension,
            ));
        } catch (UnitNotFoundException $exception) {
            return UnitExpressionParseResult::invalid($exception->getMessage());
        } catch (UnsupportedSyntaxException $exception) {
            return UnitExpressionParseResult::invalid($exception->getMessage());
        } catch (UnsupportedUnitDimensionException $exception) {
            return UnitExpressionParseResult::invalid($exception->getMessage());
        } catch (ParseException $exception) {
            $message = $exception->getMessage();
            if ($message === '') {
                $message = 'Invalid unit expression syntax.';
            }

            return UnitExpressionParseResult::invalid($message);
        } catch (\Throwable $exception) {
            return UnitExpressionParseResult::invalid(
                'Failed to parse unit expression: ' . $exception->getMessage(),
            );
        }
    }
}
