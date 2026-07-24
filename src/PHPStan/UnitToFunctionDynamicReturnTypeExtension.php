<?php

namespace jbboehr\IudexMensurarumMysteriorum\PHPStan;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;

/**
 * Infers unit_float<'to'> from unit_to($value, 'from', 'to') when unit strings are constant
 * and share a dimension (catalog-compatible conversion).
 */
final class UnitToFunctionDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{
    private const FUNCTION_NAME = 'jbboehr\\IudexMensurarumMysteriorum\\unit_to';

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
        if (count($args) < 3) {
            return null;
        }

        $fromUnit = $this->constantUnit($scope->getType($args[1]->value), 'from');
        if ($fromUnit instanceof ErrorType) {
            return $fromUnit;
        }

        $toUnit = $this->constantUnit($scope->getType($args[2]->value), 'to');
        if ($toUnit instanceof ErrorType) {
            return $toUnit;
        }

        $valueType = $scope->getType($args[0]->value);
        if ($valueType instanceof UnitIntegerType || $valueType instanceof UnitFloatType) {
            $valueUnit = $valueType->getUnitExpression();
            if (!$valueUnit->equivalent($fromUnit)) {
                return new ErrorType(sprintf(
                    "unit_to() value unit %s does not match from unit %s (normalized forms differ).",
                    $valueUnit->displayString,
                    $fromUnit->displayString,
                ));
            }
        }

        if (!$fromUnit->sameDimension($toUnit)) {
            return new ErrorType(sprintf(
                "Cannot convert with unit_to(): units %s and %s are not dimensionally compatible.",
                $fromUnit->displayString,
                $toUnit->displayString,
            ));
        }

        // Conversion factors are generally non-integral → always unit_float.
        return new UnitFloatType($toUnit);
    }

    private function constantUnit(Type $type, string $role): UnitExpression|ErrorType
    {
        $constantStrings = $type->getConstantStrings();
        if (count($constantStrings) !== 1) {
            return new ErrorType(sprintf(
                "unit_to() requires a constant %s unit string, e.g. unit_to(3.0, 'foot', 'meter').",
                $role,
            ));
        }

        $parsed = $this->parser->parse($constantStrings[0]->getValue());
        if (!$parsed->isOk()) {
            return new ErrorType($parsed->errorMessage() ?? 'Invalid unit expression.');
        }

        return $parsed->expression();
    }
}
