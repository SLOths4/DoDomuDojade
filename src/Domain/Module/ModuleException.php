<?php
namespace App\Domain\Module;

use App\Domain\Shared\DomainException;
use App\Domain\Shared\ExceptionCodes;
use DateTimeImmutable;

/**
 * Module domain exceptions - contains translation KEYS
 */
final class ModuleException extends DomainException
{
    /**
     * Invalid or missing module ID
     */
    public static function invalidId(): self
    {
        return new self(
            'module.invalid_id',
            ExceptionCodes::MODULE_INVALID_ID->value
        );
    }

    /**
     * Module isn't found in a database
     */
    public static function notFound(?int $id = null): self
    {
        return new self(
            'module.not_found',
            ExceptionCodes::MODULE_NOT_FOUND->value,
            [
                '$id' => $id
            ]
        );
    }

    /**
     * Failed to update module
     */
    public static function failedToUpdate(): self
    {
        return new self(
            'module.update_failed',
            ExceptionCodes::MODULE_UPDATE_FAILED->value
        );
    }

    /**
     * Failed to toggle module
     */
    public static function failedToToggle(): self
    {
        return new self(
            'module.toggle_failed',
            ExceptionCodes::MODULE_TOGGLE_FAILED->value
        );
    }

    public static function invalidStartTime(DateTimeImmutable $startTime): self
    {
        return new self(
            'module.invalid_start_time',
            ExceptionCodes::MODULE_TOGGLE_FAILED->value,
            [
                'start_time' => $startTime
            ]
        );
    }

    public static function invalidEndTime(DateTimeImmutable $endTime): self
    {
        return new self(
            'module.invalid_end_time',
            ExceptionCodes::MODULE_TOGGLE_FAILED->value,
            [
                'end_time' => $endTime
            ]
        );
    }

    public static function startTimeGreaterThanEndTime(DateTimeImmutable $startTime, DateTimeImmutable $endTime): self
    {
        return new self(
            'module.start_time_greater_than_end_time',
            ExceptionCodes::MODULE_TOGGLE_FAILED->value,
            [
                'start_time' => $startTime,
                'end_time' => $endTime
            ]
        );
    }

    public static function invalidName(string $name): self
    {
        return new self(
            'module.invalid_name',
            ExceptionCodes::MODULE_INVALID_NAME->value,
            [
                'name' => $name
            ]
        );
    }
}
