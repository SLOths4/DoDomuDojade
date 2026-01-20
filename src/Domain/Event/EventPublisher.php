<?php

namespace App\Domain\Event;

use App\Domain\Shared\DomainEvent;

interface EventPublisher
{
    public function publish(DomainEvent $event): void;

    public function publishAll(array $events): void;
}
