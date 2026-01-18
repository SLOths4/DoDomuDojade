<?php
namespace App\Infrastructure\Event;

use App\Infrastructure\Shared\InfrastructureException;
use Throwable;

final class EventPublishingException extends InfrastructureException
{
    public static function serializationFailed(Throwable $previous): self
    {
        return new self(
            'Failed to serialize event',
            'EVENT_SERIALIZATION_FAILED',
            500,
            $previous
        );
    }

    public static function publishingFailed(string $eventType, Throwable $previous): self
    {
        return new self(
            sprintf('Failed to publish event: %s', $eventType),
            'EVENT_PUBLISHING_FAILED',
            500,
            $previous
        );
    }

    public static function storageFailed(Throwable $previous): self
    {
        return new self(
            'Failed to store event',
            'EVENT_STORAGE_FAILED',
            500,
            $previous
        );
    }
}
