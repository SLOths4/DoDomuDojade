<?php

namespace App\Domain\Exception;

use Exception;
use Throwable;

class DomainException extends Exception
{
    public readonly int $errorCode;
    public readonly array $context;

    public function __construct(string $message, int $code = 0, $context = [], ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->errorCode = $code;
        $this->context = $context;
    }

}