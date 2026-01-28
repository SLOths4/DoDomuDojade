<?php

declare(strict_types=1);

namespace App\Application\Announcement\DTO;

use App\Domain\Shared\InvalidDateTimeException;
use App\Domain\Shared\MissingParameterException;
use DateMalformedStringException;
use DateTimeImmutable;

/**
 * Data Transfer Object for adding announcements
 */
final readonly class AddAnnouncementDTO
{
    /**
     * @param string $title
     * @param string $text
     * @param DateTimeImmutable $validUntil
     */
    public function __construct(
        public string              $title,
        public string              $text,
        public DateTimeImmutable   $validUntil,
    ){}

    /**
     * Creates DTO from an array
     * @param array $array
     * @return self
     * @throws InvalidDateTimeException
     * @throws MissingParameterException
     */
    public static function fromArray(array $array): self
    {
        $title = (string)($array['title']);
        $text = (string)($array['text']);
        $validUntil = $array['valid_until'];

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
