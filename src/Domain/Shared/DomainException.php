<?php

namespace App\Domain\Shared;

use Exception;
use Throwable;

/**
 * Basic exception for domain errors
 */
class DomainException extends Exception
{
    public function __construct(
        string $message,
        public readonly string $errorCode,
        public readonly int $httpStatusCode = 400,
        array $context = [],
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }
}