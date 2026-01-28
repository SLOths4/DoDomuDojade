<?php

namespace App\Domain\Shared;

use Throwable;

class AuthenticationException extends DomainException
{
    /**
     * Thrown when invalid announcements given
     * @return self
     */
    public static function invalidCredentials(): self
    {
        return new self(
            'auth.invalid_credentials',
            DomainExceptionCodes::AUTH_INVALID_CREDENTIALS->value,
            401
        );
    }

    /**
     * Thrown when no credentials given
     * @return self
     */
    public static function emptyCredentials(): self
    {
        return new self(
            'auth.empty_credentials',
            DomainExceptionCodes::AUTH_EMPTY_CREDENTIALS->value,
            400
        );
    }

    /**
     * Thrown when no user logged in
     * @param Throwable|null $previous
     * @return self
     */
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

    /**
     * Thrown when user is not found
     * @return self
     */
    public static function userNotFound(): self
    {
        return new self(
            'auth.user_not_found',
            DomainExceptionCodes::AUTH_USER_NOT_FOUND->value,
            401
        );
    }

    /**
     * Thrown when a user is not authorized to perform an action
     * @param int $userId
     * @param Throwable|null $previous
     * @return self
     */
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