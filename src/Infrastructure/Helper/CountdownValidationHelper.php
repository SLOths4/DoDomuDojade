<?php

namespace App\Infrastructure\Helper;

use App\config\Config;
use App\Domain\Exception\CountdownException;
use DateTimeImmutable;

final readonly class CountdownValidationHelper {
    public function __construct(
        private Config $config,
    ) {}


    /**
     * Validates countdown's title
     *
     * @param string $title
     * @return void
     * @throws CountdownException
     */
    public function validateTitle(string $title): void
    {
        if (empty($title)) {
            throw CountdownException::emptyFields();
        }

        $minLength = $this->config->announcementMinTitleLength;
        $maxLength = $this->config->announcementMaxTitleLength;
        $length = mb_strlen($title);

        if ($length < $minLength) {
            throw CountdownException::titleTooShort($minLength);
        }

        if ($length > $maxLength) {
            throw CountdownException::titleTooLong($maxLength);
        }
    }

    /**
     * Validates count to date
     *
     * Preconditions
     * - Count to cannot be before the current date
     *
     * @throws CountdownException
     */
    public function validateCountToDate(DateTimeImmutable $countTo): void
    {
        $today = new DateTimeImmutable();
        if ($countTo < $today) {
            throw CountdownException::countToInThePast();
        }
    }

    /**
     * Validates countdown's id
     *
     * @throws CountdownException
     */
    public function validateId(int $id): void
    {
        if ($id <= 0) {
            throw CountdownException::invalidId($id);
        }
    }
}