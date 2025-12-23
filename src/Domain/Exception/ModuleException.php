<?php
namespace App\Domain\Exception;

use App\Domain\Enum\ExceptionCodes;

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
     * Module isn't found in database
     */
    public static function notFound(int $id): self
    {
        return new self(
            'module.not_found',
            ExceptionCodes::MODULE_NOT_FOUND->value
        );
    }

    /**
     * Invalid time format provided
     */
    public static function invalidTimeFormat(): self
    {
        return new self(
            'module.invalid_time_format',
            ExceptionCodes::MODULE_INVALID_TIME_FORMAT->value
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
}
