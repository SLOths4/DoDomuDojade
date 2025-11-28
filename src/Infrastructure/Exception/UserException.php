<?php

namespace App\Infrastructure\Exception;

use Throwable;

class UserException extends BaseException
{
    private function __construct(string $message, int $code = 0, array $context = [], ?Throwable $previous = null) {
        parent::__construct($message, $code, $context, $previous);
    }

    public static function noUserLoggedIn(?Throwable $previous = null): self
    {
        return new self(
            "No user logged in",
            ExceptionCodes::USER_NOT_LOGGED_IN->value,
            [],
            $previous
        );
    }

    public static function notFound(int $userId, ?Throwable $previous = null): self
    {
        return new self(
            "User not found",
            ExceptionCodes::USER_NOT_FOUND->value,
            ['userId' => $userId],
            $previous
        );
    }

    public static function userAlreadyExists(int $userId, ?Throwable $previous = null): self
    {
        return new self(
          "User already exists",
          ExceptionCodes::USER_ALREADY_EXISTS->value,
          ['userId' => $userId],
          $previous
        );
    }

    public static function unauthorized(int $userId, ?Throwable $previous = null): self
    {
        return new self(
            "User unauthorized to access resource",
            ExceptionCodes::USER_UNAUTHORIZED->value,
            ['userId' => $userId],
            $previous
        );
    }
}