<?php
declare(strict_types=1);

namespace App\Application\DataTransferObject;

use App\Domain\Enum\AnnouncementStatus;
use DateTimeImmutable;

final readonly class EditAnnouncementDTO
{
    public function __construct(
        public string $title,
        public string $text,
        public DateTimeImmutable $validUntil,
        public ?AnnouncementStatus $status = null,
    ) {}

    public static function fromHttpRequest(array $post): self
    {
        $title = trim((string)($post['title'] ?? ''));
        $text = trim((string)($post['text'] ?? ''));
        $validUntil = !empty($post['valid_until'])
            ? new DateTimeImmutable($post['valid_until'])
            : null;

        $status = null;
        if (isset($post['status'])) {
            $status = AnnouncementStatus::fromString($post['status']);
        }

        return new self(
            title: $title,
            text: $text,
            validUntil: $validUntil,
            status: $status,
        );
    }
}
