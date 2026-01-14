<?php
namespace App\Domain\Exception;

use App\Domain\Shared\DomainException;
use App\Domain\Shared\ExceptionCodes;

/**
 * View/Panel domain exceptions - contains translation KEYS
 */
final class ViewException extends DomainException
{
    /**
     * User is not authenticated
     */
    public static function userNotAuthenticated(): self
    {
        return new self(
            'view.user_not_authenticated',
            ExceptionCodes::VIEW_USER_NOT_AUTHENTICATED->value
        );
    }

    /**
     * Failed to load a view
     */
    public static function failedToLoadView(string $viewName): self
    {
        return new self(
            'view.load_failed',
            ExceptionCodes::VIEW_LOAD_FAILED->value
        );
    }

    /**
     * Data required for view is missing
     */
    public static function missingData(string $dataKey): self
    {
        return new self(
            'view.missing_data',
            ExceptionCodes::VIEW_MISSING_DATA->value
        );
    }
}
