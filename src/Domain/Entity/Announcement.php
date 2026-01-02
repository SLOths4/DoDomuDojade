<?php

namespace App\Domain\Entity;

use App\Domain\Enum\AnnouncementStatus;
use DateTimeImmutable;

/**
 * Announcement entity
 */
final class Announcement {
    public function __construct(
        public ?int               $id,
        public string             $title,
        public string             $text,
        public DateTimeImmutable  $createdAt,
        public DateTimeImmutable  $validUntil,
        public ?int               $userId,
        public AnnouncementStatus $status = AnnouncementStatus::PENDING,
        public ?DateTimeImmutable $decidedAt = null,
        public ?int               $decidedBy = null,
    ){}

    /**
     * Creates a new announcement <br>
     * Announcement is approved by default
     * @param string $title
     * @param string $text
     * @param DateTimeImmutable $validUntil
     * @param int $userId
     * @return self
     */
    public static function createNew(
        string            $title,
        string            $text,
        DateTimeImmutable $validUntil,
        int               $userId,
    ): self
    {
        return new self(
            id: null,
            title: $title,
            text: $text,
            createdAt: new DateTimeImmutable(),
            validUntil: $validUntil,
            userId: $userId,
            status: AnnouncementStatus::APPROVED,
            decidedAt: new DateTimeImmutable(),
            decidedBy: $userId
        );
    }

    /**
     * Proposes a new announcement
     * @param string $title
     * @param string $text
     * @param DateTimeImmutable $validUntil
     * @return self
     */
    public static function proposeNew(
        string $title, string $text,
        DateTimeImmutable $validUntil
    ): self
    {
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
     * Changest status of an announcements entity to approved
     * @param int $decidedBy
     * @return void
     */
    public function approve(int $decidedBy): void
    {
        $this->status = AnnouncementStatus::APPROVED;
        $this->decidedAt = new DateTimeImmutable();
        $this->decidedBy = $decidedBy;
    }

    public function reject(int $decidedBy): void
    {
        $this->status = AnnouncementStatus::REJECTED;
        $this->decidedAt = new DateTimeImmutable();
        $this->decidedBy = $decidedBy;
    }

    /**
     * Checks if an announcement has been approved and if valid until date is not in the past
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->status === AnnouncementStatus::APPROVED
            && new DateTimeImmutable() <= $this->validUntil;
    }
}