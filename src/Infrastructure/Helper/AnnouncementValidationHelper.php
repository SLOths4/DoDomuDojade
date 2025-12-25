<?php

namespace App\Infrastructure\Helper;

use App\config\Config;
use App\Domain\Exception\AnnouncementException;
use DateMalformedStringException;
use DateTimeImmutable;

final readonly class AnnouncementValidationHelper {

    public function __construct(
        private Config $config,
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
        if (empty($title)) {
            throw AnnouncementException::emptyTitle();
        }

        $minLength = $this->config->announcementMinTitleLength;
        $maxLength = $this->config->announcementMaxTitleLength;
        $length = mb_strlen($title);

        if ($length < $minLength) {
            throw AnnouncementException::titleTooShort($minLength);
        }

        if ($length > $maxLength) {
            throw AnnouncementException::titleTooLong($maxLength);
        }
    }

    /**
     * Waliduje tekst ogłoszenia.
     *
     * @throws AnnouncementException
     */
    public function validateText(string $text): void
    {
        if (empty($text)) {
            throw AnnouncementException::emptyText();
        }

        $minLength = $this->config->announcementMinTextLength;
        $maxLength = $this->config->announcementMaxTextLength;
        $length = mb_strlen($text);

        if ($length < $minLength) {
            throw AnnouncementException::textTooShort($minLength);
        }

        if ($length > $maxLength) {
            throw AnnouncementException::textTooLong($maxLength);
        }
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
        $today = new DateTimeImmutable();

        if ($validUntil < $today) {
            throw AnnouncementException::expirationInThePast();
        }

        $maxDate = $this->config->announcementMaxValidDate;
        $maxDate = $today->modify($maxDate);
        if ($validUntil > $maxDate) {
            throw AnnouncementException::expirationTooFarInFuture();
        }
    }

    /**
     * Validates announcement's id
     *
     * @throws AnnouncementException
     */
    public function validateId(int $id): void
    {
        if ($id <= 0) {
            throw AnnouncementException::invalidId($id);
        }
    }
}