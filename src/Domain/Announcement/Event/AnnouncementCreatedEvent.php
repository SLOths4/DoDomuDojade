<?php

namespace App\Domain\Announcement\Event;

use App\Domain\Shared\DomainEvent;
use DateTimeImmutable;

final class AnnouncementCreatedEvent extends DomainEvent
{
    private string $announcementId {
        get {
            return $this->announcementId;
        }
    }
    private string $title {
        get {
            return $this->title;
        }
    }
    private string $text {
        get {
            return $this->text;
        }
    }
    private DateTimeImmutable $createdAt {
        get {
            return $this->createdAt;
        }
    }

    public function __construct(
        string            $announcementId,
        string            $title,
        string            $text,
        DateTimeImmutable $createdAt
    ) {
        parent::__construct(
            aggregateId: $announcementId,
            aggregateType: 'Announcement'
        );

        $this->announcementId = $announcementId;
        $this->title = $title;
        $this->text = $text;
        $this->createdAt = $createdAt;
    }

    public function getEventType(): string
    {
        return 'announcement.created';
    }

    protected function getPayload(): array
    {
        return [
            'announcementId' => $this->announcementId,
            'title' => $this->title,
            'text' => $this->text,
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }

}