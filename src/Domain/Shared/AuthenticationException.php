<?php

namespace App\Domain\Shared;

use Throwable;

class AuthenticationException extends DomainException
{
    public static function invalidCredentials(): self
    {
        return new self(
            'auth.invalid_credentials',
            DomainExceptionCodes::AUTH_INVALID_CREDENTIALS->value,
            401
        );
    }

    public static function emptyCredentials(): self
    {
        return new self(
            'auth.empty_credentials',
            DomainExceptionCodes::AUTH_EMPTY_CREDENTIALS->value,
            400
        );
    }

    public static function noUserLoggedIn(?Throwable $previous = null): self
    {
        return new self(
            "auth.no_user_logged_in",
            DomainExceptionCodes::AUTH_USER_NOT_LOGGED_IN->value,
            401,
            [],
            $previous
        );
    }

    public static function userNotFound(): self
    {
        return new self(
            'auth.user_not_found',
            DomainExceptionCodes::AUTH_USER_NOT_FOUND->value,
            401
        );
    }

    public static function unauthorized(int $userId, ?Throwable $previous = null): self
    {
        return new self(
            "auth.unauthorized",
            DomainExceptionCodes::AUTH_USER_UNAUTHORIZED->value,
            403,
            ['userId' => $userId],
            $previous
        );
    }
}