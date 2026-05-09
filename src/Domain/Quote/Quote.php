<?php

namespace App\Domain\Quote;

use App\Domain\Shared\DomainEvent;
use DateTimeImmutable;

/**
 * Quote entity
 */
class Quote
{
    /** @var DomainEvent[] */
    private array $events = [];

    public function __construct(
        public private(set) ?int $id,
        public readonly string $quote,
        public readonly string $author,
        public readonly DateTimeImmutable $fetchedOn
    ){}

    public function assignId(int $id): void
    {
        $this->id = $id;
    }

    public function markCreated(): void
    {
        $this->events[] = new QuoteCreatedEvent((string)$this->id, $this->quote, $this->author);
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
}
