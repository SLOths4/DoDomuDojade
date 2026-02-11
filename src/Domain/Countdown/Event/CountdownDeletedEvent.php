<?php

namespace App\Domain\Countdown\Event;

use App\Domain\Shared\DomainEvent;

final class CountdownDeletedEvent extends DomainEvent
{
    public function __construct(string $countdownId)
    {
        parent::__construct($countdownId, 'Countdown');
    }

    public function getEventType(): string
    {
        return 'countdown.deleted';
    }

    protected function getPayload(): array
    {
        return [
            'countdownId' => $this->aggregateId
        ];
    }
}
