<?php
namespace App\Domain\Announcement;

use App\Domain\Shared\DomainEvent;
use DateTimeImmutable;

/**
 * AnnouncementApprovedEvent
 *
 * Domain event raised when announcement is approved by a moderator
 *
 * Fired by: Announcement::approve()
 * Handled by: Event Worker → broadcasts to SSE clients
 */
final class AnnouncementApprovedEvent extends DomainEvent
{
    private string $announcementId;
    private int $approvedBy;
    private DateTimeImmutable $approvedAt;

    /**
     * Constructor
     *
     * @param string $announcementId ID of announcement being approved
     * @param int $approvedBy User ID of moderator approving
     * @param DateTimeImmutable $approvedAt When approval happened
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
        return 'announcement.approved';
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

    /**
     * Getters
     */
    public function getAnnouncementId(): string
    {
        return $this->announcementId;
    }

    public function getApprovedBy(): int
    {
        return $this->approvedBy;
    }

    public function getApprovedAt(): DateTimeImmutable
    {
        return $this->approvedAt;
    }
}