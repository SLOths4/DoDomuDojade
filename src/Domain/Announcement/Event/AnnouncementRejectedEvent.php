<?php

namespace App\Domain\Announcement\Event;

use App\Domain\Shared\DomainEvent;
use DateTimeImmutable;

class AnnouncementRejectedEvent extends DomainEvent
{

    private string $announcementId {
        get {
            return $this->announcementId;
        }
    }
    private int $approvedBy {
        get {
            return $this->approvedBy;
        }
    }
    private DateTimeImmutable $approvedAt {
        get {
            return $this->approvedAt;
        }
    }

    /**
     * @param string $announcementId ID of announcement being approved
     * @param int $approvedBy User ID of moderator approving
     * @param DateTimeImmutable $approvedAt When approval happened
     * @throws \DateMalformedStringException
     */
    public function __construct(
        string $announcementId,
        int $approvedBy,
        DateTimeImmutable $approvedAt
    ) {
        parent::__construct(
            aggregateId: $announcementId,
            aggregateType: 'Announcement'
        );

        $this->announcementId = $announcementId;
        $this->approvedBy = $approvedBy;
        $this->approvedAt = $approvedAt;
    }

    /**
     * Get event type identifier
     *
     * Used for routing events to specific handlers
     */
    public function getEventType(): string
    {
        return 'announcement.rejected';
    }

    /**
     * Get event payload
     *
     * Contains domain-specific data for this event
     * Will be stored as JSON in database
     */
    public function getPayload(): array
    {
        return [
            'announcementId' => $this->announcementId,
            'approvedBy' => $this->approvedBy,
            'approvedAt' => $this->approvedAt->format('Y-m-d H:i:s'),
        ];
    }

}