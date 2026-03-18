<?php

namespace App\Presentation\Http\DTO;

use JsonSerializable;

/**
 * DTO for API responses
 */
final class AnnouncementApiDTO implements JsonSerializable
{
    public function __construct(
        public string $id,
        public string $title,
        public string $text,
        public string $status,
        public ?int $authorId,
        public ?string $authorUsername,
        public string $createdAt,
        public string $validUntil,
        public ?string $decidedAt,
        public ?int $decidedBy,
        public ?string $decidedByName,
    ) {}

    /**
     * Serializes data to JSON
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
