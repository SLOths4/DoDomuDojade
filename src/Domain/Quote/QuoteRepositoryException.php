<?php
declare(strict_types=1);

namespace App\Domain\Quote;

use App\Domain\Shared\DomainException;

class QuoteRepositoryException extends DomainException
{
    public static function persistenceFailed(string $message, ?\Throwable $previous = null): self
    {
        return new self($message, 'QUOTE_PERSISTENCE_FAILED', 500, [], $previous);
    }

    public static function fetchFailed(string $message, ?\Throwable $previous = null): self
    {
        return new self($message, 'QUOTE_FETCH_FAILED', 500, [], $previous);
    }
}
