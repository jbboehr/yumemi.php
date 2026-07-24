<?php

namespace jbboehr\IudexMensurarumMysteriorum\Exception;

final class UnitNotFoundException extends \RuntimeException
{
    public readonly string $unitName;

    /** @var list<string> */
    public readonly array $suggestions;

    /**
     * @param list<string> $suggestions
     */
    public function __construct(string $message, string $unitName, array $suggestions = [])
    {
        parent::__construct($message);
        $this->unitName = $unitName;
        $this->suggestions = $suggestions;
    }

    /**
     * @param list<string> $suggestions
     */
    public static function create(string $name, array $suggestions = []): self
    {
        $message = sprintf('Unit not found: %s.', $name);

        if ($suggestions !== []) {
            $message .= ' Did you mean: ' . implode(', ', $suggestions) . '?';
        }

        return new self($message, $name, $suggestions);
    }
}
