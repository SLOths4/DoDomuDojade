<?php

namespace App\Infrastructure\Helper;

use App\Domain\Countdown\CountdownException;
use App\Domain\Countdown\CountdownBusinessValidator;
use DateTimeImmutable;

/**
 *  Helper class for validating countdowns
 */
final readonly class CountdownValidationHelper {
    public function __construct(
        private CountdownBusinessValidator $validator,
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
        $this->validator->validateTitle($title);
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
        $this->validator->validateCountToDate($countTo);
    }

    /**
     * Validates countdown's id
     *
     * @throws CountdownException
     */
    public function validateId(int $id): void
    {
        $this->validator->validateId($id);
    }
}
