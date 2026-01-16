<?php
declare(strict_types=1);

namespace App\Application\Shared;

use Exception;
use Throwable;

class ApplicationException extends Exception
{
    public function __construct(
        string $message = "Application error",
        private readonly int $httpStatusCode = 500,
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getHttpStatusCode(): int
    {
        return $this->httpStatusCode;
    }
}
