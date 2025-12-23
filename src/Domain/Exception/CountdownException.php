<?php
namespace App\Domain\Exception;

use App\Domain\Enum\ExceptionCodes;

/**
 * Countdown domain exceptions - contains translation KEYS
 */
final class CountdownException extends DomainException
{
    /**
     * Invalid or missing countdown ID
     */
    public static function invalidId(): self
    {
        return new self(
            'countdown.invalid_id',
            ExceptionCodes::COUNTDOWN_INVALID_ID->value
        );
    }

    /**
     * All required fields are empty
     */
    public static function emptyFields(): self
    {
        return new self(
            'countdown.empty_fields',
            ExceptionCodes::COUNTDOWN_EMPTY_FIELDS->value
        );
    }

    /**
     * Invalid datetime format
     */
    public static function invalidDateFormat(): self
    {
        return new self(
            'countdown.invalid_date_format',
            ExceptionCodes::COUNTDOWN_INVALID_DATE_FORMAT->value
        );
    }

    /**
     * Countdown not found in database
     */
    public static function notFound(int $id): self
    {
        return new self(
            'countdown.not_found',
            ExceptionCodes::COUNTDOWN_NOT_FOUND->value
        );
    }

    /**
     * No changes made to countdown
     */
    public static function noChanges(): self
    {
        return new self(
            'countdown.no_changes',
            ExceptionCodes::COUNTDOWN_NO_CHANGES->value
        );
    }

    /**
     * Failed to create countdown
     */
    public static function failedToCreate(): self
    {
        return new self(
            'countdown.create_failed',
            ExceptionCodes::COUNTDOWN_CREATE_FAILED->value
        );
    }

    /**
     * Failed to update countdown
     */
    public static function failedToUpdate(): self
    {
        return new self(
            'countdown.update_failed',
            ExceptionCodes::COUNTDOWN_UPDATE_FAILED->value
        );
    }

    /**
     * Failed to delete countdown
     */
    public static function failedToDelete(): self
    {
        return new self(
            'countdown.delete_failed',
            ExceptionCodes::COUNTDOWN_DELETE_FAILED->value
        );
    }
}
