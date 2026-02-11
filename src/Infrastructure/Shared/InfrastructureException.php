<?php
namespace App\Infrastructure\Shared;

use Exception;
use Throwable;

class InfrastructureException extends Exception
{
    public function __construct(
        string $message,
        public readonly string $errorCode = "INFRASTRUCTURE_ERROR",
        public readonly int $httpStatusCode = 500,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }
}
