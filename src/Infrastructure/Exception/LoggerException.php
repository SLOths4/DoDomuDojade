<?php

namespace App\Infrastructure\Exception;

use Throwable;

class LoggerException extends BaseException
{
    public static function creationFailed(?Throwable $previous = null): LoggerException {
        return new self(
            "Error while creating logger",
            ExceptionCodes::LOGGER_CREATION_FAILED->value,
            [],
            $previous
        );
    }
}