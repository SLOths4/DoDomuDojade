<?php

declare(strict_types=1);

namespace App\Domain\Module;

use DateTimeImmutable;

final class ModuleBusinessValidator
{
    /**
     * @throws ModuleException
     */
    public function validateStartTime(DateTimeImmutable $startTime): void
    {
        $minute = (int)$startTime->format('i');

        if ($minute < 0 || $minute > 59) {
            throw ModuleException::invalidStartTime($startTime);
        }
    }

    /**
     * @throws ModuleException
     */
    public function validateEndTime(DateTimeImmutable $endTime): void
    {
        $minute = (int)$endTime->format('i');

        if ($minute < 0 || $minute > 59) {
            throw ModuleException::invalidEndTime($endTime);
        }
    }

    /**
     * @throws ModuleException
     */
    public function validateStartTimeNotGreaterThanEndTime(
        DateTimeImmutable $startTime,
        DateTimeImmutable $endTime,
    ): void {
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
            throw ModuleException::invalidId();
        }
    }
}
