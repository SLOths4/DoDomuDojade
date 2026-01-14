<?php

namespace App\Domain\Shared;

use Exception;
use Throwable;

class DomainException extends Exception
{
    public readonly int $errorCode;
    public readonly array $context;
    public readonly int $httpStatusCode;

    public function __construct(string $message, int $errorCode = 0,  int $httpStatusCode = 500, $context = [], ?Throwable $previous = null)
    {
        parent::__construct($message, $errorCode, $previous);
        $this->errorCode = $errorCode;
        $this->context = $context;
        $this->httpStatusCode = $httpStatusCode;
    }

}