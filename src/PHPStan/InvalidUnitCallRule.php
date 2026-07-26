<?php

namespace jbboehr\Yumemi\PHPStan;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ErrorType;

/**
 * Emits standalone diagnostics for invalid unit() / unit_to() calls.
 *
 * The dynamic return type extensions infer an {@see ErrorType} for invalid unit strings,
 * dimensional mismatches, and value/from mismatches, but PHPStan does not report a call
 * just because its return type is an error. This rule surfaces the same reason as an actual
 * diagnostic so mistakes are caught even when the result is never used in a strict context.
 *
 * @implements Rule<FuncCall>
 */
final class InvalidUnitCallRule implements Rule
{
    private const UNIT = 'jbboehr\\Yumemi\\unit';
    private const UNIT_TO = 'jbboehr\\Yumemi\\unit_to';

    public function __construct(
        private readonly UnitFunctionDynamicReturnTypeExtension $unitExtension,
        private readonly UnitToFunctionDynamicReturnTypeExtension $unitToExtension,
    ) {
    }

    public function getNodeType(): string
    {
        return FuncCall::class;
    }

    /**
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->name instanceof Name) {
            return [];
        }

        $type = match ($scope->resolveName($node->name)) {
            self::UNIT => $this->unitExtension->inferType($node, $scope),
            self::UNIT_TO => $this->unitToExtension->inferType($node, $scope),
            default => null,
        };

        if (!$type instanceof ErrorType) {
            return [];
        }

        $reason = $type->getReason();
        if ($reason === null) {
            return [];
        }

        return [
            RuleErrorBuilder::message($reason)
                ->identifier('imm.invalidUnitCall')
                ->build(),
        ];
    }
}
