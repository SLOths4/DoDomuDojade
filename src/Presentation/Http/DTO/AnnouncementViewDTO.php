<?php

namespace App\Presentation\Http\DTO;

/**
 * DTO for announcement view
 */
final readonly class AnnouncementViewDTO
{
    public function __construct(
        public string  $id,
        public string  $title,
        public string  $text,
        public string  $status,
        public string  $createdAt,
        public string  $validUntil,
        public ?string $decidedAt,
        public ?string $authorUsername,
        public ?string $decidedByName,
    ) {}
}
