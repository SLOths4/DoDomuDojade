<?php
namespace App\Domain\Exception;

use App\Domain\Shared\DomainException;
use App\Domain\Shared\ExceptionCodes;

/**
 * Countdown domain exceptions - contains translation KEYS
 */
final class CountdownException extends DomainException
{
    /**
     * Invalid or missing countdown ID
     */
    public static function invalidId(int $id): self
    {
        return new self(
            'countdown.invalid_id',
            ExceptionCodes::COUNTDOWN_INVALID_ID->value,
            [
                'countdown_id' => $id
            ]
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
    public static function countToInThePast(): self
    {
        return new self(
            'countdown.count_to_in_the_past',
            ExceptionCodes::COUNTDOWN_COUNT_TO_IN_THE_PAST->value
        );
    }

    /**
     * Too long title
     */
    public static function titleTooLong(int $maxTitleLength): self
    {
        return new self(
            'countdown.title_too_long',
            ExceptionCodes::COUNTDOWN_TITLE_TOO_LONG->value,
            [
                'maximum_title_length' => $maxTitleLength
            ]
        );
    }

    /**
     * Too short a title
     */
    public static function titleTooShort(int $minTitleLength): self
    {
        return new self(
            'countdown.title_too_short',
            ExceptionCodes::COUNTDOWN_TITLE_TOO_SHORT->value,
            [
                'minimum_title_length' => $minTitleLength
            ]
        );
    }

    /**
     * Countdown isn't found in a database
     */
    public static function notFound(int $id): self
    {
        return new self(
            'countdown.not_found',
            ExceptionCodes::COUNTDOWN_NOT_FOUND->value,
            [
                'countdown_id' => $id
            ]
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

    public static function failedToFetch(): self
    {
        return new self(
            'countdown.fetch_failed',
            ExceptionCodes::COUNTDOWN_FETCH_FAILED->value
        );
    }
}
