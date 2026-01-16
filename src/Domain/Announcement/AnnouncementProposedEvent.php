<?php

namespace App\Domain\Announcement;

use App\Domain\Shared\DomainEvent;

final class AnnouncementProposedEvent extends DomainEvent
{
    private string $title;

    public function __construct(string $announcementId, string $title)
    {
        parent::__construct($announcementId, 'Announcement');
        $this->title = $title;
    }

    public function getEventType(): string
    {
        return 'announcement.proposed';
    }

    protected function getPayload(): array
    {
        return [
            'announcementId' => $this->aggregateId,
            'title' => $this->title
        ];
    }
}
