<?php

namespace App\Domain\Exception;

use App\Domain\Shared\DomainException;
use App\Domain\Shared\ExceptionCodes;
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
            "csrf.invalid",
            ExceptionCodes::INVALID_CSRF->value,
            [],
            $previous
        );
    }

    public static function missingCsrf(?Throwable $previous = null): self
    {
        return new self(
            "csrf.missing",
            ExceptionCodes::MISSING_CSRF->value,
            [],
            $previous
        );
    }
}