<?php
namespace App\Infrastructure\Configuration;

use App\Infrastructure\Shared\InfrastructureException;
use Throwable;

/**
 * Config exceptions
 */
final class ConfigException extends InfrastructureException
{
    /**
     * Thrown when a variable is not found in .env
     * @param string $key
     * @return self
     */
    public static function missingVariable(string $key): self
    {
        return new self(
            sprintf('Missing required environment variable: %s', $key),
            'CONFIG_MISSING_VARIABLE',
            500
        );
    }

    /**
     * Thrown when unable to load config
     * @param Throwable $previous
     * @return self
     */
    public static function loadingFailed(Throwable $previous): self
    {
        return new self(
            'Failed to load configuration from environment',
            'CONFIG_LOADING_FAILED',
            500,
            $previous
        );
    }
}
