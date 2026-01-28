<?php
declare(strict_types=1);

namespace App\Application\Shared;

use Exception;
use Throwable;

/**
 * Basic exception for application exceptions
 */
class ApplicationException extends Exception
{
    public function __construct(
        string $message,
        public readonly string $errorCode = "INTERNAL_ERROR",
        public readonly int $httpStatusCode = 500,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }
}