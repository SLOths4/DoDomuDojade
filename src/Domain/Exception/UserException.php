<?php
namespace App\Domain\Exception;

use App\Domain\Enum\ExceptionCodes;

/**
 * User domain exceptions - contains translation KEYS
 */
final class UserException extends DomainException
{
    /**
     * Invalid or missing user ID
     */
    public static function invalidId(): self
    {
        return new self(
            'user.invalid_id',
            ExceptionCodes::USER_INVALID_ID->value
        );
    }

    /**
     * Required fields are empty
     */
    public static function emptyFields(): self
    {
        return new self(
            'user.empty_fields',
            ExceptionCodes::USER_EMPTY_FIELDS->value
        );
    }

    /**
     * User isn't found in database
     */
    public static function notFound(int $id): self
    {
        return new self(
            'user.not_found',
            ExceptionCodes::USER_NOT_FOUND->value
        );
    }

    /**
     * Username already exists
     */
    public static function usernameTaken(): self
    {
        return new self(
            'user.username_taken',
            ExceptionCodes::USER_USERNAME_TAKEN->value
        );
    }

    /**
     * User isn't authorized to perform this action
     */
    public static function unauthorized(): self
    {
        return new self(
            'user.unauthorized',
            ExceptionCodes::USER_UNAUTHORIZED->value
        );
    }

    /**
     * Cannot delete an own user account
     */
    public static function cannotDeleteSelf(): self
    {
        return new self(
            'user.cannot_delete_self',
            ExceptionCodes::USER_CANNOT_DELETE_SELF->value
        );
    }

    /**
     * Failed to create user
     */
    public static function failedToCreate(): self
    {
        return new self(
            'user.create_failed',
            ExceptionCodes::USER_CREATE_FAILED->value
        );
    }

    /**
     * Failed to delete user
     */
    public static function failedToDelete(): self
    {
        return new self(
            'user.delete_failed',
            ExceptionCodes::USER_DELETE_FAILED->value
        );
    }
}
