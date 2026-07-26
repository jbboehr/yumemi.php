<?php

namespace jbboehr\Yumemi\Analyzer;

use jbboehr\Yumemi\Exception\UnsupportedSyntaxException;
use jbboehr\Yumemi\Expr;
use jbboehr\Yumemi\Expr\Compound;
use jbboehr\Yumemi\Expr\Constant;
use jbboehr\Yumemi\Expr\Term;
use jbboehr\Yumemi\Expr\Unit;
use jbboehr\Yumemi\Number\Rational;
use jbboehr\Yumemi\Parser\Ast;
use jbboehr\Yumemi\Parser\Ast\Add;
use jbboehr\Yumemi\Parser\Ast\At;
use jbboehr\Yumemi\Parser\Ast\Div;
use jbboehr\Yumemi\Parser\Ast\Float_;
use jbboehr\Yumemi\Parser\Ast\Identifier;
use jbboehr\Yumemi\Parser\Ast\Integer_;
use jbboehr\Yumemi\Parser\Ast\Mul;
use jbboehr\Yumemi\Parser\Ast\Pow;
use jbboehr\Yumemi\Parser\Ast\Sub;

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
