<?php
namespace App\Domain\Exception;

use App\Domain\Enum\ExceptionCodes;

/**
 * Announcement domain exceptions
 */
final class AnnouncementException extends DomainException
{

    /**
     * Invalid or missing announcement ID
     * @param int $id
     * @return self
     */
    public static function invalidId(int $id): self
    {
        return new self(
            'announcement.invalid_id',
            ExceptionCodes::ANNOUNCEMENT_INVALID_ID->value,
            [
                'announcement_id' => $id
            ]
        );
    }

    /**
     * Title is empty or too short
     * @return self
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
     * @return self
     */
    public static function emptyText(): self
    {
        return new self(
            'announcement.empty_text',
            ExceptionCodes::ANNOUNCEMENT_EMPTY_TEXT->value
        );
    }

    /**
     * Invalid announcement status
     * @param string $status
     * @return self
     */
    public static function invalidStatus(string $status): self
    {
        return new self(
            'announcement.invalid_status',
            ExceptionCodes::ANNOUNCEMENT_EMPTY_TEXT->value,
            [
                'staus' => $status
            ]
        );
    }

    /**
     * Announcement isn't found in a database
     * @param int $id
     * @return self
     */
    public static function notFound(int $id): self
    {
        return new self(
            'announcement.not_found',
            ExceptionCodes::ANNOUNCEMENT_NOT_FOUND->value,
            [
                'announcement_id' => $id
            ]
        );
    }

    /**
     * Failed to create an announcement
     * @return self
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
     * @param int $id
     * @return self
     */
    public static function failedToDelete(int $id): self
    {
        return new self(
            'announcement.delete_failed',
            ExceptionCodes::ANNOUNCEMENT_DELETE_FAILED->value,
            [
                'announcement_id' => $id
            ]
        );
    }

    /**
     * Failed to update an announcement
     * @return self
     */
    public static function failedToUpdate(): self
    {
        return new self(
            'announcement.update_failed',
            ExceptionCodes::ANNOUNCEMENT_UPDATE_FAILED->value
        );
    }

    /**
     * Failed to change the status of an announcement
     * @return self
     */
    public static function failedToUpdateStatus(): self
    {
        return new self(
            'announcement.status_update_failed',
            ExceptionCodes::ANNOUNCEMENT_UPDATE_FAILED->value
        );
    }

    /**
     * Title is too short
     * @param int $minTitleLength
     * @return self
     */
    public static function titleTooShort(int $minTitleLength): self
    {
        return new self(
            'announcement.invalid_title_length',
            ExceptionCodes::ANNOUNCEMENT_NO_CHANGES->value,
            [
                'min title length' => $minTitleLength,
            ]
        );
    }

    /**
     * Text is too short
     * @param int $minTextLength
     * @return self
     */
    public static function textTooShort(int $minTextLength): self
    {
        return new self(
                'announcement.invalid_text_length',
                ExceptionCodes::ANNOUNCEMENT_NO_CHANGES->value,
                [
                    'min title length' => $minTextLength,
                ]
        );
    }

    /**
     * Title is too long
     * @param int $maxTitleLength
     * @return self
     */
    public static function titleTooLong(int $maxTitleLength): self
    {
        return new self(
            'announcement.invalid_title_length',
            ExceptionCodes::ANNOUNCEMENT_NO_CHANGES->value,
            [
                'max title length' => $maxTitleLength
            ]
        );
    }

    /**
     * Text is too long
     * @param int $maxTextLength
     * @return self
     */
    public static function textTooLong(int $maxTextLength): self
    {
        return new self(
            'announcement.invalid_text_length',
            ExceptionCodes::ANNOUNCEMENT_NO_CHANGES->value,
            [
                'max title length' => $maxTextLength
            ]
        );
    }

    /**
     * Set expiration date is too far in the future
     * @return self
     */
    public static function expirationTooFarInFuture(): self
    {
            return new self(
                'announcement.expiration_to_far_in_future',
                ExceptionCodes::ANNOUNCEMENT_NO_CHANGES->value
            );
    }
}
