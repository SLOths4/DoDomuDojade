<?php

namespace App\Domain\Module\Event;

use App\Domain\Shared\DomainEvent;
use DateTimeImmutable;

class ModuleUpdatedEvent extends DomainEvent
{
    public function __construct(
        string $moduleId,
        public readonly \DateTimeImmutable $startTime,
        public readonly DateTimeImmutable $endTime,
    )
    {
        parent::__construct($moduleId, 'Module');
    }

    /**
     * @inheritDoc
     */
    public function getEventType(): string
    {
        return 'module.updated';
    }

    /**
     * @inheritDoc
     */
    protected function getPayload(): array
    {
        return array_filter([
            'moduleId' => $this->aggregateId,
            'startTime' => $this->startTime,
            'endTime' => $this->endTime,
        ]);
    }
}
