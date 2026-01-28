<?php

namespace App\Domain\Announcement;

use App\Domain\Announcement\Event\AnnouncementApprovedEvent;
use App\Domain\Announcement\Event\AnnouncementCreatedEvent;
use App\Domain\Announcement\Event\AnnouncementProposedEvent;
use App\Domain\Announcement\Event\AnnouncementRejectedEvent;
use App\Domain\Announcement\Event\AnnouncementUpdatedEvent;
use App\Domain\Shared\DomainEvent;
use DateTimeImmutable;

/**
 * Announcement Entity
 * Aggregate Root for Announcement domain
 */
final class Announcement
{
    private array $events = [];

    /**
     * @param AnnouncementId|null $id
     * @param string $title
     * @param string $text
     * @param DateTimeImmutable $createdAt
     * @param DateTimeImmutable $validUntil
     * @param int|null $userId
     * @param AnnouncementStatus $status
     * @param DateTimeImmutable|null $decidedAt
     * @param int|null $decidedBy
     */
    public function __construct(
        private readonly ?AnnouncementId   $id,
        private string                     $title,
        private string                     $text,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable          $validUntil,
        private readonly ?int              $userId,
        private AnnouncementStatus         $status = AnnouncementStatus::PENDING,
        private ?DateTimeImmutable         $decidedAt = null,
        private ?int                       $decidedBy = null,
    ) {}

    /**
     * Creates a new announcement <br>
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
     * @throws \DateMalformedStringException
     */
    public static function proposeNew(
        string $title,
        string $text,
        DateTimeImmutable $validUntil
    ): self {
        $id = AnnouncementId::generate();
        $announcement = new self(
            id: $id,
            title: $title,
            text: $text,
            createdAt: new DateTimeImmutable(),
            validUntil: $validUntil,
            userId: null,
            status: AnnouncementStatus::PENDING,
            decidedAt: null,
            decidedBy: null
        );

        $announcement->recordEvent(
            new AnnouncementProposedEvent(
                announcementId: $id,
                title:          $title,
            )
        );

        return $announcement;
    }

    /**
     * Change status to approved
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
     */
    public function reject(int $decidedBy): void
    {
        $this->status = AnnouncementStatus::REJECTED;
        $this->decidedAt = new DateTimeImmutable();
        $this->decidedBy = $decidedBy;

        $this->recordEvent(
            new AnnouncementRejectedEvent(
                announcementId: $this->id->getValue(),
                approvedBy: $decidedBy,
                approvedAt: $this->decidedAt,
            )
        );
    }

    /**
     * Updates announcement data
     * @throws \DateMalformedStringException
     */
    public function update(string $title, string $text, DateTimeImmutable $validUntil, ?AnnouncementStatus $status = null, ?int $decidedBy = null): void
    {
        $this->title = $title;
        $this->text = $text;
        $this->validUntil = $validUntil;

        if ($status !== null && $status !== $this->status) {
            $this->status = $status;
            $this->decidedAt = new DateTimeImmutable();
            $this->decidedBy = $decidedBy;
        }

        $this->recordEvent(
            new AnnouncementUpdatedEvent(
                announcementId: $this->id->getValue(),
                title: $this->title,
            )
        );
    }

    /**
     * Checks if an announcement is valid
     * - Status must be APPROVED
     * - Valid until date must be in the future
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
