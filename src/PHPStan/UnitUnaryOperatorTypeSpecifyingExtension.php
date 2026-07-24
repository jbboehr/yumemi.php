<?php

namespace jbboehr\IudexMensurarumMysteriorum\PHPStan;

use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;
use PHPStan\Type\UnaryOperatorTypeSpecifyingExtension;

/**
 * Infers types for unary + / - on unit_int and unit_float.
 *
 * Both keep the same unit; unary - only flips the numeric magnitude (statically).
 */
final class UnitUnaryOperatorTypeSpecifyingExtension implements UnaryOperatorTypeSpecifyingExtension
{
    private const SUPPORTED = ['+', '-'];

    public function isOperatorSupported(string $operatorSigil, Type $operand): bool
    {
        if (!in_array($operatorSigil, self::SUPPORTED, true)) {
            return false;
        }

        return $operand instanceof UnitIntegerType || $operand instanceof UnitFloatType;
    }

    public function specifyType(string $operatorSigil, Type $operand): Type
    {
        if (!$operand instanceof UnitIntegerType && !$operand instanceof UnitFloatType) {
            return new ErrorType('Unary unit operator requires a unit_int or unit_float operand.');
        }

        // Unary + / - preserve unit identity and magnitude kind.
        return $operand;
    }
}
