<?php

namespace jbboehr\IudexMensurarumMysteriorum\PHPStan;

/**
 * Result of parsing a unit string for static analysis.
 */
final class UnitExpressionParseResult
{
    private function __construct(
        private readonly ?UnitExpression $expression,
        private readonly ?string $errorMessage,
    ) {
    }

    public static function ok(UnitExpression $expression): self
    {
        return new self($expression, null);
    }

    public static function invalid(string $message): self
    {
        return new self(null, $message);
    }

    public function isOk(): bool
    {
        return $this->expression !== null;
    }

    public function expression(): UnitExpression
    {
        if ($this->expression === null) {
            throw new \LogicException('Parse result is an error: ' . ($this->errorMessage ?? ''));
        }

        return $this->expression;
    }

    public function errorMessage(): ?string
    {
        return $this->errorMessage;
    }
}
