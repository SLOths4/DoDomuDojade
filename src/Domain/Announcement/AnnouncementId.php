<?php

namespace App\Domain\Announcement;

/**
 * AnnouncementId ValueObject
 */
final readonly class AnnouncementId
{
    private string $id;

    /**
     * @param string $id
     */
    public function __construct(string $id)
    {
        $this->id = $id;
    }

    /**
     * Generates a new id for an announcement
     * @return self
     */
    public static function generate(): self
    {
        return new self(uniqid('ann_', true));
    }

    /**
     * Returns id
     * @return string
     */
    public function getValue(): string
    {
        return $this->id;
    }

    /**
     * Stringifies id
     * @return string
     */
    public function __toString(): string
    {
        return $this->id;
    }
}
