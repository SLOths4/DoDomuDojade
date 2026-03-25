<?php

declare(strict_types=1);

namespace App\Domain\Countdown;

use DateTimeImmutable;

final readonly class CountdownBusinessValidator
{
    public function __construct(
        private int $minTitleLength,
        private int $maxTitleLength,
    ) {}

    /**
     * @throws CountdownException
     */
    public function validateTitle(string $title): void
    {
        if (empty($title)) {
            throw CountdownException::emptyFields();
        }

        $length = mb_strlen($title);

        if ($length < $this->minTitleLength) {
            throw CountdownException::titleTooShort($this->minTitleLength);
        }

        if ($length > $this->maxTitleLength) {
            throw CountdownException::titleTooLong($this->maxTitleLength);
        }
    }

    /**
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
     * @throws CountdownException
     */
    public function validateId(int $id): void
    {
        if ($id <= 0) {
            throw CountdownException::invalidId($id);
        }
    }
}
