<?php

namespace App\Presentation\Http\DTO;

/**
 * Display DTO for Announcement
 * Used for presenting announcement data in API responses and views
 */
final readonly class AnnouncementViewDTO
{
    public function __construct(
        public string  $id,
        public string  $title,
        public string  $text,
        public string  $userId,
        public string  $createdAt,
        public string  $validUntil,
        public string  $status,
        public ?string $decidedAt = null,
        public ?string $decidedBy = null,
        public ?string $authorName = null,
        public ?string $decidedByName = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'text' => $this->text,
            'userId' => $this->userId,
            'createdAt' => $this->createdAt,
            'validUntil' => $this->validUntil,
            'status' => $this->status,
            'decidedAt' => $this->decidedAt,
            'decidedBy' => $this->decidedBy,
            'authorName' => $this->authorName,
            'decidedByName' => $this->decidedByName,
        ];
    }
}