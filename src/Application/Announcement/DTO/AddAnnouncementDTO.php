<?php

declare(strict_types=1);

namespace App\Application\Announcement\DTO;

use App\Domain\Shared\InvalidDateTimeException;
use App\Domain\Shared\MissingParameterException;
use DateMalformedStringException;
use DateTimeImmutable;

final readonly class AddAnnouncementDTO
{
    public function __construct(
        public string              $title,
        public string              $text,
        public DateTimeImmutable   $validUntil,
    ){}

    /**
     * @throws MissingParameterException
     * @throws InvalidDateTimeException
     */
    public static function fromHttpRequest(array $post): self
    {
        $title = (string)($post['title']);
        $text = (string)($post['text']);
        $validUntil = $post['valid_until'];

        try {
            $validUntil = new DateTimeImmutable($validUntil);
        } catch (DateMalformedStringException $e) {
            throw new InvalidDateTimeException($validUntil, "valid_until", null, $e);
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
