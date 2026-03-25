<?php

namespace App\Domain\Countdown;

use App\Domain\Countdown\Event\CountdownCreatedEvent;
use App\Domain\Countdown\Event\CountdownDeletedEvent;
use App\Domain\Countdown\Event\CountdownUpdatedEvent;
use App\Domain\Shared\DomainEvent;
use DateTimeImmutable;

/**
 * Countdown entity
 */
class Countdown
{
    /** @var DomainEvent[] */
    private array $events = [];

    public function __construct(
        public ?int              $id,
        public string            $title,
        public DateTimeImmutable $countTo,
        public int               $userId,
    ){}

    public function assignId(int $id): void
    {
        $this->id = $id;
    }

    public function updateDetails(string $title, DateTimeImmutable $countTo, int $userId): void
    {
        $this->title = $title;
        $this->countTo = $countTo;
        $this->userId = $userId;
        $this->recordEvent(new CountdownUpdatedEvent((string)$this->id, $this->title));
    }

    public function markCreated(): void
    {
        $this->recordEvent(new CountdownCreatedEvent((string)$this->id, $this->title));
    }

    public function markDeleted(): void
    {
        $this->recordEvent(new CountdownDeletedEvent((string)$this->id));
    }

    /**
     * @return DomainEvent[]
     */
    public function getDomainEvents(): array
    {
        return $this->events;
    }

    public function clearDomainEvents(): void
    {
        $this->events = [];
    }

    private function recordEvent(DomainEvent $event): void
    {
        $this->events[] = $event;
    }
}
