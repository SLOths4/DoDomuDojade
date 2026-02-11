<?php
declare(strict_types=1);

namespace App\Application\Announcement\DTO;

use App\Domain\Announcement\AnnouncementException;
use App\Domain\Announcement\AnnouncementStatus;
use DateMalformedStringException;
use DateTimeImmutable;

/**
 * Data Transfer Object for editing announcements
 */
final readonly class EditAnnouncementDTO
{
    /**
     * @param string $title
     * @param string $text
     * @param DateTimeImmutable $validUntil
     * @param AnnouncementStatus|null $status
     */
    public function __construct(
        public string $title,
        public string $text,
        public DateTimeImmutable $validUntil,
        public ?AnnouncementStatus $status = null,
    ) {}

    /**
     * Creates DTO from an array
     * @param array $array
     * @return self
     * @throws AnnouncementException
     * @throws DateMalformedStringException
     */
    public static function fromArray(array $array): self
    {
        $title = (string)($array['title']);
        $text = (string)($array['text'] ?? '');
        $validUntil = new DateTimeImmutable($array['valid_until']);

        $status = null;
        if (isset($array['status'])) {
            $status = AnnouncementStatus::fromString($array['status']);
        }

        return new self(
            title: $title,
            text: $text,
            validUntil: $validUntil,
            status: $status,
        );
    }
}
