<?php

namespace App\Domain\Announcement\Event;

use App\Domain\Shared\DomainEvent;

final class AnnouncementDeletedEvent extends DomainEvent
{
    public function __construct(string $announcementId)
    {
        parent::__construct($announcementId, 'Announcement');
    }

    public function getEventType(): string
    {
        return 'announcement.deleted';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getPayload(): array
    {
        return [
            'announcementId' => $this->aggregateId
        ];
    }
}
