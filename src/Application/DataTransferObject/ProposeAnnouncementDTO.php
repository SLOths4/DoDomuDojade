<?php
declare(strict_types=1);

namespace App\Application\DataTransferObject;

use DateTimeImmutable;
use Exception;

/**
 * Represents HTTP data
 */
final readonly class ProposeAnnouncementDTO
{
    public function __construct(
        public string $title,
        public string $text,
        public DateTimeImmutable $validUntil,
    ) {}

    /**
     * @throws Exception
     */
    public static function fromHttpRequest(array $post, string $defaultExpiry = '+30 days'): self
    {
        $title = trim((string)($post['title'] ?? ''));
        $text = trim((string)($post['content'] ?? ''));
        $validUntil = !empty($post['expires_at'])
            ? new DateTimeImmutable($post['expires_at'])
            : new DateTimeImmutable()->modify($defaultExpiry);

        return new self(
            title: $title,
            text: $text,
            validUntil: $validUntil,
        );
    }
}
