<?php
declare(strict_types=1);

namespace App\Domain\Announcement;

use App\Domain\Shared\DomainException;

class AnnouncementRepositoryException extends DomainException
{
    public static function persistenceFailed(string $message, ?\Throwable $previous = null): self
    {
        return new self($message, 'ANNOUNCEMENT_PERSISTENCE_FAILED', 500, [], $previous);
    }

    public static function fetchFailed(string $message, ?\Throwable $previous = null): self
    {
        return new self($message, 'ANNOUNCEMENT_FETCH_FAILED', 500, [], $previous);
    }
}
