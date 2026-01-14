<?php

namespace App\Domain\Announcement;

final readonly class AnnouncementId
{
    private string $id;

    public function __construct(string $id)
    {
        $this->id = $id;
    }

    public static function generate(): self
    {
        return new self(uniqid('ann_', true));
    }

    public function getValue(): string
    {
        return $this->id;
    }

    public function __toString(): string
    {
        return $this->id;
    }
}
