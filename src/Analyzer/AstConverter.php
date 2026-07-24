<?php

namespace jbboehr\IudexMensurarumMysteriorum\Analyzer;

use jbboehr\IudexMensurarumMysteriorum\Exception\UnsupportedSyntaxException;
use jbboehr\IudexMensurarumMysteriorum\Expr;
use jbboehr\IudexMensurarumMysteriorum\Expr\Compound;
use jbboehr\IudexMensurarumMysteriorum\Expr\Constant;
use jbboehr\IudexMensurarumMysteriorum\Expr\Term;
use jbboehr\IudexMensurarumMysteriorum\Expr\Unit;
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

/**
 * Converts parser AST nodes into expression trees.
 *
 * With a {@see UnitResolver}, identifiers are resolved against the catalog (conversion path).
 * Without one, identifiers become bare symbolic {@see Unit} nodes (chosen syntax / display).
 */
final class AstConverter
{
    public function __construct(
        private readonly ?UnitResolver $unitResolver = null,
    ) {
    }

    /**
     * Converter that preserves identifier names as symbolic units (no catalog lookup).
     */
    public static function symbolic(): self
    {
        return new self(null);
    }

    public function convert(Ast $ast): Expr
    {
        return match ($ast::class) {
            Div::class => new Compound([
                $this->convert($ast->left),
                new Term($this->convert($ast->right), -1),
            ]),
            Float_::class => new Constant(Rational::fromDecimalString($ast->value)),
            Identifier::class => $this->convertIdentifier($ast),
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

    private function convertIdentifier(Identifier $ast): Expr
    {
        if ($this->unitResolver !== null) {
            return $this->unitResolver->resolveOrFail($ast->identifier);
        }

        return new Unit($ast->identifier);
    }

    private function convertPower(Pow $ast): Expr
    {
        if (!$ast->right instanceof Integer_) {
            throw UnsupportedSyntaxException::create($ast);
        }

        return new Term($this->convert($ast->left), (int) $ast->right->value);
    }
}
