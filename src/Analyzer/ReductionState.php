<?php

namespace jbboehr\IudexMensurarumMysteriorum\Analyzer;

use jbboehr\IudexMensurarumMysteriorum\Number\Rational;

final class ReductionState
{
    public Rational $constant;

    /** @var array<string, array{unit: \jbboehr\IudexMensurarumMysteriorum\Expr\Unit, power: int}> */
    public array $units = [];

    public function __construct()
    {
        $this->constant = new Rational(1);
    }
}
