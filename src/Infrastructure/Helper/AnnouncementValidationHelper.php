<?php

namespace App\Infrastructure\Helper;

use App\Domain\Announcement\AnnouncementException;
use App\Domain\Announcement\AnnouncementBusinessValidator;
use App\Domain\Announcement\AnnouncementId;
use DateMalformedStringException;
use DateTimeImmutable;

/**
 * Helper class for validating announcements
 */
final readonly class AnnouncementValidationHelper {

    public function __construct(
        private AnnouncementBusinessValidator $validator,
    ) {}

    /**
     * Validates announcement's title
     *
     * @param string $title
     * @return void
     * @throws AnnouncementException
     */
    public function validateTitle(string $title): void
    {
        $this->validator->validateTitle($title);
    }

    /**
     * Waliduje tekst ogłoszenia.
     *
     * @throws AnnouncementException
     */
    public function validateText(string $text): void
    {
        $this->validator->validateText($text);
    }

    /**
     * Validates expiry date
     *
     * Preconditions
     * - Max 1 into the future
     *
     * @throws AnnouncementException
     * @throws DateMalformedStringException
     */
    public function validateValidUntilDate(DateTimeImmutable $validUntil): void
    {
        $this->validator->validateValidUntilDate($validUntil);
    }

    /**
     * Validates announcement's id
     *
     * @throws AnnouncementException
     */
    public function validateId(AnnouncementId $id): void
    {
        $this->validator->validateId($id);
    }
}
