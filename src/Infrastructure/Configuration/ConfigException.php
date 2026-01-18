<?php
namespace App\Infrastructure\Configuration;

use App\Infrastructure\Shared\InfrastructureException;
use Throwable;

final class ConfigException extends InfrastructureException
{
    public static function missingVariable(string $key): self
    {
        return new self(
            sprintf('Missing required environment variable: %s', $key),
            'CONFIG_MISSING_VARIABLE',
            500
        );
    }

    public static function invalidValue(string $key, string $reason): self
    {
        return new self(
            sprintf('Invalid configuration value for %s: %s', $key, $reason),
            'CONFIG_INVALID_VALUE',
            500
        );
    }

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
