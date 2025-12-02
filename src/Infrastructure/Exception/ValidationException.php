<?php

namespace App\Infrastructure\Exception;

use Throwable;

class ValidationException extends BaseException
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