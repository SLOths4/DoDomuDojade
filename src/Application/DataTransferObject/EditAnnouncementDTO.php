<?php
declare(strict_types=1);

namespace App\Application\DataTransferObject;

use App\Domain\Enum\AnnouncementStatus;
use App\Domain\Exception\AnnouncementException;
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
    public static function fromHttpRequest(array $post): self
    {
        $title = (string)($post['title']);
        $text = (string)($post['text'] ?? '');
        $validUntil = new DateTimeImmutable($post['valid_until']);

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
