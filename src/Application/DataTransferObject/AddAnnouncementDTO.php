<?php

declare(strict_types=1);

namespace App\Application\DataTransferObject;

use DateTimeImmutable;

final readonly class AddAnnouncementDTO
{
    public function __construct(
        public string              $title,
        public string              $text,
        public DateTimeImmutable   $validUntil,
    ){}

    /**
     * @throws \DateMalformedStringException
     */
    public static function fromHttpRequest(array $post): self
    {
        $title = trim((string)($post['title'] ?? ''));
        $text = trim((string)($post['text'] ?? ''));
        $validUntil = !empty($post['valid_until'])
            ? new DateTimeImmutable($post['valid_until'])
            : null;

        return new self(
            title: $title,
            text: $text,
            validUntil: $validUntil,
        );
    }
}
