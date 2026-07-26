<?php

namespace jbboehr\Yumemi\Exception;

use jbboehr\Yumemi\Parser\Ast;

final class UnsupportedSyntaxException extends \RuntimeException
{
    public readonly string $expression;

    public function __construct(string $message, string $expression)
    {
        parent::__construct($message);
        $this->expression = $expression;
    }

    public static function create(Ast $ast): self
    {
        $expression = $ast->toString();

        $hint = '';
        if (str_contains($expression, '@')) {
            $hint = ' Affine / offset units (for example temperature with @) are not supported yet.';
        } elseif (str_contains($expression, '+') || str_contains($expression, '-')) {
            // Avoid treating unary minus in powers as addition syntax; AST toString uses " + " / " - ".
            if (str_contains($expression, ' + ') || str_contains($expression, ' - ')) {
                $hint = ' Addition and subtraction in unit expressions are not supported.';
            }
        }

        return new self(
            sprintf('Unsupported unit expression syntax: %s.%s', $expression, $hint),
            $expression,
        );
    }
}
