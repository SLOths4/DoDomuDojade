<?php
declare(strict_types=1);

namespace App\Application\DataTransferObject;

use DateMalformedStringException;
use App\Domain\Exception\InvalidDateTimeException;
use App\Domain\Exception\MissingParameterException;
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
    public static function fromHttpRequest(array $post, DateTimeImmutable $defaultValidUntil): self
    {
        $title = trim((string)($post['title']));
        $text = trim((string)($post['content']));
        $validUntil = $post['expires_at'];

        if (empty($validUntil)) {
            $validUntil = $defaultValidUntil;
        } else {
            try {
                $validUntil = new DateTimeImmutable($validUntil);
            } catch (DateMalformedStringException $e) {
                throw new InvalidDateTimeException($validUntil, "expires_at", null, $e);
            }
        }

        if (empty($title)) {
            throw new MissingParameterException("title");
        }

        if (empty($text)) {
            throw new MissingParameterException("text");
        }

        return new self(
            title: $title,
            text: $text,
            validUntil: $validUntil,
        );
    }
}
