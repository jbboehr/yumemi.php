<?php

namespace jbboehr\IudexMensurarumMysteriorum\PHPStan;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;

/**
 * Infers unit_int / unit_float from unit($value, 'meter') when the unit string is constant.
 */
final class UnitFunctionDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{
    private const FUNCTION_NAME = 'jbboehr\\IudexMensurarumMysteriorum\\unit';

    public function __construct(
        private readonly UnitExpressionParser $parser,
    ) {
    }

    public function isFunctionSupported(FunctionReflection $functionReflection): bool
    {
        return $functionReflection->getName() === self::FUNCTION_NAME;
    }

    public function getTypeFromFunctionCall(
        FunctionReflection $functionReflection,
        FuncCall $functionCall,
        Scope $scope,
    ): ?Type {
        $args = $functionCall->getArgs();
        if (count($args) < 2) {
            return null;
        }

        $unitType = $scope->getType($args[1]->value);
        $constantStrings = $unitType->getConstantStrings();
        if (count($constantStrings) !== 1) {
            return null;
        }

        $parsed = $this->parser->parse($constantStrings[0]->getValue());
        if (!$parsed->isOk()) {
            return new ErrorType($parsed->errorMessage() ?? 'Invalid unit expression.');
        }

        $unit = $parsed->expression();
        $valueType = $scope->getType($args[0]->value);

        // Prefer int branding when the magnitude is definitely an integer (not a float).
        if ($valueType->isInteger()->yes() && !$valueType->isFloat()->yes()) {
            return new UnitIntegerType($unit);
        }

        return new UnitFloatType($unit);
    }
}
