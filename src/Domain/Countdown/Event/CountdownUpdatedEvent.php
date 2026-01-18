<?php

namespace App\Domain\Countdown\Event;

use App\Domain\Shared\DomainEvent;

final class CountdownUpdatedEvent extends DomainEvent
{
    private string $title;

    public function __construct(string $countdownId, string $title)
    {
        parent::__construct($countdownId, 'Countdown');
        $this->title = $title;
    }

    public function getEventType(): string
    {
        return 'countdown.updated';
    }

    protected function getPayload(): array
    {
        return [
            'countdownId' => $this->aggregateId,
            'title' => $this->title
        ];
    }
}
