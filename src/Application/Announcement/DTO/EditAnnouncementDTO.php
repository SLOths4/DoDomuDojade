<?php
declare(strict_types=1);

namespace App\Application\Announcement\DTO;

use App\Domain\Announcement\AnnouncementException;
use App\Domain\Announcement\AnnouncementStatus;
use DateTimeImmutable;

final readonly class EditAnnouncementDTO
{
    public function __construct(
        public string $title,
        public string $text,
        public DateTimeImmutable $validUntil,
        public ?AnnouncementStatus $status = null,
    ) {}

    /**
     * @throws \DateMalformedStringException
     * @throws AnnouncementException
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
