<?php

namespace App\Domain\Exception;

use App\Domain\Enum\ExceptionCodes;
use Throwable;

class AuthenticationException extends DomainException
{
    public static function invalidCredentials(): self
    {
        return new self(
            'auth.invalid_credentials',
            ExceptionCodes::AUTH_INVALID_CREDENTIALS->value
        );
    }

    public static function emptyCredentials(): self
    {
        return new self(
            'auth.empty_credentials',
            ExceptionCodes::AUTH_EMPTY_CREDENTIALS->value
        );
    }

    public static function noUserLoggedIn(?Throwable $previous = null): self
    {
        return new self(
            "auth.no_user_logged_in",
            ExceptionCodes::AUTH_USER_NOT_LOGGED_IN->value,
            [],
            $previous
        );
    }

    public static function userNotFound(): self
    {
        return new self(
            'auth.user_not_found',
            ExceptionCodes::AUTH_USER_NOT_FOUND->value
        );
    }

    public static function unauthorized(int $userId, ?Throwable $previous = null): self
    {
        return new self(
            "auth.unauthorized",
            ExceptionCodes::AUTH_USER_UNAUTHORIZED->value,
            ['userId' => $userId],
            $previous
        );
    }
}