<?php

namespace jbboehr\Yumemi\Analyzer;

use jbboehr\Yumemi\Number\Rational;

final class ReductionState
{
    public Rational $constant;

    /** @var array<string, array{unit: \jbboehr\Yumemi\Expr\Unit, power: int}> */
    public array $units = [];

    public function __construct()
    {
        $this->constant = new Rational(1);
    }
}
