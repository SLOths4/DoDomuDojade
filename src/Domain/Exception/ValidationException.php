<?php

namespace App\Domain\Exception;

use App\Domain\Enum\ExceptionCodes;
use Throwable;

class ValidationException extends DomainException
{

    /**
     * @param array<string, list<string>> $errors
     */
    public static function invalidInput(array $errors, ?Throwable $previous = null): self {
        return new self(
            "Validation failed",
            ExceptionCodes::VALIDATION_FAILED->value,
            ['fieldCount' => count($errors)],
            $previous
        );
    }

    public static function invalidCsrf(?Throwable $previous = null): self {
        return new self(
            "Invalid csrf",
            ExceptionCodes::INVALID_CSRF->value,
            [],
            $previous
        );
    }
}