<?php

namespace App\Domain\Announcement;

use DateTimeImmutable;
use App\Domain\Shared\DomainEvent;

/**
 * Announcement Entity
 *
 * Aggregate Root for Announcement domain
 *
 * Immutability strategy:
 * - Mutable properties: status, decidedAt, decidedBy (mogą się zmieniać)
 * - Immutable properties: id, title, text, createdAt, validUntil, userId (nie zmieniają się)
 *
 * Getters/Setters tylko dla properties które mogą się zmieniać!
 */
final class Announcement
{
    private array $events = [];

    public function __construct(
        private ?AnnouncementId $id,
        private string $title,
        private string $text,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $validUntil,
        private ?int $userId,
        private AnnouncementStatus $status = AnnouncementStatus::PENDING,
        private ?DateTimeImmutable $decidedAt = null,
        private ?int $decidedBy = null,
    ) {}

    /**
     * Creates a new announcement
     * Announcement is approved by default
     */
    public static function create(
        string $title,
        string $text,
        DateTimeImmutable $validUntil,
        int $userId,
    ): self {
        $id = AnnouncementId::generate();
        $now = new DateTimeImmutable();

        $announcement = new self(
            id: $id,
            title: $title,
            text: $text,
            createdAt: $now,
            validUntil: $validUntil,
            userId: $userId,
            status: AnnouncementStatus::APPROVED,
            decidedAt: $now,
            decidedBy: $userId
        );

        $announcement->recordEvent(
            new AnnouncementCreatedEvent(
                announcementId: $id->getValue(),
                title: $title,
                text: $text,
                createdAt: $now,
            )
        );

        return $announcement;
    }

    /**
     * Proposes a new announcement
     */
    public static function proposeNew(
        string $title,
        string $text,
        DateTimeImmutable $validUntil
    ): self {
        return new self(
            id: null,
            title: $title,
            text: $text,
            createdAt: new DateTimeImmutable(),
            validUntil: $validUntil,
            userId: null,
            status: AnnouncementStatus::PENDING,
            decidedAt: null,
            decidedBy: null
        );
    }

    /**
     * Change status to approved
     *
     * MUTABLE OPERATION - zmienia state entity
     */
    public function approve(int $decidedBy): void
    {
        $this->status = AnnouncementStatus::APPROVED;
        $this->decidedAt = new DateTimeImmutable();
        $this->decidedBy = $decidedBy;

        $this->recordEvent(
            new AnnouncementApprovedEvent(
                announcementId: $this->id->getValue(),
                approvedBy: $decidedBy,
                approvedAt: $this->decidedAt,
            )
        );
    }

    /**
     * Change status to rejected
     *
     * MUTABLE OPERATION - zmienia state entity
     */
    public function reject(int $decidedBy): void
    {
        $this->status = AnnouncementStatus::REJECTED;
        $this->decidedAt = new DateTimeImmutable();
        $this->decidedBy = $decidedBy;
    }

    /**
     * Checks if announcement is valid
     * - Status must be APPROVED
     * - Valid until date must be in future
     */
    public function isValid(): bool
    {
        return $this->status === AnnouncementStatus::APPROVED
            && new DateTimeImmutable() <= $this->validUntil;
    }

    public function getId(): ?AnnouncementId
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getValidUntil(): DateTimeImmutable
    {
        return $this->validUntil;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getStatus(): AnnouncementStatus
    {
        return $this->status;
    }

    public function setStatus(AnnouncementStatus $status): void
    {
        $this->status = $status;
    }

    public function getDecidedAt(): ?DateTimeImmutable
    {
        return $this->decidedAt;
    }

    public function setDecidedAt(?DateTimeImmutable $decidedAt): void
    {
        $this->decidedAt = $decidedAt;
    }

    public function getDecidedBy(): ?int
    {
        return $this->decidedBy;
    }

    public function setDecidedBy(?int $decidedBy): void
    {
        $this->decidedBy = $decidedBy;
    }

    /**
     * Domain Event Management
     */

    private function recordEvent(DomainEvent $event): void
    {
        $this->events[] = $event;
    }

    public function getDomainEvents(): array
    {
        return $this->events;
    }

    public function clearDomainEvents(): void
    {
        $this->events = [];
    }

    /**
     * Serialize to array (for database storage)
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id?->getValue(),
            'title' => $this->title,
            'text' => $this->text,
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
            'validUntil' => $this->validUntil->format('Y-m-d H:i:s'),
            'userId' => $this->userId,
            'status' => $this->status->value,
            'decidedAt' => $this->decidedAt?->format('Y-m-d H:i:s'),
            'decidedBy' => $this->decidedBy,
        ];
    }

    /**
     * Hydrate from database array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ? new AnnouncementId($data['id']) : null,
            title: $data['title'],
            text: $data['text'],
            createdAt: new DateTimeImmutable($data['created_at']),
            validUntil: new DateTimeImmutable($data['valid_until']),
            userId: $data['user_id'],
            status: AnnouncementStatus::from($data['status']),
            decidedAt: $data['decided_at'] ? new DateTimeImmutable($data['decided_at']) : null,
            decidedBy: $data['decided_by'],
        );
    }
}
