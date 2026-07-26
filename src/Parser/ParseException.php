<?php

namespace jbboehr\Yumemi\Parser;

final class ParseException extends \Exception
{
    public function __construct(
        string $message = "",
        int $code = 0,
        public readonly ?Location $location = null,
    ) {
        parent::__construct($message, $code);
    }

    public function getLocation(): ?Location
    {
        return $this->location;
    }
}
