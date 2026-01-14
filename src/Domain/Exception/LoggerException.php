<?php

namespace App\Domain\Exception;

use App\Domain\Shared\DomainException;
use App\Domain\Shared\ExceptionCodes;
use Throwable;

class LoggerException extends DomainException
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