<?php

declare(strict_types=1);

namespace App\Domain\Event;

use App\Domain\Shared\DomainEvent;

interface EventStoreRepositoryInterface
{
    public function append(DomainEvent $event): bool;
}
