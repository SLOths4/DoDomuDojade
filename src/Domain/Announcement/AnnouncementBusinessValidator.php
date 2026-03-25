<?php

declare(strict_types=1);

namespace App\Domain\Announcement;

use DateMalformedStringException;
use DateTimeImmutable;

final readonly class AnnouncementBusinessValidator
{
    public function __construct(
        private int $minTitleLength,
        private int $maxTitleLength,
        private int $minTextLength,
        private int $maxTextLength,
        private string $maxValidDate,
    ) {}

    /**
     * @throws AnnouncementException
     */
    public function validateTitle(string $title): void
    {
        if (empty($title)) {
            throw AnnouncementException::emptyTitle();
        }

        $length = mb_strlen($title);

        if ($length < $this->minTitleLength) {
            throw AnnouncementException::titleTooShort($this->minTitleLength);
        }

        if ($length > $this->maxTitleLength) {
            throw AnnouncementException::titleTooLong($this->maxTitleLength);
        }
    }

    /**
     * @throws AnnouncementException
     */
    public function validateText(string $text): void
    {
        if (empty($text)) {
            throw AnnouncementException::emptyText();
        }

        $length = mb_strlen($text);

        if ($length < $this->minTextLength) {
            throw AnnouncementException::textTooShort($this->minTextLength);
        }

        if ($length > $this->maxTextLength) {
            throw AnnouncementException::textTooLong($this->maxTextLength);
        }
    }

    /**
     * @throws AnnouncementException
     * @throws DateMalformedStringException
     */
    public function validateValidUntilDate(DateTimeImmutable $validUntil): void
    {
        $today = new DateTimeImmutable();

        if ($validUntil < $today) {
            throw AnnouncementException::expirationInThePast();
        }

        $maxDate = $today->modify($this->maxValidDate);

        if ($validUntil > $maxDate) {
            throw AnnouncementException::expirationTooFarInFuture();
        }
    }

    /**
     * @throws AnnouncementException
     */
    public function validateId(AnnouncementId $id): void
    {
        if (!str_starts_with($id->getValue(), 'ann_')) {
            throw AnnouncementException::invalidId($id);
        }
    }
}
