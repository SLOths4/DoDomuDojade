<?php

namespace App\Domain\Entity;

use App\Domain\Enum\AnnouncementStatus;
use DateTimeImmutable;

class Announcement {
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

    public function approve(int $userId): void
    {
        $this->status = AnnouncementStatus::APPROVED;
        $this->decidedAt = new DateTimeImmutable();
        $this->decidedBy = $userId;
    }

    public function reject(int $userId): void
    {
        $this->status = AnnouncementStatus::REJECTED;
        $this->decidedAt = new DateTimeImmutable();
        $this->decidedBy = $userId;
    }
}