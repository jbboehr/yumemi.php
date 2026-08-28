<?php

namespace jbboehr\Yumemi\Parser;

/** @internal */
final class NativeParser
{
    public const ABI_VERSION = 1;

    public static function isCompatible(): bool
    {
        throw new \LogicException('Analysis-only ext-yumemi stub.');
    }

    /** @return array<string, mixed> */
    public static function parse(string $input): array
    {
        throw new \LogicException('Analysis-only ext-yumemi stub.');
    }
}

/** @internal */
final class NativeParseException extends \RuntimeException
{
    public readonly string $input;
    public readonly int $start;
    public readonly int $end;
    public readonly ?string $unexpected;

    /** @var list<string> */
    public readonly array $expected;
}

/** @internal */
final class NativeLimitException extends \LengthException
{
    public readonly string $limit;
    public readonly int $maximum;
    public readonly int $observed;
    public readonly ?int $start;
    public readonly ?int $end;
}
