<?php
declare(strict_types=1);

namespace App\Infrastructure\Redis;

use Exception;
use Throwable;

/**
 * Redis-specific exception
 */
final class RedisException extends Exception
{
    /**
     * When Redis client creation fails
     * @param Throwable $previous
     * @return self
     */
    public static function creationFailed(Throwable $previous): self
    {
        return new self(
            'Failed to create Redis client: ' . $previous->getMessage(),
            0,
            $previous
        );
    }

    /**
     * When Redis command fails
     * @param string $command
     * @param Throwable $previous
     * @return self
     */
    public static function commandFailed(string $command, Throwable $previous): self
    {
        return new self(
            "Redis command failed [{$command}]: " . $previous->getMessage(),
            0,
            $previous
        );
    }
}