<?php

namespace App\Domain\Entity;

use App\Domain\Enum\AnnouncementStatus;
use DateTimeImmutable;

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

    public function isValid(): bool
    {
        return $this->status === AnnouncementStatus::APPROVED
            && new DateTimeImmutable() <= $this->validUntil;
    }
}