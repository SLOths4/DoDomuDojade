<?php
namespace App\Domain\Announcement;

use App\Domain\Shared\DomainException;
use App\Domain\Shared\DomainExceptionCodes;

/**
 * Announcement domain exceptions
 */
final class AnnouncementException extends DomainException
{

    /**
     * Invalid or missing announcement ID
     * @param AnnouncementId $id
     * @return self
     */
    public static function invalidId(AnnouncementId $id): self
    {
        return new self(
            'announcement.invalid_id',
            DomainExceptionCodes::ANNOUNCEMENT_INVALID_ID->value,
            422,
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
            DomainExceptionCodes::ANNOUNCEMENT_EMPTY_TITLE->value
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
            DomainExceptionCodes::ANNOUNCEMENT_EMPTY_TEXT->value
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
            DomainExceptionCodes::ANNOUNCEMENT_INVALID_STATUS->value,
            400,
            [
                'staus' => $status
            ]
        );
    }

    /**
     * Announcement isn't found in a database
     * @param AnnouncementId $id
     * @return self
     */
    public static function notFound(AnnouncementId $id): self
    {
        return new self(
            'announcement.not_found',
            DomainExceptionCodes::ANNOUNCEMENT_NOT_FOUND->value,
            404,
            [
                'announcement_id' => $id
            ]
        );
    }

    /**
     * Failed to delete an announcement
     * @param AnnouncementId $id
     * @return self
     */
    public static function failedToDelete(AnnouncementId $id): self
    {
        return new self(
            'announcement.delete_failed',
            DomainExceptionCodes::ANNOUNCEMENT_DELETE_FAILED->value,
            500,
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
            DomainExceptionCodes::ANNOUNCEMENT_UPDATE_FAILED->value,
            500
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
            DomainExceptionCodes::ANNOUNCEMENT_STATUS_UPDATE_FAILED->value,
            500
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
            'announcement.title_too_short',
            DomainExceptionCodes::ANNOUNCEMENT_TITLE_TOO_SHORT->value,
            400,
            [
                'min_title_length' => $minTitleLength,
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
                DomainExceptionCodes::ANNOUNCEMENT_TEXT_TOO_SHORT->value,
                400,
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
            'announcement.title_too_long',
            DomainExceptionCodes::ANNOUNCEMENT_TITLE_TOO_LONG->value,
            400,
            [
                'max_title_length' => $maxTitleLength
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
            DomainExceptionCodes::ANNOUNCEMENT_TEXT_TOO_LONG->value,
            400,
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
                'announcement.expiration_to_far_in_the_future',
                DomainExceptionCodes::ANNOUNCEMENT_EXPIRATION_TOO_FAR_IN_THE_FUTURE->value,
                422
            );
    }

    /**
     * Thrown when the expiration date is in the past
     * @return self
     */
    public static function expirationInThePast(): self
    {
        return new self(
            'announcement.expiration_in_the_past',
            DomainExceptionCodes::ANNOUNCEMENT_EXPIRATION_IN_THE_PAST->value,
            422
        );
    }
}
