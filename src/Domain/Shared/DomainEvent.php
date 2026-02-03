<?php

namespace App\Domain\Shared;

use DateTimeImmutable;
use DateTimeZone;

/**
 * Describes a domain event
 */
abstract class DomainEvent {
    protected string $eventId {
        get {
            return $this->eventId;
        }
    }
    protected DateTimeImmutable $occurredAt;
    protected int $version = 1;
    protected string $aggregateId {
        get {
            return $this->aggregateId;
        }
    }
    protected string $aggregateType;

    /**
     * @throws \DateMalformedStringException
     */
    public function __construct(string $aggregateId, string $aggregateType) {
        $this->eventId = uniqid('evt_', true);
        $this->occurredAt = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $this->aggregateId = $aggregateId;
        $this->aggregateType = $aggregateType;
    }

    /**
     * Get event type identifier
     *
     * Used for routing events to specific handlers
     */
    abstract public function getEventType(): string;

    /**
     * Returns payload
     *
     * @return array
     */
    abstract protected function getPayload(): array;

    /**
     * Returns events contents as an array
     * @return array
     */
    public function toArray(): array
    {
        return [
            'eventId' => $this->eventId,
            'eventType' => $this->getEventType(),
            'aggregateId' => $this->aggregateId,
            'aggregateType' => $this->aggregateType,
            'payload' => $this->getPayload(),
            'occurredAt' => $this->occurredAt->format('Y-m-d H:i:s'),
            'version' => $this->version,
        ];
    }
}