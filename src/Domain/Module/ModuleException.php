<?php
namespace App\Domain\Module;

use App\Domain\Shared\DomainException;
use App\Domain\Shared\DomainExceptionCodes;
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
            DomainExceptionCodes::MODULE_INVALID_ID->value
        );
    }

    /**
     * Module isn't found in a database
     */
    public static function notFound(?int $id = null): self
    {
        return new self(
            'module.not_found',
            DomainExceptionCodes::MODULE_NOT_FOUND->value,
            404,
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
            DomainExceptionCodes::MODULE_UPDATE_FAILED->value
        );
    }

    /**
     * Failed to toggle module
     */
    public static function failedToToggle(): self
    {
        return new self(
            'module.toggle_failed',
            DomainExceptionCodes::MODULE_TOGGLE_FAILED->value
        );
    }

    public static function invalidStartTime(DateTimeImmutable $startTime): self
    {
        return new self(
            'module.invalid_start_time',
            DomainExceptionCodes::MODULE_TOGGLE_FAILED->value,
            400,
            [
                'start_time' => $startTime
            ]
        );
    }

    public static function invalidEndTime(DateTimeImmutable $endTime): self
    {
        return new self(
            'module.invalid_end_time',
            DomainExceptionCodes::MODULE_TOGGLE_FAILED->value,
            400,
            [
                'end_time' => $endTime
            ]
        );
    }

    public static function startTimeGreaterThanEndTime(DateTimeImmutable $startTime, DateTimeImmutable $endTime): self
    {
        return new self(
            'module.start_time_greater_than_end_time',
            DomainExceptionCodes::MODULE_TOGGLE_FAILED->value,
            400,
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
            DomainExceptionCodes::MODULE_INVALID_NAME->value,
            400,
            [
                'name' => $name
            ]
        );
    }
}
