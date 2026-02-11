<?php

namespace App\Infrastructure\Helper;

use App\Domain\Module\ModuleException;
use DateTimeImmutable;

/**
 *  Helper class for validating modules
 */
final readonly class ModuleValidationHelper
{
    /**
     * @throws ModuleException
     */
    public function validateStartTime(DateTimeImmutable $startTime): void
    {
        $hour = (int)$startTime->format('H');
        $minute = (int)$startTime->format('i');

        if ($hour < 0 || $hour > 23 || $minute < 0 || $minute > 59) {
            throw ModuleException::invalidStartTime($startTime);
        }
    }

    /**
     * @throws ModuleException
     */
    public function validateEndTime(DateTimeImmutable $endTime): void
    {
        $hour = (int)$endTime->format('H');
        $minute = (int)$endTime->format('i');

        if ($hour < 0 || $hour > 23 || $minute < 0 || $minute > 59) {
            throw ModuleException::invalidEndTime($endTime);
        }
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
        if ($startTime > $endTime) {
            throw ModuleException::startTimeGreaterThanEndTime($startTime, $endTime);
        }
    }

    /**
     * @throws ModuleException
     */
    public function validateId(int $id): void
    {
        if ($id <= 0) {
            throw ModuleException::invalidId($id);
        }
    }
}