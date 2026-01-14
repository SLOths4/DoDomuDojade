<?php

namespace App\Domain\Shared;

use DateTimeImmutable;
use DateTimeZone;

abstract class DomainEvent {
    protected string $eventId;
    protected DateTimeImmutable $occurredAt;
    protected int $version = 1;
    protected string $aggregateId;
    protected string $aggregateType;

    public function __construct(string $aggregateId, string $aggregateType) {
        $this->eventId = uniqid('evt_', true);
        $this->occurredAt = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $this->aggregateId = $aggregateId;
        $this->aggregateType = $aggregateType;
    }

    abstract public function getEventType(): string;
    abstract protected function getPayload(): array;

    public function getEventId(): string { return $this->eventId; }
    public function getAggregateId(): string { return $this->aggregateId; }

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