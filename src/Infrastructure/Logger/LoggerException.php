<?php
namespace App\Infrastructure\Logger;

use App\Infrastructure\Shared\InfrastructureException;
use Throwable;

final class LoggerException extends InfrastructureException
{
    public static function creationFailed(Throwable $previous): self
    {
        return new self(
            'Failed to create logger instance',
            'LOGGER_CREATION_FAILED',
            500,
            $previous
        );
    }
}
