<?php

namespace App\Infrastructure\Helper;

use App\Domain\Module\ModuleException;
use App\Domain\Module\ModuleBusinessValidator;
use DateTimeImmutable;

/**
 *  Helper class for validating modules
 */
final readonly class ModuleValidationHelper
{
    public function __construct(
        private ModuleBusinessValidator $validator,
    ) {}

    /**
     * @throws ModuleException
     */
    public function validateStartTime(DateTimeImmutable $startTime): void
    {
        $this->validator->validateStartTime($startTime);
    }

    /**
     * @throws ModuleException
     */
    public function validateEndTime(DateTimeImmutable $endTime): void
    {
        $this->validator->validateEndTime($endTime);
    }

    /**
     * Validates that start time is not greater than end time
     *
     * @throws ModuleException
     */
    public function validateStartTimeNotGreaterThanEndTime(
        DateTimeImmutable $startTime,
        DateTimeImmutable $endTime
    ): void
    {
        $this->validator->validateStartTimeNotGreaterThanEndTime($startTime, $endTime);
    }

    /**
     * @throws ModuleException
     */
    public function validateId(int $id): void
    {
        $this->validator->validateId($id);
    }
}
