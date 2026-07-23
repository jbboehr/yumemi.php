<?php

namespace jbboehr\IudexMensurarumMysteriorum\Analyzer;

use jbboehr\IudexMensurarumMysteriorum\Exception\UnsupportedSyntaxException;
use jbboehr\IudexMensurarumMysteriorum\Expr;
use jbboehr\IudexMensurarumMysteriorum\Expr\Compound;
use jbboehr\IudexMensurarumMysteriorum\Expr\Constant;
use jbboehr\IudexMensurarumMysteriorum\Expr\Term;
use jbboehr\IudexMensurarumMysteriorum\Number\Rational;
use jbboehr\IudexMensurarumMysteriorum\Parser\Ast;
use jbboehr\IudexMensurarumMysteriorum\Parser\Ast\Add;
use jbboehr\IudexMensurarumMysteriorum\Parser\Ast\At;
use jbboehr\IudexMensurarumMysteriorum\Parser\Ast\Div;
use jbboehr\IudexMensurarumMysteriorum\Parser\Ast\Float_;
use jbboehr\IudexMensurarumMysteriorum\Parser\Ast\Identifier;
use jbboehr\IudexMensurarumMysteriorum\Parser\Ast\Integer_;
use jbboehr\IudexMensurarumMysteriorum\Parser\Ast\Mul;
use jbboehr\IudexMensurarumMysteriorum\Parser\Ast\Pow;
use jbboehr\IudexMensurarumMysteriorum\Parser\Ast\Sub;
use jbboehr\IudexMensurarumMysteriorum\Registry\UnitRegistry;

final class AstConverter
{
    public function __construct(
        private readonly UnitRegistry $unitRegistry,
    ) {
    }

    public function convert(Ast $ast): Expr
    {
        return match ($ast::class) {
            Div::class => new Compound([
                $this->convert($ast->left),
                new Term($this->convert($ast->right), -1),
            ]),
            Float_::class => new Constant(Rational::fromDecimalString($ast->value)),
            Identifier::class => $this->unitRegistry->get($ast->identifier),
            Integer_::class => new Constant(gmp_init($ast->value)),
            Mul::class => new Compound([
                $this->convert($ast->left),
                $this->convert($ast->right),
            ]),
            Pow::class => $this->convertPower($ast),
            Add::class,
            At::class,
            Sub::class => throw UnsupportedSyntaxException::create($ast),
            default => throw new \LogicException('Unknown parser AST node: ' . $ast::class),
        };
    }

    private function convertPower(Pow $ast): Expr
    {
        if (!$ast->right instanceof Integer_) {
            throw UnsupportedSyntaxException::create($ast);
        }

        return new Term($this->convert($ast->left), (int) $ast->right->value);
    }
}
