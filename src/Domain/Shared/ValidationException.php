<?php

namespace App\Domain\Shared;

use Throwable;

class ValidationException extends DomainException
{

    /**
     * @param array<string, list<string>> $errors
     */
    public static function invalidInput(array $errors, ?Throwable $previous = null): self {
        return new self(
            "validation.failed",
            DomainExceptionCodes::VALIDATION_FAILED->value,
            400,
            ['fieldCount' => count($errors)],
            $previous
        );
    }

    /**
     * Thrown when invalid csrf provided
     * @param Throwable|null $previous
     * @return self
     */
    public static function invalidCsrf(?Throwable $previous = null): self {
        return new self(
            "validation.csrf.invalid",
            DomainExceptionCodes::INVALID_CSRF->value,
            400,
            [],
            $previous
        );
    }

    /**
     * Thrown when no csrf found
     * @param Throwable|null $previous
     * @return self
     */
    public static function missingCsrf(?Throwable $previous = null): self
    {
        return new self(
            "validation.csrf.missing",
            DomainExceptionCodes::MISSING_CSRF->value,
            400,
            [],
            $previous
        );
    }
}