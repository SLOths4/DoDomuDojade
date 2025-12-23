<?php
namespace App\Domain\Exception;

use App\Domain\Enum\ExceptionCodes;

/**
 * Announcement domain exceptions - contains translation KEYS
 */
final class AnnouncementException extends DomainException
{

    /**
     * Invalid or missing announcement ID
     */
    public static function invalidId(): self
    {
        return new self(
            'announcement.invalid_id',
            ExceptionCodes::ANNOUNCEMENT_INVALID_ID->value
        );
    }

    /**
     * Title is empty or too short
     */
    public static function emptyTitle(): self
    {
        return new self(
            'announcement.empty_title',
            ExceptionCodes::ANNOUNCEMENT_EMPTY_TITLE->value
        );
    }

    /**
     * Text is empty or too short
     */
    public static function emptyText(): self
    {
        return new self(
            'announcement.empty_text',
            ExceptionCodes::ANNOUNCEMENT_EMPTY_TEXT->value
        );
    }

    /**
     * Valid until the date is invalid or in the past
     */
    public static function invalidValidUntilDate(): self
    {
        return new self(
            'announcement.invalid_valid_until',
            ExceptionCodes::ANNOUNCEMENT_INVALID_VALID_UNTIL->value
        );
    }

    /**
     * Announcement isn't found in a database
     */
    public static function notFound(int $id): self
    {
        return new self(
            'announcement.not_found',
            ExceptionCodes::ANNOUNCEMENT_NOT_FOUND->value
        );
    }

    /**
     * Failed to create an announcement
     */
    public static function failedToCreate(): self
    {
        return new self(
            'announcement.create_failed',
            ExceptionCodes::ANNOUNCEMENT_CREATE_FAILED->value
        );
    }

    /**
     * Failed to delete an announcement
     */
    public static function failedToDelete(): self
    {
        return new self(
            'announcement.delete_failed',
            ExceptionCodes::ANNOUNCEMENT_DELETE_FAILED->value
        );
    }

    /**
     * Failed to update an announcement
     */
    public static function failedToUpdate(): self
    {
        return new self(
            'announcement.update_failed',
            ExceptionCodes::ANNOUNCEMENT_UPDATE_FAILED->value
        );
    }

    /**
     * No changes made to an announcement
     */
    public static function noChanges(): self
    {
        return new self(
            'announcement.no_changes',
            ExceptionCodes::ANNOUNCEMENT_NO_CHANGES->value
        );
    }
}
