<?php

namespace App\Domain\Event;

use App\Domain\Shared\DomainEvent;

/**
 * Describes behavior of event publisher
 */
interface EventPublisher
{
    /**
     * Publishes one event
     * @param DomainEvent $event
     * @return void
     */
    public function publish(DomainEvent $event): void;

    /**
     * Publishes multiple events
     * @param array<DomainEvent> $events
     * @return void
     */
    public function publishAll(array $events): void;
}
